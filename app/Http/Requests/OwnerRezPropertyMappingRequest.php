<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnerRezPropertyMappingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'apartment_id' => 'required|exists:apartments,id|unique:ownerrez_property_mappings,apartment_id,'.$id,
            'ownerrez_property_id' => 'required|string|max:255|unique:ownerrez_property_mappings,ownerrez_property_id,'.$id,
            'ownerrez_property_name' => 'nullable|string|max:255',
            'sync_enabled' => 'boolean',
            'check_availability_enabled' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'apartment_id.required' => 'يجب اختيار الشقة',
            'apartment_id.exists' => 'الشقة المحددة غير موجودة',
            'apartment_id.unique' => 'هذه الشقة مربوطة بالفعل مع OwnerRez',
            'ownerrez_property_id.unique' => 'هذا العقار في OwnerRez مربوط بالفعل مع شقة أخرى',
            'ownerrez_property_id.required' => 'يجب إدخال Property ID من OwnerRez',
            'ownerrez_property_id.string' => 'Property ID يجب أن يكون نصاً',
            'ownerrez_property_id.max' => 'Property ID طويل جداً',
            'ownerrez_property_name.max' => 'اسم العقار طويل جداً',
            'notes.max' => 'الملاحظات طويلة جداً (الحد الأقصى 1000 حرف)',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'apartment_id' => 'الشقة',
            'ownerrez_property_id' => 'Property ID',
            'ownerrez_property_name' => 'اسم العقار',
            'sync_enabled' => 'المزامنة التلقائية',
            'check_availability_enabled' => 'التحقق من التوفر',
            'notes' => 'الملاحظات',
        ];
    }
}
