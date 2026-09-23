<?php

namespace App\Services\Inventory;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;
use Exception;

class InventoryService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function listItems(int $organizationId, int $page = 1, int $limit = 15, ?string $search = null, ?int $categoryId = null, ?string $status = null): array
    {
        if (!$this->pdo) {
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }

        $conditions = ["ii.organization_id = :org_id", "ii.deleted_at IS NULL"];
        $params = [':org_id' => $organizationId];

        if (!empty($search)) {
            $conditions[] = "(ii.item_name LIKE :search OR ii.item_code LIKE :search OR ii.location_name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($categoryId)) {
            $conditions[] = "ii.category_id = :cat_id";
            $params[':cat_id'] = $categoryId;
        }

        if (!empty($status)) {
            $conditions[] = "ii.status = :status";
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $conditions);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM inventory_items ii WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                ii.*,
                ic.name as category_name,
                (ii.quantity <= ii.reorder_level) as is_low_stock
            FROM inventory_items ii
            LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
            WHERE {$whereClause}
            ORDER BY ii.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / max(1, $limit)),
        ];
    }

    public function getItem(int $organizationId, int $id): ?array
    {
        if (!$this->pdo) return null;

        $stmt = $this->pdo->prepare("
            SELECT ii.*, ic.name as category_name
            FROM inventory_items ii
            LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
            WHERE ii.id = :id AND ii.organization_id = :org_id AND ii.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) return null;

        // Stock Transactions
        $txStmt = $this->pdo->prepare("
            SELECT st.*, CONCAT(e.first_name, ' ', e.last_name) as performer_name
            FROM stock_transactions st
            LEFT JOIN employees e ON st.performed_by = e.user_id
            WHERE st.inventory_item_id = :item_id AND st.organization_id = :org_id
            ORDER BY st.transaction_date DESC, st.id DESC
            LIMIT 10
        ");
        $txStmt->execute([':item_id' => $id, ':org_id' => $organizationId]);
        $item['transactions'] = $txStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Tracked Individual Equipment Units
        $eqStmt = $this->pdo->prepare("
            SELECT eq.*, ea.status as assignment_status, tm.name as assigned_team_name
            FROM equipment eq
            LEFT JOIN equipment_assignments ea ON eq.id = ea.equipment_id AND ea.status = 'assigned'
            LEFT JOIN teams tm ON ea.team_id = tm.id
            WHERE eq.inventory_item_id = :item_id AND eq.organization_id = :org_id AND eq.deleted_at IS NULL
            ORDER BY eq.asset_code ASC
        ");
        $eqStmt->execute([':item_id' => $id, ':org_id' => $organizationId]);
        $item['equipment_units'] = $eqStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $item;
    }

    public function createItem(int $organizationId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $this->pdo->beginTransaction();
        try {
            $code = $data['item_code'] ?? ('ITM-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));
            $initialQty = (float)($data['quantity'] ?? 0);
            $unitCost = (float)($data['unit_cost'] ?? 0);

            $sql = "
                INSERT INTO inventory_items (
                    organization_id, category_id, item_code, item_name, description,
                    unit, quantity, minimum_stock_level, reorder_level, unit_cost,
                    location_name, status, created_at, updated_at
                ) VALUES (
                    :org_id, :cat_id, :code, :name, :desc,
                    :unit, :qty, :min_lvl, :reorder_lvl, :cost,
                    :loc, :status, NOW(), NOW()
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':org_id' => $organizationId,
                ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                ':code' => $code,
                ':name' => trim($data['item_name'] ?? ''),
                ':desc' => $data['description'] ?? null,
                ':unit' => $data['unit'] ?? 'Pieces',
                ':qty' => $initialQty,
                ':min_lvl' => (float)($data['minimum_stock_level'] ?? 5),
                ':reorder_lvl' => (float)($data['reorder_level'] ?? 10),
                ':cost' => $unitCost,
                ':loc' => $data['location_name'] ?? 'Main Store Room',
                ':status' => $data['status'] ?? 'active',
            ]);

            $itemId = (int)$this->pdo->lastInsertId();

            // Record opening stock transaction if quantity > 0
            if ($initialQty > 0) {
                $txSql = "
                    INSERT INTO stock_transactions (
                        organization_id, inventory_item_id, transaction_type, quantity,
                        unit_cost, transaction_date, performed_by, remarks, created_at
                    ) VALUES (
                        :org_id, :item_id, 'opening', :qty,
                        :cost, NOW(), :perf_by, 'Initial stock on item registration', NOW()
                    )
                ";
                $txStmt = $this->pdo->prepare($txSql);
                $txStmt->execute([
                    ':org_id' => $organizationId,
                    ':item_id' => $itemId,
                    ':qty' => $initialQty,
                    ':cost' => $unitCost,
                    ':perf_by' => $performedBy,
                ]);
            }

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'INVENTORY_ITEM_CREATE',
                'Inventory',
                'inventory_items',
                $itemId,
                null,
                ['code' => $code, 'name' => $data['item_name'] ?? '', 'quantity' => $initialQty],
                "Created inventory item {$data['item_name']} ({$code})"
            );

            return [
                'id' => $itemId,
                'item_code' => $code,
                'item_name' => $data['item_name'] ?? ''
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateItem(int $organizationId, int $id, array $data, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getItem($organizationId, $id);
        if (!$existing) return false;

        $sql = "
            UPDATE inventory_items SET
                item_name = :name,
                category_id = :cat_id,
                description = :desc,
                unit = :unit,
                minimum_stock_level = :min_lvl,
                reorder_level = :reorder_lvl,
                unit_cost = :cost,
                location_name = :loc,
                status = :status,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':name' => trim($data['item_name'] ?? $existing['item_name']),
            ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : $existing['category_id'],
            ':desc' => $data['description'] ?? $existing['description'],
            ':unit' => $data['unit'] ?? $existing['unit'],
            ':min_lvl' => (float)($data['minimum_stock_level'] ?? $existing['minimum_stock_level']),
            ':reorder_lvl' => (float)($data['reorder_level'] ?? $existing['reorder_level']),
            ':cost' => (float)($data['unit_cost'] ?? $existing['unit_cost']),
            ':loc' => $data['location_name'] ?? $existing['location_name'],
            ':status' => $data['status'] ?? $existing['status'],
            ':id' => $id,
            ':org_id' => $organizationId,
        ]);

        if ($ok) {
            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'INVENTORY_ITEM_UPDATE',
                'Inventory',
                'inventory_items',
                $id,
                $existing,
                $data,
                "Updated inventory item #{$id} ({$existing['item_name']})"
            );
        }

        return $ok;
    }

    public function recordStockTransaction(int $organizationId, int $itemId, array $data, ?int $performedBy = null): array
    {
        if (!$this->pdo) return [];

        $existing = $this->getItem($organizationId, $itemId);
        if (!$existing) {
            throw new Exception("Inventory item #{$itemId} not found.");
        }

        $type = $data['transaction_type'] ?? 'adjustment';
        $qty = (float)($data['quantity'] ?? 0);
        $currentQty = (float)$existing['quantity'];

        if ($qty <= 0) {
            throw new Exception("Transaction quantity must be greater than zero.");
        }

        $this->pdo->beginTransaction();
        try {
            $isDeduction = in_array($type, ['issue', 'damage', 'loss', 'disposal']);
            if ($isDeduction) {
                if ($currentQty < $qty) {
                    throw new Exception("Insufficient stock: Current quantity is {$currentQty}, cannot deduct {$qty}.");
                }
                $newQty = $currentQty - $qty;
            } else {
                $newQty = $currentQty + $qty;
            }

            // 1. Insert transaction
            $txSql = "
                INSERT INTO stock_transactions (
                    organization_id, inventory_item_id, transaction_type, quantity,
                    unit_cost, transaction_date, performed_by, remarks, created_at
                ) VALUES (
                    :org_id, :item_id, :type, :qty,
                    :cost, NOW(), :perf_by, :remarks, NOW()
                )
            ";
            $txStmt = $this->pdo->prepare($txSql);
            $txStmt->execute([
                ':org_id' => $organizationId,
                ':item_id' => $itemId,
                ':type' => $type,
                ':qty' => $qty,
                ':cost' => (float)($data['unit_cost'] ?? $existing['unit_cost']),
                ':perf_by' => $performedBy,
                ':remarks' => $data['remarks'] ?? null,
            ]);

            // 2. Update stock quantity
            $upSql = "UPDATE inventory_items SET quantity = :new_qty, updated_at = NOW() WHERE id = :id AND organization_id = :org_id";
            $upStmt = $this->pdo->prepare($upSql);
            $upStmt->execute([':new_qty' => $newQty, ':id' => $itemId, ':org_id' => $organizationId]);

            $this->pdo->commit();

            $this->auditLog->log(
                $organizationId,
                $performedBy,
                'STOCK_TRANSACTION',
                'Inventory',
                'stock_transactions',
                $itemId,
                ['old_qty' => $currentQty],
                ['type' => $type, 'qty' => $qty, 'new_qty' => $newQty],
                "Recorded stock {$type} of {$qty} units for {$existing['item_name']}"
            );

            return [
                'new_quantity' => $newQty,
                'transaction_type' => $type
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteItem(int $organizationId, int $id, ?int $performedBy = null): bool
    {
        if (!$this->pdo) return false;

        $existing = $this->getItem($organizationId, $id);
        if (!$existing) return false;

        $stmt = $this->pdo->prepare("UPDATE inventory_items SET deleted_at = NOW() WHERE id = :id AND organization_id = :org_id");
        $stmt->execute([':id' => $id, ':org_id' => $organizationId]);

        $this->auditLog->log(
            $organizationId,
            $performedBy,
            'INVENTORY_ITEM_DELETE',
            'Inventory',
            'inventory_items',
            $id,
            $existing,
            null,
            "Soft deleted inventory item #{$id}"
        );

        return true;
    }
}
