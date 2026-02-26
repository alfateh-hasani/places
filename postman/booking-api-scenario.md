# سيناريو الحجز عبر API (تفصيلي)

هذا الملف يشرح رحلة الحجز في التطبيق كما هي مطبقة فعليًا في الكود (API + الدفع + تأكيد الحجز).

## 1) المتطلبات العامة

### Base URL

- جميع المسارات أدناه تكون تحت:
- `/api`

مثال:

```text
https://your-domain.com/api/...
```

### Headers المطلوبة

#### لكل طلبات API داخل مجموعة `appSecret`

- `Accept: application/json`
- `Content-Type: application/json`
- `x-secret-key: <API_SECRET_KEY>`
- `x-language: ar` أو `en` (اختياري لكن مهم للرسائل والحقول المترجمة)

#### للطلبات المحمية (بعد تسجيل الدخول)

- `Authorization: Bearer <sanctum_token>`

### شكل الاستجابة العام (في أغلب endpoints)

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

وفي الخطأ غالبًا:

```json
{
  "success": false,
  "errors": [],
  "message": "Error",
  "data": null
}
```

### ملاحظات مهمة على الاستجابات

- بعض أخطاء التحقق (`validation`) قد ترجع بصيغة Laravel الافتراضية `422` وليس نفس الـ wrapper أعلاه.
- عند غياب `x-secret-key` أو خطئه: الاستجابة تكون مباشرة:

```json
{
  "error": "Unauthorized"
}
```

- يتم إرجاع هيدرز إضافية مع الاستجابة:
  - `x-app-version`
  - `x-update-required`

## 2) ملخص رحلة الحجز (End-to-End)

1. طلب OTP برقم الجوال
2. التحقق من OTP
3. تسجيل مستخدم جديد (إذا لزم)
4. جلب الشقة/تفاصيلها
5. حساب السعر (مع/بدون كوبون)
6. التحقق النهائي من إمكانية الحجز + أوقات الدخول/الخروج + وسائل الدفع
7. إنشاء الحجز (`pending`) وإنشاء معاملة الدفع
8. فتح رابط الدفع (`callback`) داخل WebView/Browser
9. مزود الدفع يستدعي callback السيرفر
10. النظام يحدّث الحجز إلى `approved` و`paid` عند نجاح الدفع
11. التطبيق يؤكد الحالة عبر `get-booking` أو `get-booking-via-customer`

## 3) خطوات السيناريو بالتفصيل

## 3.1 طلب OTP

### Endpoint

- `POST /api/otp/request`

### Request body

```json
{
  "phone": "05XXXXXXXX"
}
```

### Response (مثال)

```json
{
  "success": true,
  "message": "OTP_SENT",
  "data": {
    "has_account": true
  }
}
```

`has_account` يحدد هل المستخدم موجود مسبقًا أم سيحتاج تسجيل بعد التحقق.

## 3.2 التحقق من OTP

### Endpoint

- `POST /api/otp/verify`

### Request body

```json
{
  "phone": "05XXXXXXXX",
  "otp": "1234",
  "fcm_token": "optional-device-token"
}
```

### حالتان مهمتان

#### أ) المستخدم موجود

ترجع الاستجابة:

- `data.customer`
- `data.token` (Sanctum Bearer Token)
- `data.register_required = false`

#### ب) المستخدم غير موجود

ترجع الاستجابة:

- `data.token` (توكن مؤقت للتسجيل فقط - ليس Bearer)
- `data.register_required = true`

## 3.3 تسجيل مستخدم جديد (إذا `register_required = true`)

### Endpoint

- `POST /api/customer/register`

### Request body

```json
{
  "token": "temporary_token_from_verify_otp",
  "first_name": "Ahmad",
  "last_name": "Ali",
  "email": "ahmad@example.com",
  "fcm_token": "optional-device-token"
}
```

### Response

ترجع:

- `data.customer`
- `data.token` (Sanctum Bearer Token النهائي)

## 3.4 استكشاف الشقق واختيار شقة

### خيارات شائعة قبل الحجز

- `POST /api/home/get-filter-apartments` (فلترة)
- `GET /api/home/get-apartment?id={id_or_slug}` (تفاصيل شقة)

مهم من تفاصيل الشقة:

- `id`
- `adults_count`
- `children_count`
- `check_in_time`
- `check_out_time`
- `booked_days` (منع اختيار تواريخ محجوزة)

## 3.5 حساب السعر (اختياري لكنه مهم جدًا)

### بدون كوبون

- `GET /api/booking/calculate-price-withOut-coupon`

#### Query params

- `apartment_id`
- `check_in`
- `check_out`

### مع كوبون

- `GET /api/booking/calculate-price-with-coupon`

#### Query params

- `apartment_id`
- `coupon_code`
- `check_in`
- `check_out`

