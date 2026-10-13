<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', array_column(OrderStatus::cases(), 'value'))->default(OrderStatus::Pending->value);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('delivery_fee', 10, 2);
            $table->timestamps();

            // availableForDriver / active lists: filter by status (+ driver_id null)
            $table->index(['status', 'driver_id']);
            // latest() sort; today() / late() filters on admin orders
            $table->index('created_at');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
