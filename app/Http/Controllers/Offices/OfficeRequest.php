<?php

namespace App\Http\Controllers\Offices;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;

class OfficeRequest extends FormRequest
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
            'name' => 'required|max:50',
            'addr' => 'required|max:250',
            'address' => 'required|max:250',
            'sms' => 'nullable|max:500',
        ];
    }

    /**
     * Validator instance add-on.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('tel') and !Controller::checkPhone($this->input('tel'))) {
                $validator->errors()->add('tel', 'Номер телефона секретаря указан неправильно');
            }
        });
    }
}
