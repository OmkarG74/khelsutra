<?php

namespace App\Services\Venue;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;
use Exception;

class VenueService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listVenues(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?string $status = null): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }

        $conditions = ["v.organization_id = :org_id", "v.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(v.name LIKE :search OR v.venue_code LIKE :search OR v.city LIKE :search OR v.venue_type LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($status)) {
            $conditions[] = "v.status = :status";
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $conditions);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM venues v WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                v.*,
                COUNT(DISTINCT vf.id) as facility_count,
                COUNT(DISTINCT vb.id) as booking_count
            FROM venues v
            LEFT JOIN venue_facilities vf ON v.id = vf.venue_id AND vf.deleted_at IS NULL
            LEFT JOIN venue_bookings vb ON v.id = vb.venue_id AND vb.deleted_at IS NULL
            WHERE {$whereClause}
            GROUP BY v.id
            ORDER BY v.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $venues,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / max(1, $limit)),
        ];
    }

    public function getVenue(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT * FROM venues
            WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venue) return null;

        // Facilities
        $facStmt = $this->pdo->prepare("
            SELECT * FROM venue_facilities
            WHERE venue_id = :v_id AND organization_id = :org_id AND deleted_at IS NULL
            ORDER BY name ASC
        ");
        $facStmt->execute([':v_id' => $id, ':org_id' => $organizationId]);
        $venue['facilities'] = $facStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Recent & Upcoming Bookings
        $bookStmt = $this->pdo->prepare("
            SELECT vb.*, vf.name as facility_name, t.name as team_name
            FROM venue_bookings vb
            LEFT JOIN venue_facilities vf ON vb.facility_id = vf.id
            LEFT JOIN teams t ON vb.team_id = t.id
            WHERE vb.venue_id = :v_id AND vb.organization_id = :org_id AND vb.deleted_at IS NULL
            ORDER BY vb.booking_date DESC, vb.start_time ASC
            LIMIT 10
        ");
        $bookStmt->execute([':v_id' => $id, ':org_id' => $organizationId]);
        $venue['bookings'] = $bookStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Maintenance Logs
        $maintStmt = $this->pdo->prepare("
            SELECT vm.*, vf.name as facility_name
            FROM venue_maintenance vm
            LEFT JOIN venue_facilities vf ON vm.facility_id = vf.id
            WHERE vm.venue_id = :v_id AND vm.organization_id = :org_id AND vm.deleted_at IS NULL
            ORDER BY vm.scheduled_date DESC
            LIMIT 5
        ");
        $maintStmt->execute([':v_id' => $id, ':org_id' => $organizationId]);
        $venue['maintenance'] = $maintStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $venue;
    }

    public function createVenue(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $code = $data['venue_code'] ?? ('VEN-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

            $sql = "
                INSERT INTO venues (
                    organization_id, venue_code, name, venue_type, description,
                    address_line1, city, state, country, postal_code,
                    capacity, opening_time, closing_time, status, created_at, updated_at
                ) VALUES (
                    :org_id, :code, :name, :type, :desc,
                    :addr, :city, :state, 'India', :zip,
                    :cap, :open, :close, :status, NOW(), NOW()
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':org_id' => $organizationId,
                ':code' => $code,
                ':name' => trim($data['name'] ?? ''),
                ':type' => $data['venue_type'] ?? 'Sports Complex',
                ':desc' => $data['description'] ?? null,
                ':addr' => $data['address_line1'] ?? null,
                ':city' => $data['city'] ?? 'Mumbai',
                ':state' => $data['state'] ?? 'Maharashtra',
                ':zip' => $data['postal_code'] ?? null,
                ':cap' => !empty($data['capacity']) ? (int)$data['capacity'] : null,
                ':open' => $data['opening_time'] ?? '06:00:00',
                ':close' => $data['closing_time'] ?? '22:00:00',
                ':status' => $data['status'] ?? 'active',
            ]);

            $venueId = (int)$this->pdo->lastInsertId();

            // Optional initial facility
            if (!empty($data['facility_name'])) {
                $facSql = "
                    INSERT INTO venue_facilities (organization_id, venue_id, name, facility_type, capacity, status, created_at, updated_at)
                    VALUES (:org_id, :v_id, :name, :type, :cap, 'active', NOW(), NOW())
                ";
                $fStmt = $this->pdo->prepare($facSql);
                $fStmt->execute([
                    ':org_id' => $organizationId,
                    ':v_id' => $venueId,
                    ':name' => trim($data['facility_name']),
                    ':type' => $data['facility_type'] ?? 'Main Field',
                    ':cap' => !empty($data['facility_capacity']) ? (int)$data['facility_capacity'] : null
                ]);
            }

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'VENUE_CREATE',
                'Venues',
                'venues',
                $venueId,
                null,
                ['code' => $code, 'name' => $data['name'] ?? ''],
                "Created venue {$data['name']} ({$code})"
            );

            return [
                'id' => $venueId,
                'venue_code' => $code,
                'name' => $data['name'] ?? ''
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateVenue(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getVenue($organizationId, $id);
        if (!$existing) return false;

        $sql = "
            UPDATE venues SET
                name = :name,
                venue_type = :type,
                description = :desc,
                address_line1 = :addr,
                city = :city,
                state = :state,
                postal_code = :zip,
                capacity = :cap,
                opening_time = :open,
                closing_time = :close,
                status = :status,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':name' => trim($data['name'] ?? $existing['name']),
            ':type' => $data['venue_type'] ?? $existing['venue_type'],
            ':desc' => $data['description'] ?? $existing['description'],
            ':addr' => $data['address_line1'] ?? $existing['address_line1'],
            ':city' => $data['city'] ?? $existing['city'],
            ':state' => $data['state'] ?? $existing['state'],
            ':zip' => $data['postal_code'] ?? $existing['postal_code'],
            ':cap' => !empty($data['capacity']) ? (int)$data['capacity'] : $existing['capacity'],
            ':open' => $data['opening_time'] ?? $existing['opening_time'],
            ':close' => $data['closing_time'] ?? $existing['closing_time'],
            ':status' => $data['status'] ?? $existing['status'],
            ':id' => $id,
            ':org_id' => $organizationId,
        ]);

        if ($ok) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'VENUE_UPDATE',
                'Venues',
                'venues',
                $id,
                $existing,
                $data,
                "Updated venue #{$id} ({$existing['name']})"
            );
        }

        return $ok;
    }

    public function createFacility(int $organizationId, int $venueId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $sql = "
            INSERT INTO venue_facilities (
                organization_id, venue_id, name, facility_type, description, capacity, status, created_at, updated_at
            ) VALUES (
                :org_id, :v_id, :name, :type, :desc, :cap, 'active', NOW(), NOW()
            )
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':org_id' => $organizationId,
            ':v_id' => $venueId,
            ':name' => trim($data['name'] ?? ''),
            ':type' => $data['facility_type'] ?? 'Court',
            ':desc' => $data['description'] ?? null,
            ':cap' => !empty($data['capacity']) ? (int)$data['capacity'] : null,
        ]);

        $facId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'FACILITY_CREATE',
            'Venues',
            'venue_facilities',
            $facId,
            null,
            ['facility_id' => $facId, 'name' => $data['name'] ?? ''],
            "Created facility {$data['name']} under venue #{$venueId}"
        );

        return ['id' => $facId, 'name' => $data['name'] ?? ''];
    }

    public function createBooking(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $venueId = (int)($data['venue_id'] ?? 0);
        $facilityId = !empty($data['facility_id']) ? (int)$data['facility_id'] : null;
        $bookingDate = $data['booking_date'] ?? date('Y-m-d');
        $startTime = $data['start_time'] ?? '08:00:00';
        $endTime = $data['end_time'] ?? '10:00:00';

        if ($endTime <= $startTime) {
            throw new Exception("Booking end time ({$endTime}) must be strictly after start time ({$startTime}).");
        }

        // Service-level Time Conflict Check without changing database schema (Section 35)
        if ($facilityId) {
            $conflictStmt = $this->pdo->prepare("
                SELECT id, booking_reference, start_time, end_time 
                FROM venue_bookings 
                WHERE facility_id = :fac_id 
                  AND booking_date = :b_date 
                  AND status IN ('pending', 'approved') 
                  AND deleted_at IS NULL
                  AND (start_time < :end_time AND end_time > :start_time)
                LIMIT 1
            ");
            $conflictStmt->execute([
                ':fac_id' => $facilityId,
                ':b_date' => $bookingDate,
                ':start_time' => $startTime,
                ':end_time' => $endTime
            ]);
            $conflict = $conflictStmt->fetch(PDO::FETCH_ASSOC);

            if ($conflict) {
                throw new Exception("Time slot conflict: Facility is already booked ({$conflict['booking_reference']}) from {$conflict['start_time']} to {$conflict['end_time']} on {$bookingDate}.");
            }
        }

        $ref = $data['booking_reference'] ?? ('BKG-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

        $sql = "
            INSERT INTO venue_bookings (
                organization_id, venue_id, facility_id, booking_reference,
                booked_by_user_id, booking_type, purpose, team_id,
                booking_date, start_time, end_time, status, approved_by, approved_at, notes, created_at, updated_at
            ) VALUES (
                :org_id, :v_id, :fac_id, :ref,
                :user_id, :b_type, :purpose, :team_id,
                :b_date, :stime, :etime, 'approved', :appr_by, NOW(), :notes, NOW(), NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':org_id' => $organizationId,
            ':v_id' => $venueId,
            ':fac_id' => $facilityId,
            ':ref' => $ref,
            ':user_id' => $performedBy,
            ':b_type' => $data['booking_type'] ?? 'Training',
            ':purpose' => $data['purpose'] ?? 'Squad Training Session',
            ':team_id' => !empty($data['team_id']) ? (int)$data['team_id'] : null,
            ':b_date' => $bookingDate,
            ':stime' => $startTime,
            ':etime' => $endTime,
            ':appr_by' => $performedBy,
            ':notes' => $data['notes'] ?? null,
        ]);

        $bookingId = (int)$this->pdo->lastInsertId();

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'VENUE_BOOKING_CREATE',
            'Venues',
            'venue_bookings',
            $bookingId,
            null,
            ['reference' => $ref, 'facility_id' => $facilityId, 'date' => $bookingDate],
            "Created venue booking {$ref} for {$bookingDate} ({$startTime} - {$endTime})"
        );

        return [
            'id' => $bookingId,
            'booking_reference' => $ref,
            'booking_date' => $bookingDate
        ];
    }

    public function deleteVenue(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getVenue($organizationId, $id);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("UPDATE venues SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'VENUE_DELETE',
            'Venues',
            'venues',
            $id,
            $existing,
            null,
            "Soft deleted venue #{$id}"
        );

        return true;
    }
}
