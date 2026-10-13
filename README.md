# توصيلة — Delivery Orders API

Laravel API for a small Latakia delivery platform that connects customers, restaurants/shops, and drivers. API-only — all responses are JSON (`{ "data": ... }`).

## Features

- List active restaurants (suspended shops hidden by default)
- Browse available products per restaurant
- Place and cancel orders (cancel only while `Pending`)
- Admin: manage restaurants, products, accept orders, list/filter orders
- Drivers: see available orders, accept one at a time, mark picked up / delivered
- Delivery fee calculated on the server (swap formula via container `bind`)

## Tech Stack

- PHP 8.3+
- Laravel 13
- SQLite / MySQL
- Eloquent ORM
- Form Requests
- Enums, Interface + `bind`, Trait for JSON responses

## Database

| Table | Purpose |
|-------|---------|
| `users` | Customers, drivers, and admins (`role`) |
| `restaurants` | Shops; `is_active = false` means suspended |
| `products` | Menu items per restaurant |
| `orders` | Order header: customer, restaurant, optional driver, status, totals |
| `order_items` | Products and quantities for each order |

Relationships:

```
User (customer) → Orders
User (driver) → Orders
Restaurant → Products, Orders
Order → OrderItems → Product
```

## Order Status

`Pending` → `Accepted` → `PickedUp` → `Delivered`  
`Pending` → `Cancelled`

Flow:

1. Customer creates order → `Pending`
2. Admin accepts → `Accepted` (no driver yet)
3. Driver claims from available list → `driver_id` set
4. Driver picks up → `PickedUp`
5. Driver delivers → `Delivered`

A driver can have only one **active** order at a time (not delivered/cancelled).

## Business Rules

- Public restaurant lists hide suspended shops (global scope)
- Admin can see/manage suspended shops with `withoutGlobalScope('active')`
- “Active order” = not `Delivered` and not `Cancelled` (`Order::active()` local scope — not global, so reports can still see history)
- Available for drivers = `Accepted` + no `driver_id`
- Delivery fee is calculated by `DeliveryFeeCalculatorInterface` (bound to percentage-based calculator: 10% of items subtotal, minimum 10)

## Architecture

```
Route → Form Request → Controller → Service → Model
```

- Thin controllers; JSON via `ApiResponse` trait
- Order business logic in `OrderService`
- Delivery fee strategy: interface + `bind` in `AppServiceProvider`
- Validation in Form Requests
- Status / role via `OrderStatus` and `UserRole` enums

### Query scopes

| Scope | Type | Meaning |
|-------|------|---------|
| `Restaurant` active | Global | Only `is_active = true` |
| `Order::active()` | Local | Not delivered / cancelled |
| `Order::availableForDriver()` | Local | Accepted, unassigned |
| `Order::late()` | Local | Unassigned for more than 30 minutes |
| `Order::today()` | Local | Created today |
| `Product::available()` | Local | `is_available = true` |

Composable example:

```php
Order::query()->active()->today()->latest()->get();
```

## Database indexes

Only columns used in filters/sorts are indexed (FK columns already get indexes from `foreignId`).

| Table | Index | Reason |
|-------|-------|--------|
| `orders` | `(status, driver_id)` | `availableForDriver` / `active` filters |
| `orders` | `created_at` | `latest()` sort; `today` / `late` filters |
| `restaurants` | `is_active` | Global scope on public lists |
| `products` | `(restaurant_id, is_available)` | Customer menu filter |
| `users` | `role` | Customer/driver lookups |

## N+1 and selective columns

List endpoints use `with(...)` / `withCount(...)` and only load needed columns (e.g. `customer:id,name`).

In local/development, lazy loading is blocked so N+1 shows up immediately:

```php
// AppServiceProvider::boot()
Model::preventLazyLoading(! app()->isProduction());
```

### Admin orders (“الطلبات الحالية”)

Endpoint: `GET /api/admin/orders?filter=active`

Eager loads:

- `customer:id,name`
- `restaurant:id,name`
- `driver:id,name`
- `withCount('orderItems')` (count only, not all item rows)

Expected query count for any list size (e.g. 50 orders): **4**

1. `orders` (+ `withCount` subquery)
2. `users` (customers)
3. `restaurants`
4. `users` (drivers)

How to measure:

```bash
# In tinker or a temporary route, wrap the query with:
DB::enableQueryLog();
# ... run the endpoint logic ...
count(DB::getQueryLog());
```

Or use Laravel Debugbar / Telescope in local.

## API Endpoints

### Public / Customer

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/restaurants` | Active restaurants only |
| GET | `/api/restaurants/{restaurant}/products` | Available products |
| POST | `/api/orders` | Create order (`customer_id`, `restaurant_id`, `items`) |
| POST | `/api/orders/{order}/cancel` | Cancel if `Pending` |
| GET | `/api/customers/{customer}/orders` | Orders for a customer |

### Driver

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/driver/orders/available` | Accepted, no driver |
| POST | `/api/driver/orders/{order}/accept` | Body: `{ "driver_id": 1 }` |
| POST | `/api/driver/orders/{order}/picked-up` | → `PickedUp` |
| POST | `/api/driver/orders/{order}/delivered` | → `Delivered` |
| GET | `/api/drivers/{driver}/orders` | Assigned orders |

### Admin

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/restaurants` | All restaurants (incl. suspended) |
| POST | `/api/admin/restaurants` | Create restaurant |
| POST | `/api/admin/restaurants/{id}/suspend` | Suspend |
| POST | `/api/admin/restaurants/{id}/activate` | Activate |
| GET | `/api/admin/restaurants/{id}/products` | All products |
| POST | `/api/admin/restaurants/{id}/products` | Add product |
| GET | `/api/admin/orders` | All orders; optional `?filter=active\|today\|late` |
| POST | `/api/admin/orders/{order}/accept` | `Pending` → `Accepted` |

### Example requests

**Create order**

```json
POST /api/orders
{
  "customer_id": 1,
  "restaurant_id": 1,
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

**Driver accept**

```json
POST /api/driver/orders/1/accept
{ "driver_id": 2 }
```

**Admin current orders**

```http
GET /api/admin/orders?filter=active
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure DB in `.env` (default example uses SQLite), then:

```bash
php artisan migrate --seed
php artisan serve
```

API base: `http://127.0.0.1:8000/api`

Errors for API routes return JSON (e.g. `{ "message": "Not found." }` for 404).
