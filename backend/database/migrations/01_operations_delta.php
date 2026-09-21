<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class extends Migration
{
    public function up(): void
    {
        $schema = DB::schema();

        // 1. Convert tables to InnoDB
        $tables = [
            'venues', 'venue_facilities', 'venue_bookings', 'venue_maintenance',
            'housekeeping_tasks', 'events', 'event_participants', 'school_activities',
            'vehicles', 'transport_trips', 'transport_passengers', 'accommodations',
            'accommodation_rooms', 'accommodation_allocations'
        ];

        foreach ($tables as $table) {
            if ($schema->hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
            }
        }

        // 2. Add columns
        if ($schema->hasTable('housekeeping_tasks') && !$schema->hasColumn('housekeeping_tasks', 'priority')) {
            $schema->table('housekeeping_tasks', function (Blueprint $table) {
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            });
        }

        if ($schema->hasTable('venue_bookings')) {
            $schema->table('venue_bookings', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('venue_bookings', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                }
                if (!$schema->hasColumn('venue_bookings', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable();
                }
                if (!$schema->hasColumn('venue_bookings', 'training_session_id')) {
                    $table->unsignedBigInteger('training_session_id')->nullable();
                }
                if (!$schema->hasColumn('venue_bookings', 'fixture_id')) {
                    $table->unsignedBigInteger('fixture_id')->nullable();
                }
            });
        }

        if ($schema->hasTable('transport_trips')) {
            $schema->table('transport_trips', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('transport_trips', 'training_camp_id')) {
                    $table->unsignedBigInteger('training_camp_id')->nullable();
                }
                if (!$schema->hasColumn('transport_trips', 'tournament_id')) {
                    $table->unsignedBigInteger('tournament_id')->nullable();
                }
                if (!$schema->hasColumn('transport_trips', 'expense_id')) {
                    $table->unsignedBigInteger('expense_id')->nullable();
                }
            });
        }

        if ($schema->hasTable('accommodation_allocations')) {
            $schema->table('accommodation_allocations', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('accommodation_allocations', 'training_camp_id')) {
                    $table->unsignedBigInteger('training_camp_id')->nullable();
                }
                if (!$schema->hasColumn('accommodation_allocations', 'tournament_id')) {
                    $table->unsignedBigInteger('tournament_id')->nullable();
                }
            });
        }

        if ($schema->hasTable('venue_maintenance')) {
            $schema->table('venue_maintenance', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('venue_maintenance', 'expense_id')) {
                    $table->unsignedBigInteger('expense_id')->nullable();
                }
            });
        }

        // 3. Tenant ID + Backfill for child tables
        if ($schema->hasTable('event_participants') && !$schema->hasColumn('event_participants', 'organization_id')) {
            $schema->table('event_participants', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable();
            });
            DB::statement("UPDATE event_participants ep JOIN events e ON ep.event_id = e.id SET ep.organization_id = e.organization_id");
            DB::statement("ALTER TABLE event_participants MODIFY organization_id BIGINT UNSIGNED NOT NULL");
        }

        if ($schema->hasTable('transport_passengers') && !$schema->hasColumn('transport_passengers', 'organization_id')) {
            $schema->table('transport_passengers', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable();
            });
            DB::statement("UPDATE transport_passengers tp JOIN transport_trips t ON tp.transport_trip_id = t.id SET tp.organization_id = t.organization_id");
            DB::statement("ALTER TABLE transport_passengers MODIFY organization_id BIGINT UNSIGNED NOT NULL");
        }

        if ($schema->hasTable('accommodation_rooms') && !$schema->hasColumn('accommodation_rooms', 'organization_id')) {
            $schema->table('accommodation_rooms', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable();
            });
            DB::statement("UPDATE accommodation_rooms ar JOIN accommodations a ON ar.accommodation_id = a.id SET ar.organization_id = a.organization_id");
            DB::statement("ALTER TABLE accommodation_rooms MODIFY organization_id BIGINT UNSIGNED NOT NULL");
        }

        if ($schema->hasTable('accommodation_allocations') && !$schema->hasColumn('accommodation_allocations', 'organization_id')) {
            // the above block might have added some columns, so let's just do an alter
            $schema->table('accommodation_allocations', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable();
            });
            DB::statement("UPDATE accommodation_allocations aa JOIN accommodations a ON aa.accommodation_id = a.id SET aa.organization_id = a.organization_id");
            DB::statement("ALTER TABLE accommodation_allocations MODIFY organization_id BIGINT UNSIGNED NOT NULL");
        }

        // 4. Indexes
        $this->addIndexIfNotExists('venue_bookings', ['organization_id', 'venue_id', 'facility_id', 'booking_date', 'status'], 'idx_vb_org_venue_fac_date_status');
        $this->addIndexIfNotExists('accommodation_allocations', ['organization_id', 'room_id', 'status', 'check_in_date', 'check_out_date'], 'idx_aa_org_room_stat_dates');
        $this->addIndexIfNotExists('transport_trips', ['organization_id', 'vehicle_id', 'trip_date'], 'idx_tt_org_veh_date');
        $this->addIndexIfNotExists('event_participants', ['organization_id', 'event_id'], 'idx_ep_org_event');
        $this->addIndexIfNotExists('transport_passengers', ['organization_id', 'transport_trip_id'], 'idx_tp_org_trip');
    }

    private function addIndexIfNotExists($table, $columns, $name)
    {
        $schema = DB::schema();
        if ($schema->hasTable($table)) {
            $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$name]);
            if (empty($indexes)) {
                $schema->table($table, function (Blueprint $t) use ($columns, $name) {
                    $t->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        $schema = DB::schema();
        // Down is optional for purely additive schema according to brief, but let's provide best effort.
        $this->dropIndexIfExists('venue_bookings', 'idx_vb_org_venue_fac_date_status');
        $this->dropIndexIfExists('accommodation_allocations', 'idx_aa_org_room_stat_dates');
        $this->dropIndexIfExists('transport_trips', 'idx_tt_org_veh_date');
        $this->dropIndexIfExists('event_participants', 'idx_ep_org_event');
        $this->dropIndexIfExists('transport_passengers', 'idx_tp_org_trip');
        // Not dropping columns to avoid data loss, but we could.
    }

    private function dropIndexIfExists($table, $name)
    {
        $schema = DB::schema();
        if ($schema->hasTable($table)) {
            $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$name]);
            if (!empty($indexes)) {
                $schema->table($table, function (Blueprint $t) use ($name) {
                    $t->dropIndex($name);
                });
            }
        }
    }
};
