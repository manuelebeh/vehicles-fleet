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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->string('license_plate', 30)->unique();
            $table->integer('year')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('status', 50)->default('available');
            $table->timestamps();
        });

        // Add CHECK constraint for year (PostgreSQL)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE vehicles ADD CONSTRAINT check_year_range CHECK (year >= 1900 AND year <= EXTRACT(YEAR FROM NOW()) + 1)');
        }

        // Add CHECK constraint for status
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicles ADD CONSTRAINT check_vehicle_status CHECK (status IN ('available', 'maintenance', 'out_of_service'))");
        }

        // Indexes
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('status', 'idx_vehicles_status');
            $table->index(['brand', 'model'], 'idx_vehicles_brand_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
