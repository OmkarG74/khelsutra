-- Phase 2 Operations Delta
-- Mirroring the operations_delta migration

-- 1. Engine conversion to InnoDB
ALTER TABLE venues ENGINE = InnoDB;
ALTER TABLE venue_facilities ENGINE = InnoDB;
ALTER TABLE venue_bookings ENGINE = InnoDB;
ALTER TABLE venue_maintenance ENGINE = InnoDB;
ALTER TABLE housekeeping_tasks ENGINE = InnoDB;
ALTER TABLE events ENGINE = InnoDB;
ALTER TABLE event_participants ENGINE = InnoDB;
ALTER TABLE school_activities ENGINE = InnoDB;
ALTER TABLE vehicles ENGINE = InnoDB;
ALTER TABLE transport_trips ENGINE = InnoDB;
ALTER TABLE transport_passengers ENGINE = InnoDB;
ALTER TABLE accommodations ENGINE = InnoDB;
ALTER TABLE accommodation_rooms ENGINE = InnoDB;
ALTER TABLE accommodation_allocations ENGINE = InnoDB;

DELIMITER $$
CREATE PROCEDURE sp_upgrade_operations_delta()
BEGIN
    -- 2. New columns
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'housekeeping_tasks' AND COLUMN_NAME = 'priority') THEN
        ALTER TABLE housekeeping_tasks ADD COLUMN priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium';
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venue_bookings' AND COLUMN_NAME = 'cancelled_at') THEN
        ALTER TABLE venue_bookings 
          ADD COLUMN cancelled_at TIMESTAMP NULL,
          ADD COLUMN cancelled_by BIGINT UNSIGNED NULL,
          ADD COLUMN training_session_id BIGINT UNSIGNED NULL,
          ADD COLUMN fixture_id BIGINT UNSIGNED NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transport_trips' AND COLUMN_NAME = 'training_camp_id') THEN
        ALTER TABLE transport_trips 
          ADD COLUMN training_camp_id BIGINT UNSIGNED NULL,
          ADD COLUMN tournament_id BIGINT UNSIGNED NULL,
          ADD COLUMN expense_id BIGINT UNSIGNED NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accommodation_allocations' AND COLUMN_NAME = 'training_camp_id') THEN
        ALTER TABLE accommodation_allocations 
          ADD COLUMN training_camp_id BIGINT UNSIGNED NULL,
          ADD COLUMN tournament_id BIGINT UNSIGNED NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venue_maintenance' AND COLUMN_NAME = 'expense_id') THEN
        ALTER TABLE venue_maintenance 
          ADD COLUMN expense_id BIGINT UNSIGNED NULL;
    END IF;

    -- 3. Tenant ID + Backfill for child tables
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_participants' AND COLUMN_NAME = 'organization_id') THEN
        ALTER TABLE event_participants ADD COLUMN organization_id BIGINT UNSIGNED NULL;
        UPDATE event_participants ep JOIN events e ON ep.event_id = e.id SET ep.organization_id = e.organization_id;
        ALTER TABLE event_participants MODIFY organization_id BIGINT UNSIGNED NOT NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transport_passengers' AND COLUMN_NAME = 'organization_id') THEN
        ALTER TABLE transport_passengers ADD COLUMN organization_id BIGINT UNSIGNED NULL;
        UPDATE transport_passengers tp JOIN transport_trips t ON tp.transport_trip_id = t.id SET tp.organization_id = t.organization_id;
        ALTER TABLE transport_passengers MODIFY organization_id BIGINT UNSIGNED NOT NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accommodation_rooms' AND COLUMN_NAME = 'organization_id') THEN
        ALTER TABLE accommodation_rooms ADD COLUMN organization_id BIGINT UNSIGNED NULL;
        UPDATE accommodation_rooms ar JOIN accommodations a ON ar.accommodation_id = a.id SET ar.organization_id = a.organization_id;
        ALTER TABLE accommodation_rooms MODIFY organization_id BIGINT UNSIGNED NOT NULL;
    END IF;

    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accommodation_allocations' AND COLUMN_NAME = 'organization_id') THEN
        ALTER TABLE accommodation_allocations ADD COLUMN organization_id BIGINT UNSIGNED NULL;
        UPDATE accommodation_allocations aa JOIN accommodations a ON aa.accommodation_id = a.id SET aa.organization_id = a.organization_id;
        ALTER TABLE accommodation_allocations MODIFY organization_id BIGINT UNSIGNED NOT NULL;
    END IF;

    -- 4. Indexes
    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venue_bookings' AND INDEX_NAME = 'idx_vb_org_venue_fac_date_status') THEN
        CREATE INDEX idx_vb_org_venue_fac_date_status ON venue_bookings (organization_id, venue_id, facility_id, booking_date, status);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accommodation_allocations' AND INDEX_NAME = 'idx_aa_org_room_stat_dates') THEN
        CREATE INDEX idx_aa_org_room_stat_dates ON accommodation_allocations (organization_id, room_id, status, check_in_date, check_out_date);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transport_trips' AND INDEX_NAME = 'idx_tt_org_veh_date') THEN
        CREATE INDEX idx_tt_org_veh_date ON transport_trips (organization_id, vehicle_id, trip_date);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_participants' AND INDEX_NAME = 'idx_ep_org_event') THEN
        CREATE INDEX idx_ep_org_event ON event_participants (organization_id, event_id);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transport_passengers' AND INDEX_NAME = 'idx_tp_org_trip') THEN
        CREATE INDEX idx_tp_org_trip ON transport_passengers (organization_id, transport_trip_id);
    END IF;

END $$
DELIMITER ;

CALL sp_upgrade_operations_delta();
DROP PROCEDURE sp_upgrade_operations_delta;

CREATE TABLE IF NOT EXISTS vehicle_positions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    speed DECIMAL(5, 2) NULL,
    heading INT NULL,
    recorded_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vp_org_veh_time (organization_id, vehicle_id, recorded_at),
    CONSTRAINT fk_vp_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS geofences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    venue_id BIGINT UNSIGNED NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    radius_meters INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_gf_org (organization_id),
    CONSTRAINT fk_gf_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
