<?php

namespace App\Models;

class User
{
    protected string $table = 'users';

    public ?int $id = null;
    public string $uuid;
    public ?string $username = null;
    public string $email;
    public string $password;
    public string $first_name;
    public ?string $last_name = null;
    public ?string $phone = null;
    public ?string $profile_photo_path = null;
    public string $status = 'active';

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function getFullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'phone' => $this->phone,
            'profile_photo_path' => $this->profile_photo_path,
            'status' => $this->status,
        ];
    }
}
