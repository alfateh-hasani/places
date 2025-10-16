<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'order' => 'nullable|integer|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'title_ar.required' => 'العنوان بالعربية مطلوب',
            'title_ar.string' => 'العنوان بالعربية يجب أن يكون نص',
            'title_ar.max' => 'العنوان بالعربية يجب أن لا يتجاوز 255 حرف',
            'title_en.required' => 'العنوان بالإنجليزية مطلوب',
            'title_en.string' => 'العنوان بالإنجليزية يجب أن يكون نص',
            'title_en.max' => 'العنوان بالإنجليزية يجب أن لا يتجاوز 255 حرف',
            'description_ar.string' => 'الوصف بالعربية يجب أن يكون نص',
            'description_en.string' => 'الوصف بالإنجليزية يجب أن يكون نص',
            'order.integer' => 'الترتيب يجب أن يكون رقم صحيح',
            'order.min' => 'الترتيب يجب أن يكون أكبر من أو يساوي 0'
        ];
    }
}
