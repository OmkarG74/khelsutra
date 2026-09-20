<?php

namespace App\Http\Requests\Teams;

class StoreTeamRequest
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
            $errors['name'][] = 'The team name is required.';
        }
        if (empty($this->data['sport_id'])) {
            $errors['sport_id'][] = 'The sport selection is required.';
        }
        return $errors;
    }
}
