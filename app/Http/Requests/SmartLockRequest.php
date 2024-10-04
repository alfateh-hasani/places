<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmartLockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'lock_alias' => 'required',
            'lock_id' => 'required',
            'lock_name' => 'required',
            ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'lock_alias' => __('cms.lock_alias'),
            'lock_id' => __('cms.lock_id'),
            'lock_name' => __('cms.lock_name'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [


        ];
    }
}
