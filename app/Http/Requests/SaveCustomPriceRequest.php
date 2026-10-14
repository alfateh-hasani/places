<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCustomPriceRequest extends FormRequest
{
    /**
     * Upper bound for a single-day custom price (SAR). Guards against
     * fat-finger input and numeric overflow; adjust in one place if the
     * business ceiling ever changes.
     */
    public const MAX_CUSTOM_PRICE = 1_000_000;

    /**
     * Determine if the user is authorized to make this request.
     *
     * The route itself is gated by `staff.can:apartment.update`; this keeps
     * authorization consistent with the sibling admin form requests.
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Pull the apartment id from the route so it can be validated alongside
     * the body, keeping all validation in one place.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'apartment_id' => $this->route('id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'custom_price' => ['required', 'numeric', 'gt:0', 'max:'.self::MAX_CUSTOM_PRICE],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'apartment_id.exists' => 'الشقة غير موجودة.',
            'date.after_or_equal' => 'لا يمكن تحديد سعر لتاريخ سابق.',
            'custom_price.gt' => 'يجب أن يكون السعر المخصص أكبر من صفر.',
            'custom_price.max' => 'السعر المخصص يتجاوز الحد الأعلى المسموح به.',
        ];
    }
}
