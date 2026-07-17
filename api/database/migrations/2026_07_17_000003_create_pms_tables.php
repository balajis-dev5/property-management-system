<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->unsignedTinyInteger('floors');
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->string('unit_no', 20);
            $table->unsignedTinyInteger('floor');
            $table->string('type', 10); // 1BHK | 2BHK | 3BHK
            $table->string('facing', 10); // north | south | east | west
            $table->unsignedSmallInteger('area_sqft');
            $table->unsignedBigInteger('price'); // base + floor rise + PLC, in rupees
            $table->string('status', 10)->default('available'); // available | held | booked | sold
            $table->timestamps();

            $table->unique(['block_id', 'unit_no']);
            $table->index(['status', 'type']);
            $table->index('floor');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('stage', 12)->default('hold'); // hold | booked | sold | cancelled
            $table->unsignedBigInteger('price_snapshot'); // price at booking time — source tables change
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamps();

            $table->index(['stage', 'hold_expires_at']);
        });

        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage', 12)->nullable();
            $table->string('to_stage', 12);
            $table->string('note')->nullable();
            $table->timestamp('created_at');

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_events');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('units');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('projects');
    }
};
