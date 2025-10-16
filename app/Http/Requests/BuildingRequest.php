<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuildingRequest extends FormRequest
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
            'name_ar' => 'required|min:3|max:100',
            'name_en' => 'required|min:3|max:100',
            'city_id' => 'required|exists:cities,id',
            'slug' => 'required|min:3|max:100',
            'map' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'supervisor_id' => 'required',
            'ttlock_username' => 'required',
            'ttlock_password' => 'required',
            'link' => 'required',
            'sort_order' => 'required',
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
