<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApartmentRequest extends FormRequest
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
            'name_ar' => 'required',
            'name_en' => 'required',
            'building_id' => 'required|exists:buildings,id',
            'description_ar' => 'required',
            'description_en' => 'required',
            'num_rooms' => 'required',
            'num_beds' => 'required',
            'adults_count' => 'required',
            'children_count' => 'required',
            'slug' => 'required',
            // 'area' => 'required',
            // 'is_active' => 'required',
            // 'smart_lock_id' => 'required|exists:smart_locks,id',
            // 'price' => 'required',
            // 'policy_id' => 'required|exists:policies,id',
            // 'features' => 'required|array',
            // 'features.*' => 'exists:features,id',
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
            //
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
            //
        ];
    }
}
