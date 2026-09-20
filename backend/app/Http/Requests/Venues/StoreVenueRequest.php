<?php

namespace App\Http\Requests\Venues;

class StoreVenueRequest
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->data['name'])) {
            $errors['name'][] = 'The venue name is required.';
        }
        return $errors;
    }
}
