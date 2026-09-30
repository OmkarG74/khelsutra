<?php

namespace App\Services\Audit;

use App\Services\BaseService;
use PDO;

class AuditLogService extends BaseService
{
    private static array $sensitiveKeys = [
        'password', 'password_confirmation', 'token', 'remember_token', 'secret', 'api_key'
    ];

    public function log(
        ?int $orgId,
        ?int $userId,
        string $action,
        string $module,
        ?string $tableName = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): bool {
        if (!$this->pdo) {
            return false;
        }

        $sanitizedOld = $oldValues ? self::sanitize($oldValues) : null;
        $sanitizedNew = $newValues ? self::sanitize($newValues) : null;

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Internal/System';

        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs 
            (organization_id, user_id, action, module, table_name, record_id, old_values, new_values, description, ip_address, user_agent, created_at)
            VALUES 
            (:org_id, :user_id, :action, :module, :table_name, :record_id, :old_values, :new_values, :description, :ip, :ua, NOW())
        ");

        return $stmt->execute([
            ':org_id' => $orgId,
            ':user_id' => $userId,
            ':action' => $action,
            ':module' => $module,
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':old_values' => $sanitizedOld ? json_encode($sanitizedOld) : null,
            ':new_values' => $sanitizedNew ? json_encode($sanitizedNew) : null,
            ':description' => $description,
            ':ip' => substr($ipAddress, 0, 45),
            ':ua' => substr($userAgent, 0, 500),
        ]);
    }

    public function getLogs(?int $orgId = null, int|array $limit = 50, int|array $offset = 0, array $filters = []): array
    {
        if (is_array($limit)) {
            $filters = $limit;
            $limit = is_numeric($offset) ? (int)$offset : 50;
            $offset = 0;
        } elseif (is_array($offset)) {
            $filters = $offset;
            $offset = 0;
        }

        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $limit = (int)$filters['limit'];
        }
        if (isset($filters['offset']) && is_numeric($filters['offset'])) {
            $offset = (int)$filters['offset'];
        }

        if (!$this->pdo) return [];

        $sql = "SELECT a.*, u.first_name, u.last_name, u.email as user_email, o.name as organization_name 
                FROM audit_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                LEFT JOIN organizations o ON a.organization_id = o.id 
                WHERE 1=1 ";
        $params = [];

        if ($orgId !== null) {
            $sql .= " AND a.organization_id = :org_id ";
            $params[':org_id'] = $orgId;
        }

        if (!empty($filters['module'])) {
            $sql .= " AND a.module = :module ";
            $params[':module'] = $filters['module'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action ";
            $params[':action'] = $filters['action'];
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::$sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