### مثال استجابة

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "total_price": "1150.00",
    "discount": "100.00",
    "final_price": "1050.00",
    "vat": "136.96",
    "price_breakdown": []
  }
}
```

## 3.6 التحقق النهائي قبل إنشاء الحجز (موصى به جدًا)

### Endpoint

- `POST /api/booking/determine-booking`

### Request body

مهم: أسماء الحقول هنا تختلف عن `add-booking`.

```json
{
  "apartment_id": 12,
  "check_in": "2026-03-15",
  "check_out": "2026-03-18",
  "number_of_adults": 2,
  "number_of_children": 1
}
```

### ماذا يفعل هذا endpoint؟

- يتحقق من التوفر (محليًا)
- قد يتحقق من التوفر الخارجي (OwnerRez) إذا كان مفعّلًا
- يتحقق من سعة الضيوف للشقة
- يحسب السعر
- يرجع:
  - عدد الليالي
  - السعر/الإجمالي/الضريبة
  - سياسة الحجز
  - أوقات الدخول والخروج
  - `payment_details` (وسائل الدفع المتاحة)

### جزء مهم من الاستجابة

```json
{
  "success": true,
  "data": {
    "number_of_nights": 3,
    "total_price": "1150.00",
    "discount": "0.00",
    "final_price": "1150.00",
    "vat": "150.00",
    "check_in_time": "03:00 PM",
    "check_out_time": "12:00 PM",
    "payment_details": [
      {
        "name": "الدفع بالبطاقة",
        "icon": "https://your-domain/icons/geidea.png",
        "value": "geidea"
      }
    ]
  }
}
```

## 3.7 إنشاء الحجز وبدء الدفع

### Endpoint

- `POST /api/booking/add-booking`

### Headers

- أضف `Authorization: Bearer <token>`

### Request body

مهم: أسماء الحقول هنا تختلف عن `determine-booking`.

```json
{
  "apartment_id": 12,
  "check_in": "2026-03-15",
  "check_out": "2026-03-18",
  "adults_count": 2,
  "children_count": 1,
  "coupon_code": "RAMADAN10",
  "notes": "Late arrival",
  "booking_source": "web",
  "payment_method_code": "geidea"
}
```

### ماذا يحدث داخليًا؟

1. التحقق من المدخلات
2. جلب الشقة
3. التحقق من التوفر
4. حساب السعر النهائي (مع/بدون كوبون)
5. إنشاء `transaction`
6. إنشاء `booking` بحالة:
   - `status = pending`
   - `payment_status = pending`
7. إنشاء جلسة دفع عند مزود الدفع
8. إرجاع رابط الدفع

### Response المتوقعة

```json
{
  "success": true,
  "message": "transaction url",
  "data": {
    "callback": "https://payment-gateway/.../checkout",
    "booking_id": 987
  }
}
```

### ماذا يفعل التطبيق بعد ذلك؟

- يفتح `data.callback` داخل WebView أو متصفح داخلي.
- يحتفظ بـ `booking_id` لاستخدامه في متابعة الحالة لاحقًا.

## 3.8 تدفق الدفع (Payment Flow)

### نقاط مهمة

- التطبيق لا ينهي الحجز مباشرة بعد `add-booking`.
- الحجز يبقى `pending` حتى يصل callback ناجح من مزود الدفع.

### Callback (يستدعيه مزود الدفع)

- `GET /api/payment-methods/{code}/callback/{transaction_id}`

مثال:

- `/api/payment-methods/geidea/callback/12345`

### عند نجاح الدفع (داخليًا)

النظام يقوم بـ:

- تحديث الحجز:
  - `status = approved`
  - `payment_status = paid`
  - `payment_method_code = geidea`
- إرسال إيميل تفاصيل الحجز
- إعادة توجيه المستخدم إلى:
  - `GET /api/payment-methods/success/{booking_id}/{booking_number}`

### عند فشل الدفع

- إعادة توجيه إلى:
  - `GET /api/payment-methods/failed`

ملاحظة:

- في الكود الحالي، عند فشل الدفع يتم تحديث حالة `transaction` فقط إلى `failed`.
- الحجز نفسه غالبًا يبقى `pending` حتى يتم حذفه عبر `cancel-booking-payment/{id}` أو عبر التنظيف الدوري.

## 3.9 كيف يؤكد التطبيق نجاح الحجز بعد الدفع؟

الأفضل ألا يعتمد فقط على صفحة success/failed، بل يؤكد عبر API الحجز:

### خيار 1 (مفضل): جلب حجز محدد

- `GET /api/booking/get-booking?booking_id={id}`

يرجع `BookingResource` وفيه:

- `status`
- `status_title`
- `final_price`
- `number_of_booking`
- `can_cancel`
- بيانات الشقة/المدينة

### خيار 2: جلب جميع حجوزات المستخدم

- `GET /api/booking/get-booking-via-customer`

أو من customer endpoints:

- `GET /api/customer/get-all-bookings`

## 3.10 إلغاء الحجز قبل الدفع (إلغاء Pending)

إذا خرج المستخدم من صفحة الدفع أو لم يُكمل:

### Endpoint

- `POST /api/booking/cancel-booking-payment/{id}`

### الشرط

- الحجز يجب أن يكون:
  - يخص نفس المستخدم
  - `status = pending`

### النتيجة

- حذف الحجز (Delete)

## 3.11 إلغاء الحجز بعد الدفع (Refund Workflow)

### Endpoint

- `POST /api/booking/cancel-booking`

### Request body

```json
{
  "booking_id": 987
}
```

### شروط الإلغاء (منطق النظام)

- الحجز يجب أن يكون:
  - `status = approved`
  - `payment_status = paid`
- مسموح الإلغاء فقط قبل عدد ساعات محدد من وقت الدخول
  - القيمة تُقرأ من الإعداد `cancel_before_hours` (افتراضيًا 24 ساعة)

### عند نجاح طلب الإلغاء

يتم تحديث الحجز إلى:

- `status = customer_canceled`
- `refund_status = pending`
- `refund_amount = final_price`

ويرجع `BookingResource` محدثًا.

## 3.12 خدمات إضافية بعد الحجز (اختياري)

### جلب الخدمات

- `GET /api/booking/get-services`

### إضافة خدمات لحجز قائم

- `POST /api/booking/add-services-to-booking`

### Request body

```json
{
  "booking_id": 987,
  "services": [1, 3, 5]
}
```

الشرط:

- الحجز لنفس العميل
- تاريخ الخروج لم ينته بعد

## 3.13 معلومات الدخول للشقة (بعد بدء مدة الحجز)

### Endpoint

- `GET /api/booking/login-apartment?booking_id={id}`

يرجع عادة:

- `unit_number`
- `floor_number`
- `passcode`
- `lock_alias`

ملاحظات:

- لن يعمل إلا إذا الحجز لنفس المستخدم ولم ينته بعد.
- كلمة المرور تعتمد على وجود passcode فعال على القفل الذكي.

## 4) مثال سيناريو كامل مختصر (عملي)

1. `POST /api/otp/request`
2. `POST /api/otp/verify`
3. إذا `register_required=true`:
   - `POST /api/customer/register`
4. `GET /api/home/get-apartment?id=12`
5. `POST /api/booking/determine-booking`
6. `POST /api/booking/add-booking`
7. افتح `data.callback` للدفع
8. بعد الرجوع من WebView:
   - `GET /api/booking/get-booking?booking_id={booking_id}`
9. تحقق أن:
   - `status = approved`
   - (ملاحظة) `BookingResource` الحالي لا يعرض `payment_status` بشكل مباشر

## 5) فروقات مهمة جدًا يجب مراعاتها في التكامل

### 1) أسماء الحقول تختلف بين endpoint التحقق وendpoint إنشاء الحجز

- `determine-booking`:
  - `number_of_adults`
  - `number_of_children`

- `add-booking`:
  - `adults_count`
  - `children_count`

### 2) لا تعتمد على `add-booking` كنجاح نهائي للحجز

- `add-booking` = إنشاء حجز pending + إنشاء رابط دفع فقط.
- النجاح النهائي بعد callback الدفع ثم إعادة التحقق من الحجز.

### 3) وسيلة الدفع تُؤخذ من `payment_details`

- استخدم القيمة `value` (مثل `geidea`) داخل `payment_method_code`.

### 4) يفضّل إعادة الاستعلام عن الحجز بعد success redirect

- لأن redirect success مجرد نقطة توجيه، والأضمن هو قراءة حالة الحجز من `get-booking`.

### 5) ملاحظة تقنية موجودة في الكود الحالي

- التحقق من `booking_source` داخل `add-booking` يبدو مقيدًا فعليًا بـ `web` بسبب صياغة rule الحالية.
- إن كنتم سترسلون `android` أو `ios` فقد تحتاجون تعديل التحقق في الكود.
- صفحة success الخاصة بالدفع لا يُفضّل الاعتماد على `booking_number` فيها فقط، والأضمن دائمًا إعادة الاستعلام عن الحجز عبر `get-booking`.

## 6) أمثلة cURL سريعة

## طلب التحقق النهائي قبل الحجز

```bash
curl -X POST "https://your-domain.com/api/booking/determine-booking" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "x-secret-key: YOUR_SECRET" \
  -H "x-language: ar" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "apartment_id": 12,
    "check_in": "2026-03-15",
    "check_out": "2026-03-18",
    "number_of_adults": 2,
    "number_of_children": 1
  }'
```

## إنشاء الحجز

```bash
curl -X POST "https://your-domain.com/api/booking/add-booking" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "x-secret-key: YOUR_SECRET" \
  -H "x-language: ar" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "apartment_id": 12,
    "check_in": "2026-03-15",
    "check_out": "2026-03-18",
    "adults_count": 2,
    "children_count": 1,
    "payment_method_code": "geidea"
  }'
```

## تأكيد حالة الحجز بعد الدفع

```bash
curl -X GET "https://your-domain.com/api/booking/get-booking?booking_id=987" \
  -H "Accept: application/json" \
  -H "x-secret-key: YOUR_SECRET" \
  -H "x-language: ar" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 7) مخرجات متوقعة للحالة (State Transitions)

- بعد `add-booking`:
  - `status = pending`
  - `payment_status = pending`

- بعد callback دفع ناجح:
  - `status = approved`
  - `payment_status = paid`

- بعد إلغاء العميل (إذا مسموح):
  - `status = customer_canceled`
  - `refund_status = pending`
