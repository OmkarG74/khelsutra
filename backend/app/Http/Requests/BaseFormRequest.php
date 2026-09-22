<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Validation\DatabasePresenceVerifier;

abstract class BaseFormRequest
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        if (isset($this->data['organization_id'])) {
            unset($this->data['organization_id']);
        }
    }

    abstract public function rules(): array;

    public function validate(): array
    {
        $loader = new ArrayLoader();
        $translator = new Translator($loader, 'en');
        $factory = new Factory($translator);
        
        $presenceVerifier = new DatabasePresenceVerifier(DB::getDatabaseManager());
        $factory->setPresenceVerifier($presenceVerifier);

        $validator = $factory->make($this->data, $this->rules());

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }
}
