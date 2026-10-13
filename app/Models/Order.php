<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'restaurant_id', 'driver_id', 'status', 'total_amount', 'delivery_fee'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
        ];
    }

    /**
     * In-progress orders (not delivered or cancelled).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ]);
    }

    /**
     * Accepted orders waiting for a driver.
     */
    public function scopeAvailableForDriver(Builder $query): Builder
    {
        return $query
            ->where('status', OrderStatus::Accepted)
            ->whereNull('driver_id');
    }

    /**
     * Unassigned orders waiting longer than 30 minutes.
     */
    public function scopeLate(Builder $query): Builder
    {
        return $query
            ->whereNull('driver_id')
            ->where('created_at', '<=', now()->subMinutes(30));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
