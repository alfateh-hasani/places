<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DirectBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check() && backpack_user()->can('direct-booking.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'number_of_adults' => ['required', 'integer', 'min:1', 'max:50'],
            'number_of_children' => ['required', 'integer', 'min:0', 'max:50'],

            // Customer: existing OR new — enforced in withValidator() for a clear message.
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'new_customer.first_name' => ['nullable', 'string', 'max:255'],
            'new_customer.last_name' => ['nullable', 'string', 'max:255'],
            'new_customer.phone' => ['nullable', 'string', 'max:30', 'phone:SA,mobile', 'unique:customers,phone'],
            'new_customer.email' => ['nullable', 'email:filter', 'regex:'.Customer::GATEWAY_EMAIL_REGEX, 'max:255'],

            'final_price' => ['nullable', 'numeric', 'min:0'],
            'transfer_number' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $customerId = $this->input('customer_id');
            $newCustomer = $this->input('new_customer', []);
            $hasAnyNew = filled($newCustomer['first_name'] ?? null)
                || filled($newCustomer['last_name'] ?? null)
                || filled($newCustomer['phone'] ?? null);

            // Neither an existing customer nor any new-customer data → one clear message.
            if (blank($customerId) && ! $hasAnyNew) {
                $validator->errors()->add('customer_id', 'يرجى اختيار عميل موجود أو إدخال بيانات عميل جديد.');

                return;
            }

            // New-customer path chosen → require its core fields with clear messages.
            if (blank($customerId)) {
                $required = [
                    'first_name' => 'الاسم الأول',
                    'last_name' => 'الاسم الأخير',
                    'phone' => 'رقم الجوال',
                ];
                foreach ($required as $field => $label) {
                    if (blank($newCustomer[$field] ?? null)) {
                        $validator->errors()->add("new_customer.{$field}", "حقل {$label} مطلوب لإنشاء عميل جديد.");
                    }
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'apartment_id.required' => 'يرجى اختيار الوحدة (الشقة).',
            'apartment_id.exists' => 'الوحدة المختارة غير صحيحة.',
            'check_in.required' => 'يرجى تحديد تاريخ الوصول.',
            'check_in.after_or_equal' => 'لا يمكن اختيار تاريخ وصول قبل اليوم.',
            'check_out.required' => 'يرجى تحديد تاريخ المغادرة.',
            'check_out.after' => 'يجب أن يكون تاريخ المغادرة بعد تاريخ الوصول.',
            'number_of_adults.required' => 'يرجى تحديد عدد البالغين.',
            'number_of_adults.min' => 'يجب أن يكون هناك بالغ واحد على الأقل.',
            'number_of_children.required' => 'يرجى تحديد عدد الأطفال.',
            'new_customer.phone.phone' => 'رقم جوال سعودي غير صحيح — يجب أن يبدأ بـ 5 ويتكوّن من 9 أرقام بعد رمز الدولة (+966).',
            'new_customer.phone.unique' => 'رقم الجوال مستخدم مسبقاً — يرجى اختيار العميل من قائمة "عميل موجود".',
            'new_customer.email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'new_customer.email.regex' => 'صيغة البريد الإلكتروني غير صحيحة (يجب أن يحتوي على نطاق صحيح).',
            'final_price.numeric' => 'السعر النهائي يجب أن يكون رقماً.',
            'receipt.image' => 'يجب أن يكون إيصال التحويل صورة.',
            'receipt.mimes' => 'صيغة الصورة غير مدعومة (jpg, jpeg, png, webp).',
            'receipt.max' => 'حجم صورة الإيصال كبير جداً (الحد الأقصى 5 ميجابايت).',
        ];
    }
}
