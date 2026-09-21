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

-- 2. New columns
ALTER TABLE housekeeping_tasks ADD COLUMN priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium';

ALTER TABLE venue_bookings 
  ADD COLUMN cancelled_at TIMESTAMP NULL,
  ADD COLUMN cancelled_by BIGINT UNSIGNED NULL,
  ADD COLUMN training_session_id BIGINT UNSIGNED NULL,
  ADD COLUMN fixture_id BIGINT UNSIGNED NULL;

ALTER TABLE transport_trips 
  ADD COLUMN training_camp_id BIGINT UNSIGNED NULL,
  ADD COLUMN tournament_id BIGINT UNSIGNED NULL,
  ADD COLUMN expense_id BIGINT UNSIGNED NULL;

ALTER TABLE accommodation_allocations 
  ADD COLUMN training_camp_id BIGINT UNSIGNED NULL,
  ADD COLUMN tournament_id BIGINT UNSIGNED NULL;

ALTER TABLE venue_maintenance 
  ADD COLUMN expense_id BIGINT UNSIGNED NULL;

-- 3. Tenant ID + Backfill for child tables
ALTER TABLE event_participants ADD COLUMN organization_id BIGINT UNSIGNED NULL;
UPDATE event_participants ep JOIN events e ON ep.event_id = e.id SET ep.organization_id = e.organization_id;
ALTER TABLE event_participants MODIFY organization_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE transport_passengers ADD COLUMN organization_id BIGINT UNSIGNED NULL;
UPDATE transport_passengers tp JOIN transport_trips t ON tp.transport_trip_id = t.id SET tp.organization_id = t.organization_id;
ALTER TABLE transport_passengers MODIFY organization_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE accommodation_rooms ADD COLUMN organization_id BIGINT UNSIGNED NULL;
UPDATE accommodation_rooms ar JOIN accommodations a ON ar.accommodation_id = a.id SET ar.organization_id = a.organization_id;
ALTER TABLE accommodation_rooms MODIFY organization_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE accommodation_allocations ADD COLUMN organization_id BIGINT UNSIGNED NULL;
UPDATE accommodation_allocations aa JOIN accommodations a ON aa.accommodation_id = a.id SET aa.organization_id = a.organization_id;
ALTER TABLE accommodation_allocations MODIFY organization_id BIGINT UNSIGNED NOT NULL;

-- 4. Indexes
CREATE INDEX idx_vb_org_venue_fac_date_status ON venue_bookings (organization_id, venue_id, facility_id, booking_date, status);
CREATE INDEX idx_aa_org_room_stat_dates ON accommodation_allocations (organization_id, room_id, status, check_in_date, check_out_date);
CREATE INDEX idx_tt_org_veh_date ON transport_trips (organization_id, vehicle_id, trip_date);
CREATE INDEX idx_ep_org_event ON event_participants (organization_id, event_id);
CREATE INDEX idx_tp_org_trip ON transport_passengers (organization_id, transport_trip_id);
