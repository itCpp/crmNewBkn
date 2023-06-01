<?php

namespace App\Http\Requests\Mailler;

use Illuminate\Foundation\Http\FormRequest;

class CreateMaillerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => ["nullable", "string", "max:255"],
            'type' => ["nullable", "integer"],
            'destination' => ["required", "email"],
            'is_active' => ["nullable", "boolean"],
            'config.change_pin' => ["nullable", "boolean"],
            'config.change_status' => ["nullable", "boolean"],
            'config.from_name' => ["nullable", "string"],
            'config.message' => ["nullable", "string"],
            'config.pins' => ["nullable", "array"],
            'config.status_from' => ["nullable", "integer", "exists:statuses,id"],
            'config.status_to' => ["nullable", "integer", "exists:statuses,id"],
            'config.subject' => ["nullable", "string"],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'type' => 1,
        ]);
    }

    /**
     * Наименование атрибутов
     * 
     * @return array
     */
    public function attributes()
    {
        return [
            'destination' => "Адресат",
        ];
    }
}
