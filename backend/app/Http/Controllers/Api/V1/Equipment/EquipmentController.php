<?php

namespace App\Http\Controllers\Api\V1\Equipment;

use App\Http\Controllers\Controller;
use App\Services\Equipment\EquipmentService;
use App\Helpers\ApiResponse;
use InvalidArgumentException;
use Exception;

class EquipmentController extends Controller
{
    protected EquipmentService $equipmentService;

    public function __construct(?EquipmentService $equipmentService = null)
    {
        $this->equipmentService = $equipmentService ?? new EquipmentService();
    }

    /**
     * List equipment rentals.
     */
    public function index(int $orgId): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 15)));
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $itemId = !empty($_GET['inventory_item_id']) ? (int)$_GET['inventory_item_id'] : null;

        $result = $this->equipmentService->listRentals(
            $orgId,
            $page,
            $limit,
            $search ?: null,
            $status ?: null,
            $itemId
        );

        return ApiResponse::success($result, 'Equipment rentals retrieved successfully', 200);
    }

    /**
     * Show single rental details.
     */
    public function show(int $orgId, int $id): array
    {
        $rental = $this->equipmentService->getRental($orgId, $id);
        if (!$rental) {
            return ApiResponse::error('Rental not found or access denied.', null, 404);
        }
        return ApiResponse::success($rental, 'Rental retrieved successfully', 200);
    }

    /**
     * Issue equipment (start new rental).
     */
    public function issue(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['inventory_item_id'])) {
            return ApiResponse::error('At least one inventory item is required.', ['inventory_item_id' => ['The inventory_item_id field is required.']], 422);
        }

        try {
            $created = $this->equipmentService->issueEquipment($orgId, $requestData, $performedBy);
            return ApiResponse::success($created, 'Equipment issued successfully', 201);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (Exception $e) {
            return ApiResponse::error('Failed to issue equipment: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Return equipment (update rental).
     */
    public function returnItem(int $orgId, int $id, array $requestData, ?int $performedBy = null): array
    {
        try {
            $result = $this->equipmentService->returnEquipment($orgId, $id, $requestData, $performedBy);
            return ApiResponse::success($result, 'Equipment returned successfully', 200);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (Exception $e) {
            return ApiResponse::error('Failed to process return: ' . $e->getMessage(), null, 500);
        }
    }
}
