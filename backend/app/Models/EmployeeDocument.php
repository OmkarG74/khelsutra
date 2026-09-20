<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;

class EmployeeDocument
{
    use BelongsToOrganization;

    protected string $table = 'employee_documents';

    public ?int $id = null;
    public int $organization_id;
    public int $employee_id;
    public string $document_type;
    public ?string $document_number = null;
    public string $file_path;
    public ?string $issue_date = null;
    public ?string $expiry_date = null;
    public ?string $notes = null;
    public ?int $uploaded_by = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'employee_id' => $this->employee_id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'file_path' => $this->file_path,
            'issue_date' => $this->issue_date,
            'expiry_date' => $this->expiry_date,
            'notes' => $this->notes,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
