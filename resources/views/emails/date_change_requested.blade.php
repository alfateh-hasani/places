<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب تعديل تواريخ الحجز</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; color: #333; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,.1); }
        th, td { padding: 12px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .badge { background-color: #3498db; color: #fff; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body style="direction: rtl;" dir="rtl">
    <h1 style="text-align:center; color:#3498db;">طلب تعديل تواريخ الحجز</h1>
    <div style="text-align:center; margin-bottom:20px;">
        <span class="badge">حجز رقم: {{ $booking->number_of_booking }}</span>
    </div>

    <table>
        <tr><th colspan="2" style="text-align:center;">العميل والوحدة</th></tr>
        <tr><td>الاسم:</td><td>{{ $booking->customer_full_name }}</td></tr>
        <tr><td>الوحدة:</td><td>{{ $booking->apartment?->name_ar }}</td></tr>
    </table>

    <table>
        <tr><th colspan="2" style="text-align:center;">التغيير المطلوب</th></tr>
        <tr><td>التواريخ الحالية:</td><td>{{ $request->original_check_in->format('Y-m-d') }} → {{ $request->original_check_out->format('Y-m-d') }}</td></tr>
        <tr><td>التواريخ الجديدة:</td><td>{{ $request->new_check_in->format('Y-m-d') }} → {{ $request->new_check_out->format('Y-m-d') }}</td></tr>
        <tr><td>السعر الحالي:</td><td>{{ number_format((float) $request->original_price, 2) }} SAR</td></tr>
        <tr><td>السعر الجديد:</td><td>{{ number_format((float) $request->new_price, 2) }} SAR</td></tr>
        <tr>
            <td>الفرق (يُسترد للعميل):</td>
            <td style="color:#28a745; font-weight:bold;">{{ number_format(abs((float) $request->price_delta), 2) }} SAR</td>
        </tr>
    </table>

    <p style="text-align:center; color:#666; margin-top:20px;">
        يرجى مراجعة الطلب من لوحة الإدارة: «طلبات تعديل التواريخ»، ثم الموافقة (تطبيق + استرداد الفرق) أو الرفض.
    </p>
</body>
</html>
