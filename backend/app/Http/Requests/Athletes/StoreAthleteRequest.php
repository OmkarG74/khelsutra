<?php

namespace App\Http\Requests\Athletes;

class StoreAthleteRequest
{
    public array $data;
    public ?array $files;

    public function __construct(array $data, ?array $files = null)
    {
        $this->data = $data;
        $this->files = $files;
    }

    public function validate(): array
    {
        $errors = [];

        // 1. Personal Information
        if (empty(trim($this->data['first_name'] ?? ''))) {
            $errors['first_name'][] = 'First name is required.';
        } elseif (strlen(trim($this->data['first_name'])) > 100) {
            $errors['first_name'][] = 'First name must not exceed 100 characters.';
        }

        if (empty(trim($this->data['last_name'] ?? ''))) {
            $errors['last_name'][] = 'Last name is required.';
        } elseif (strlen(trim($this->data['last_name'])) > 100) {
            $errors['last_name'][] = 'Last name must not exceed 100 characters.';
        }

        if (empty($this->data['date_of_birth'])) {
            $errors['date_of_birth'][] = 'Date of birth is required.';
        } else {
            $dobTime = strtotime($this->data['date_of_birth']);
            if (!$dobTime || $dobTime > time()) {
                $errors['date_of_birth'][] = 'Date of birth must be a valid past date.';
            }
        }

        if (empty($this->data['gender'])) {
            $errors['gender'][] = 'Gender is required.';
        } elseif (!in_array($this->data['gender'], ['male', 'female', 'other', 'not_specified'], true)) {
            $errors['gender'][] = 'Please select a valid gender option.';
        }

        if (!empty($this->data['email']) && !filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please provide a valid athlete email address.';
        }

        if (!empty($this->data['phone']) && !preg_match('/^[0-9+\s\-()]{7,25}$/', trim($this->data['phone']))) {
            $errors['phone'][] = 'Please enter a valid phone number.';
        }

        if (!empty($this->data['postal_code']) && strlen(trim($this->data['postal_code'])) > 20) {
            $errors['postal_code'][] = 'Postal code must not exceed 20 characters.';
        }

        // 2. Sport & Roster
        $sportId = (int)($this->data['current_sport_id'] ?? ($this->data['primary_sport_id'] ?? 0));
        if ($sportId <= 0) {
            $errors['current_sport_id'][] = 'Primary sport selection is required.';
        }

        // 3. Athlete Status
        if (empty(trim($this->data['status'] ?? ''))) {
            $errors['status'][] = 'Athlete status is required.';
        } elseif (!in_array($this->data['status'], ['active', 'inactive', 'injured', 'suspended'], true)) {
            $errors['status'][] = 'Please select a valid athlete status.';
        }

        // 4. Account / Login validation
        $createAccount = !empty($this->data['create_account']) && ($this->data['create_account'] === '1' || $this->data['create_account'] === true || $this->data['create_account'] === 'on');
        if ($createAccount) {
            $loginEmail = trim($this->data['login_email'] ?? ($this->data['email'] ?? ''));
            if (empty($loginEmail)) {
                $errors['login_email'][] = 'Login email address is required when creating an account.';
            } elseif (!filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['login_email'][] = 'Please provide a valid login email address.';
            }
        }

        return $errors;
    }
}
