<?php

namespace App\Services\Equipment;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use App\Services\Inventory\InventoryService;
use PDO;
use InvalidArgumentException;
use RuntimeException;

class EquipmentService extends BaseService
{
    protected ?AuditLogService $auditLogService = null;
    protected ?InventoryService $inventoryService = null;

    public function __construct(?AuditLogService $auditLogService = null, ?InventoryService $inventoryService = null)
    {
        parent::__construct();
        $this->auditLogService = $auditLogService ?: new AuditLogService();
        $this->inventoryService = $inventoryService ?: new InventoryService($this->pdo, $this->auditLogService);
    }

    protected function resolveAuditUserId(?int $userId, int $organizationId): ?int
    {
        if (!$this->pdo) return null;
        if ($userId) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            if ($stmt->fetchColumn()) {
                return $userId;
            }
        }

        return null;
    }

    /**
     * List equipment rentals with optional search and status.
     */
    public function listRentals(
        int $organizationId,
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        ?int $inventoryItemId = null
    ): array {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $perPage, 'total_pages' => 0];
        }

        $where = ["er.organization_id = :org_id", "er.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if ($search) {
            $where[] = "(er.borrower_name LIKE :search OR ii.item_name LIKE :search OR ii.item_code LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($inventoryItemId) {
            $where[] = "er.inventory_item_id = :inv_item_id";
            $params[':inv_item_id'] = $inventoryItemId;
        }

        $whereClause = implode(" AND ", $where);

        $having = [];
        if ($status) {
            // Grouped status determination
            if ($status === 'issued') {
                $having[] = "SUM(er.borrowed_quantity) > (SUM(er.returned_quantity) + SUM(er.damaged_quantity))";
            } elseif ($status === 'returned') {
                $having[] = "SUM(er.borrowed_quantity) <= (SUM(er.returned_quantity) + SUM(er.damaged_quantity))";
            } elseif ($status === 'returned_with_damage') {
                $having[] = "SUM(er.borrowed_quantity) <= (SUM(er.returned_quantity) + SUM(er.damaged_quantity)) AND SUM(er.damaged_quantity) > 0";
            } elseif ($status === 'partially_returned') {
                // To keep filter functional, partially returned = some returned but still outstanding
                $having[] = "SUM(er.borrowed_quantity) > (SUM(er.returned_quantity) + SUM(er.damaged_quantity)) AND (SUM(er.returned_quantity) + SUM(er.damaged_quantity)) > 0";
            }
        }

        $havingClause = !empty($having) ? "HAVING " . implode(" AND ", $having) : "";

        $countSql = "
            SELECT COUNT(*) FROM (
                SELECT MAX(er.id)
                FROM equipment_rentals er
                LEFT JOIN inventory_items ii ON er.inventory_item_id = ii.id
                WHERE {$whereClause}
                GROUP BY
                    er.organization_id,
                    er.inventory_item_id,
                    er.borrower_type,
                    er.athlete_id,
                    er.coach_id,
                    er.employee_id,
                    er.team_id,
                    er.venue_id,
                    er.borrower_name
                {$havingClause}
            ) as grouped
        ";

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                MAX(er.id) as id,
                er.organization_id,
                er.inventory_item_id,
                er.borrower_type,
                er.athlete_id,
                er.coach_id,
                er.employee_id,
                er.team_id,
                er.venue_id,
                er.borrower_name,
                SUM(er.borrowed_quantity) as borrowed_quantity,
                SUM(er.returned_quantity) as returned_quantity,
                SUM(er.damaged_quantity) as damaged_quantity,
                MIN(er.start_time) as start_time,
                MAX(er.expected_return_time) as expected_return_time,
                MAX(er.actual_return_time) as actual_return_time,
                CASE
                    WHEN SUM(er.borrowed_quantity) > (SUM(er.returned_quantity) + SUM(er.damaged_quantity)) THEN 'issued'
                    WHEN SUM(er.damaged_quantity) > 0 THEN 'returned_with_damage'
                    ELSE 'returned'
                END as status,
                ii.item_name,
                ii.item_code,
                CASE
                    WHEN er.borrower_type = 'athlete' THEN CONCAT(MAX(ath.first_name), ' ', MAX(ath.last_name))
                    WHEN er.borrower_type = 'coach' THEN (
                        SELECT CONCAT(emp_c.first_name, ' ', emp_c.last_name)
                        FROM coach_profiles cp
                        JOIN employees emp_c ON cp.employee_id = emp_c.id
                        WHERE cp.id = er.coach_id LIMIT 1
                    )
                    WHEN er.borrower_type = 'employee' THEN CONCAT(MAX(emp.first_name), ' ', MAX(emp.last_name))
                    WHEN er.borrower_type = 'team' THEN MAX(tm.name)
                    WHEN er.borrower_type = 'venue' THEN MAX(vn.name)
                    ELSE er.borrower_name
                END as resolved_borrower_name
            FROM equipment_rentals er
            LEFT JOIN inventory_items ii ON er.inventory_item_id = ii.id
            LEFT JOIN athletes ath ON er.athlete_id = ath.id
            LEFT JOIN employees emp ON er.employee_id = emp.id
            LEFT JOIN teams tm ON er.team_id = tm.id
            LEFT JOIN venues vn ON er.venue_id = vn.id
            WHERE {$whereClause}
            GROUP BY
                er.organization_id,
                er.inventory_item_id,
                er.borrower_type,
                er.athlete_id,
                er.coach_id,
                er.employee_id,
                er.team_id,
                er.venue_id,
                er.borrower_name,
                ii.item_name,
                ii.item_code
            {$havingClause}
            ORDER BY MAX(er.id) DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $perPage,
            'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 1
        ];
    }

    /**
     * Get a single rental record.
     */
    public function getRental(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT
                er.*,
                ii.item_name,
                ii.item_code,
                CASE
                    WHEN er.borrower_type = 'athlete' THEN CONCAT(ath.first_name, ' ', ath.last_name)
                    WHEN er.borrower_type = 'coach' THEN (
                        SELECT CONCAT(emp_c.first_name, ' ', emp_c.last_name)
                        FROM coach_profiles cp
                        JOIN employees emp_c ON cp.employee_id = emp_c.id
                        WHERE cp.id = er.coach_id
                    )
                    WHEN er.borrower_type = 'employee' THEN CONCAT(emp.first_name, ' ', emp.last_name)
                    WHEN er.borrower_type = 'team' THEN tm.name
                    WHEN er.borrower_type = 'venue' THEN vn.name
                    ELSE er.borrower_name
                END as resolved_borrower_name,
                CONCAT(u_iss.first_name, ' ', u_iss.last_name) as issued_by_name,
                CONCAT(u_rec.first_name, ' ', u_rec.last_name) as received_by_name
            FROM equipment_rentals er
            LEFT JOIN inventory_items ii ON er.inventory_item_id = ii.id
            LEFT JOIN athletes ath ON er.athlete_id = ath.id
            LEFT JOIN employees emp ON er.employee_id = emp.id
            LEFT JOIN teams tm ON er.team_id = tm.id
            LEFT JOIN venues vn ON er.venue_id = vn.id
            LEFT JOIN users u_iss ON er.issued_by = u_iss.id
            LEFT JOIN users u_rec ON er.received_by = u_rec.id
            WHERE er.id = :id AND er.organization_id = :org_id AND er.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Issue equipment to a borrower (Start a new rental).
     */
    public function issueEquipment(int $organizationId, array $data, ?int $userId = null): array
    {
        if (!$this->pdo) throw new RuntimeException("Database connection unavailable.");

        $itemIds = (array)($data['inventory_item_id'] ?? []);
        $quantities = (array)($data['borrowed_quantity'] ?? []);

        if (empty($itemIds)) {
            throw new InvalidArgumentException("At least one inventory item is required.");
        }

        // Remove empty values that might come from hidden clones
        $filteredItems = [];
        $filteredQuantities = [];
        foreach ($itemIds as $index => $id) {
            if (!empty($id)) {
                $filteredItems[] = $id;
                $filteredQuantities[] = $quantities[$index] ?? 1;
            }
        }
        $itemIds = $filteredItems;
        $quantities = $filteredQuantities;

        if (empty($itemIds)) {
            throw new InvalidArgumentException("At least one inventory item is required.");
        }

        if (count($itemIds) !== count(array_unique($itemIds))) {
            throw new InvalidArgumentException("Duplicate inventory items selected. Please combine quantities for the same item.");
        }

        $borrowerType = trim($data['borrower_type'] ?? 'other');
        $allowedTypes = ['athlete', 'coach', 'employee', 'team', 'venue', 'other'];
        if (!in_array($borrowerType, $allowedTypes, true)) {
            $borrowerType = 'other';
        }

        $athleteId = null;
        $coachId = null;
        $employeeId = null;
        $teamId = null;
        $venueId = null;
        $borrowerName = trim($data['borrower_name'] ?? '');

        switch ($borrowerType) {
            case 'athlete':
                $athleteId = !empty($data['athlete_id']) ? (int)$data['athlete_id'] : null;
                if (!$athleteId) throw new InvalidArgumentException("Athlete ID is required.");
                break;
            case 'coach':
                $coachId = !empty($data['coach_id']) ? (int)$data['coach_id'] : null;
                if (!$coachId) throw new InvalidArgumentException("Coach ID is required.");
                break;
            case 'employee':
                $employeeId = !empty($data['employee_id']) ? (int)$data['employee_id'] : null;
                if (!$employeeId) throw new InvalidArgumentException("Employee ID is required.");
                break;
            case 'team':
                $teamId = !empty($data['team_id']) ? (int)$data['team_id'] : null;
                if (!$teamId) throw new InvalidArgumentException("Team ID is required.");
                break;
            case 'venue':
                $venueId = !empty($data['venue_id']) ? (int)$data['venue_id'] : null;
                if (!$venueId) throw new InvalidArgumentException("Venue ID is required.");
                break;
            case 'other':
                if (empty($borrowerName)) throw new InvalidArgumentException("Borrower name is required when type is 'other'.");
                break;
        }

        $startTime = !empty($data['start_time']) ? trim($data['start_time']) : date('Y-m-d H:i:s');
        $expectedReturnTime = !empty($data['expected_return_time']) ? trim($data['expected_return_time']) : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;

        $auditUser = $this->resolveAuditUserId($userId, $organizationId);

        $this->pdo->beginTransaction();
        try {
            $firstRental = null;

            foreach ($itemIds as $index => $inventoryItemId) {
                $inventoryItemId = (int)$inventoryItemId;
                $borrowedQty = !empty($quantities[$index]) ? (float)$quantities[$index] : 1;

                if ($borrowedQty <= 0) {
                    throw new InvalidArgumentException("Borrowed quantity must be greater than zero.");
                }

                // Lock inventory item to prevent overselling
                $lockStmt = $this->pdo->prepare("SELECT * FROM inventory_items WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL FOR UPDATE");
                $lockStmt->execute([':id' => $inventoryItemId, ':org_id' => $organizationId]);
                $item = $lockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new InvalidArgumentException("Inventory item with ID {$inventoryItemId} not found.");
                }

                if ($item['status'] !== 'active') {
                    throw new InvalidArgumentException("Cannot issue equipment because the inventory item '{$item['item_name']}' is not active.");
                }

                if ((float)$item['quantity'] < $borrowedQty) {
                    throw new InvalidArgumentException("Insufficient stock for '{$item['item_name']}'. Only {$item['quantity']} available.");
                }

                // 1. Deduct stock using InventoryService
                $this->inventoryService->recordStockTransaction($organizationId, $inventoryItemId, [
                    'transaction_type' => 'issue',
                    'quantity' => $borrowedQty,
                    'remarks' => "Equipment issued to {$borrowerType}",
                    'reference_type' => 'equipment_rental',
                ], $auditUser);

                // 2. Create the rental record
                $insStmt = $this->pdo->prepare("
                    INSERT INTO equipment_rentals (
                        organization_id, inventory_item_id, borrower_type,
                        athlete_id, coach_id, employee_id, team_id, venue_id, borrower_name,
                        borrowed_quantity, returned_quantity, damaged_quantity,
                        start_time, expected_return_time, status, issued_by, notes,
                        created_at, updated_at
                    ) VALUES (
                        :org_id, :item_id, :borrower_type,
                        :athlete_id, :coach_id, :employee_id, :team_id, :venue_id, :borrower_name,
                        :borrowed_qty, 0, 0,
                        :start_time, :expected_return_time, 'issued', :issued_by, :notes,
                        NOW(), NOW()
                    )
                ");

                $insStmt->execute([
                    ':org_id' => $organizationId,
                    ':item_id' => $inventoryItemId,
                    ':borrower_type' => $borrowerType,
                    ':athlete_id' => $athleteId,
                    ':coach_id' => $coachId,
                    ':employee_id' => $employeeId,
                    ':team_id' => $teamId,
                    ':venue_id' => $venueId,
                    ':borrower_name' => $borrowerName,
                    ':borrowed_qty' => $borrowedQty,
                    ':start_time' => $startTime,
                    ':expected_return_time' => $expectedReturnTime,
                    ':issued_by' => $auditUser,
                    ':notes' => $notes
                ]);

                $rentalId = (int)$this->pdo->lastInsertId();

                // Optionally, update the inventory transaction with the real reference ID
                $this->pdo->prepare("UPDATE stock_transactions SET reference_id = :ref_id WHERE id = LAST_INSERT_ID()")->execute([':ref_id' => $rentalId]);

                if (!isset($firstRentalId)) {
                    $firstRentalId = $rentalId;
                }
            }

            $this->pdo->commit();

            return $this->getRental($organizationId, $firstRentalId ?? $rentalId);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function returnEquipment(int $organizationId, int $rentalId, array $data, ?int $userId = null): array
    {
        if (!$this->pdo) throw new RuntimeException("Database connection unavailable.");

        $this->pdo->beginTransaction();
        try {
            // 1. Identify the base group from the provided ID
            $baseStmt = $this->pdo->prepare("SELECT * FROM equipment_rentals WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL");
            $baseStmt->execute([':id' => $rentalId, ':org_id' => $organizationId]);
            $baseRental = $baseStmt->fetch(PDO::FETCH_ASSOC);

            if (!$baseRental) {
                throw new InvalidArgumentException("Rental record not found.");
            }

            // 2. Fetch all outstanding rentals for this exact borrower + item group
            $groupSql = "
                SELECT * FROM equipment_rentals
                WHERE organization_id = :org_id
                  AND inventory_item_id = :item_id
                  AND borrower_type = :borrower_type
                  AND (athlete_id = :athlete_id OR (athlete_id IS NULL AND :athlete_id_null = 1))
                  AND (coach_id = :coach_id OR (coach_id IS NULL AND :coach_id_null = 1))
                  AND (employee_id = :employee_id OR (employee_id IS NULL AND :employee_id_null = 1))
                  AND (team_id = :team_id OR (team_id IS NULL AND :team_id_null = 1))
                  AND (venue_id = :venue_id OR (venue_id IS NULL AND :venue_id_null = 1))
                  AND (borrower_name = :borrower_name OR (borrower_name IS NULL AND :borrower_name_null = 1))
                  AND deleted_at IS NULL
                  AND status NOT IN ('returned', 'returned_with_damage', 'lost', 'cancelled')
                  ORDER BY id ASC
                  FOR UPDATE
            ";

            $groupStmt = $this->pdo->prepare($groupSql);
            $groupStmt->execute([
                ':org_id' => $organizationId,
                ':item_id' => $baseRental['inventory_item_id'],
                ':borrower_type' => $baseRental['borrower_type'],
                ':athlete_id' => $baseRental['athlete_id'],
                ':athlete_id_null' => is_null($baseRental['athlete_id']) ? 1 : 0,
                ':coach_id' => $baseRental['coach_id'],
                ':coach_id_null' => is_null($baseRental['coach_id']) ? 1 : 0,
                ':employee_id' => $baseRental['employee_id'],
                ':employee_id_null' => is_null($baseRental['employee_id']) ? 1 : 0,
                ':team_id' => $baseRental['team_id'],
                ':team_id_null' => is_null($baseRental['team_id']) ? 1 : 0,
                ':venue_id' => $baseRental['venue_id'],
                ':venue_id_null' => is_null($baseRental['venue_id']) ? 1 : 0,
                ':borrower_name' => $baseRental['borrower_name'],
                ':borrower_name_null' => is_null($baseRental['borrower_name']) ? 1 : 0,
            ]);
            $activeRentals = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$activeRentals) {
                throw new InvalidArgumentException("This rental group is already fully returned or closed.");
            }

            $returnQtyLeft = isset($data['returned_quantity']) ? (float)$data['returned_quantity'] : 0;
            $damageQtyLeft = isset($data['damaged_quantity']) ? (float)$data['damaged_quantity'] : 0;
            // Handle lost quantity by adding to damaged if the DB doesn't support lost column yet
            $lostQtyLeft = isset($data['lost_quantity']) ? (float)$data['lost_quantity'] : 0;
            $damageQtyLeft += $lostQtyLeft;

            $totalReturningNow = $returnQtyLeft + $damageQtyLeft;
            if ($totalReturningNow <= 0) {
                throw new InvalidArgumentException("You must specify a returned or damaged/lost quantity greater than zero.");
            }

            $totalRemaining = 0;
            foreach ($activeRentals as $rent) {
                $totalRemaining += (float)$rent['borrowed_quantity'] - (float)$rent['returned_quantity'] - (float)$rent['damaged_quantity'];
            }

            if ($totalReturningNow > $totalRemaining) {
                throw new InvalidArgumentException("Cannot return {$totalReturningNow} items. Only {$totalRemaining} items remain to be returned.");
            }

            $auditUser = $this->resolveAuditUserId($userId, $organizationId);

            // Return good items to inventory
            if ($returnQtyLeft > 0) {
                $this->inventoryService->recordStockTransaction($organizationId, $baseRental['inventory_item_id'], [
                    'transaction_type' => 'return',
                    'quantity' => $returnQtyLeft,
                    'remarks' => "Returned from rental group (including #{$rentalId})",
                    'reference_type' => 'equipment_rental',
                    'reference_id' => $rentalId
                ], $auditUser);
            }

            $actualReturnTime = !empty($data['actual_return_time']) ? trim($data['actual_return_time']) : date('Y-m-d H:i:s');

            // In the new UI we have return_date and return_time
            if (!empty($data['return_date']) && !empty($data['return_time'])) {
                $actualReturnTime = $data['return_date'] . ' ' . $data['return_time'] . ':00';
            }

            $conditionOnReturn = !empty($data['condition_on_return']) ? trim($data['condition_on_return']) : null;

            $updStmt = $this->pdo->prepare("
                UPDATE equipment_rentals SET
                    returned_quantity = :ret_qty,
                    damaged_quantity = :dam_qty,
                    actual_return_time = :ret_time,
                    condition_on_return = :cond,
                    status = :status,
                    received_by = :rec_by,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
            ");

            foreach ($activeRentals as $rent) {
                if ($returnQtyLeft <= 0 && $damageQtyLeft <= 0) {
                    break;
                }

                $rentRem = (float)$rent['borrowed_quantity'] - (float)$rent['returned_quantity'] - (float)$rent['damaged_quantity'];
                if ($rentRem <= 0) continue;

                $applyReturn = min($returnQtyLeft, $rentRem);
                $returnQtyLeft -= $applyReturn;
                $rentRem -= $applyReturn;

                $applyDamage = min($damageQtyLeft, $rentRem);
                $damageQtyLeft -= $applyDamage;
                $rentRem -= $applyDamage;

                $newReturned = (float)$rent['returned_quantity'] + $applyReturn;
                $newDamaged = (float)$rent['damaged_quantity'] + $applyDamage;
                $totalReturnedAllTime = $newReturned + $newDamaged;

                $status = $rent['status'];
                if ($totalReturnedAllTime >= (float)$rent['borrowed_quantity']) {
                    if ($newDamaged > 0) {
                        $status = 'returned_with_damage';
                    } else {
                        $status = 'returned';
                    }
                } else {
                    $status = 'partially_returned';
                }

                $notes = $rent['notes'];
                if (!empty($data['notes'])) {
                    $notes .= "\n[" . date('Y-m-d H:i') . "] " . trim($data['notes']);
                }

                $updStmt->execute([
                    ':ret_qty' => $newReturned,
                    ':dam_qty' => $newDamaged,
                    ':ret_time' => $actualReturnTime,
                    ':cond' => $conditionOnReturn,
                    ':status' => $status,
                    ':rec_by' => $auditUser,
                    ':notes' => $notes,
                    ':id' => $rent['id']
                ]);
            }

            $this->pdo->commit();

            return $this->getRental($organizationId, $rentalId);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
