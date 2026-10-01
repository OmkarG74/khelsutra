<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class extends Migration
{
    public function up(): void
    {
        $schema = DB::schema();

        if (!$schema->hasTable('equipment_rentals')) {
            $schema->create('equipment_rentals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('inventory_item_id');

                // Borrower info (polymorphic or just IDs as in existing schema)
                $table->enum('borrower_type', ['athlete', 'coach', 'employee', 'team', 'venue', 'other'])->default('athlete');
                $table->unsignedBigInteger('athlete_id')->nullable();
                $table->unsignedBigInteger('coach_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->unsignedBigInteger('venue_id')->nullable();
                $table->string('borrower_name', 150)->nullable(); // fallback for generic persons

                $table->decimal('borrowed_quantity', 14, 2)->default(1);
                $table->decimal('returned_quantity', 14, 2)->default(0);
                $table->decimal('damaged_quantity', 14, 2)->default(0);

                $table->dateTime('start_time');
                $table->dateTime('expected_return_time')->nullable();
                $table->dateTime('actual_return_time')->nullable();

                $table->string('condition_on_return', 100)->nullable();

                $table->enum('status', ['issued', 'returned', 'partially_returned', 'returned_with_damage', 'lost', 'cancelled'])->default('issued');

                $table->unsignedBigInteger('issued_by')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('organization_id');
                $table->index('inventory_item_id');
                $table->index('status');
                $table->index('start_time');
            });
        }
    }

    public function down(): void
    {
        $schema = DB::schema();
        if ($schema->hasTable('equipment_rentals')) {
            $schema->drop('equipment_rentals');
        }
    }
};
