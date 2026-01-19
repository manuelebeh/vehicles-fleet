<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create extension for PostgreSQL exclusion constraint
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        }

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->timestampTz('start_date');
            $table->timestampTz('end_date');
            $table->string('status', 50)->default('pending');
            $table->text('purpose')->nullable();
            $table->timestamps();
        });

        // Add CHECK constraint for date range
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reservations ADD CONSTRAINT valid_date_range CHECK (end_date > start_date)');
        }

        // Add CHECK constraint for status
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE reservations ADD CONSTRAINT check_reservation_status CHECK (status IN ('pending', 'confirmed', 'cancelled', 'completed'))");
        }

        // Add exclusion constraint for overlapping reservations (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE reservations
                ADD CONSTRAINT no_overlapping_reservations
                EXCLUDE USING gist (
                    vehicle_id WITH =,
                    tsrange(start_date, end_date) WITH &&
                ) WHERE (status = 'confirmed')
            ");
        }

        // Indexes
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_reservations_user_status');
            $table->index('status', 'idx_reservations_status');
            $table->index(['vehicle_id', 'start_date', 'end_date'], 'idx_reservations_dates');
        });

        // Partial index for confirmed reservations (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX idx_reservations_vehicle_status 
                ON reservations(vehicle_id, status) 
                WHERE status = 'confirmed'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
