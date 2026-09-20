<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\EmployeeDocumentService;
use App\Helpers\ApiResponse;

class EmployeeDocumentController extends Controller
{
    protected EmployeeDocumentService $docService;

    public function __construct(?EmployeeDocumentService $docService = null)
    {
        $this->docService = $docService ?? new EmployeeDocumentService();
    }

    public function index(int $orgId, int $employeeId): array
    {
        $docs = $this->docService->listDocuments($orgId, $employeeId);
        return ApiResponse::success($docs, 'Employee documents retrieved', 200);
    }

    public function store(int $orgId, int $employeeId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['document_type']) || empty($requestData['file_path'])) {
            return ApiResponse::error('Document type and file path are required.', null, 422);
        }

        $doc = $this->docService->addDocument($orgId, $employeeId, $requestData, $performedBy);
        if (!$doc) {
            return ApiResponse::error('Failed to save document record.', null, 500);
        }
        return ApiResponse::success($doc, 'Document recorded successfully', 201);
    }
}
