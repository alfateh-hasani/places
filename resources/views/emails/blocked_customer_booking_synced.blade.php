<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز وارد من قناة خارجية لعميل محظور</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f9f9f9; color: #333; }
        .wrapper { width: 100%; background-color: #f9f9f9; padding: 20px 0; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 0 16px; box-sizing: border-box; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,.1); }
        table.info th, table.info td { padding: 12px; text-align: right; border-bottom: 1px solid #ddd; word-break: break-word; }
        table.info th { background-color: #f2f2f2; font-weight: bold; }
        .badge { display: inline-block; background-color: #dc3545; color: #fff; padding: 8px 14px; border-radius: 5px; font-weight: bold; white-space: nowrap; font-size: 14px; }
        .btn { display: inline-block; background-color: #3498db; color: #fff !important; text-decoration: none; padding: 8px 14px; border-radius: 5px; font-weight: bold; white-space: nowrap; font-size: 14px; }

        @media only screen and (max-width: 480px) {
            .container { padding: 0 10px; }
            h1 { font-size: 19px !important; }
            .badge, .btn { padding: 7px 9px !important; font-size: 12px !important; }
            table.info th, table.info td { padding: 8px !important; font-size: 13px !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container" style="direction: rtl;" dir="rtl">
            <h1 style="text-align:center; color:#dc3545; font-size:24px;">حجز وارد من قناة خارجية لعميل محظور — يحتاج مراجعة</h1>

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 20px; border-collapse:collapse; box-shadow:none; background:transparent; table-layout:fixed;">
                <tr>
                    <td width="50%" align="center" valign="middle" style="border:0; padding:4px;">
                        <span class="badge">حجز رقم: {{ $booking->number_of_booking }}</span>
                    </td>
                    <td width="50%" align="center" valign="middle" style="border:0; padding:4px;">
                        <a class="btn" href="{{ route('booking.show', $booking->id) }}" target="_blank" rel="noopener">
                            عرض تفاصيل الحجز
                        </a>
                    </td>
                </tr>
                @if (! empty($booking->ownerrez_booking_id))
                    <tr>
                        <td colspan="2" align="center" valign="middle" style="border:0; padding:4px;">
                            <a class="btn" style="background-color:#6f42c1;" href="{{ rtrim(config('ownerrez.app_url'), '/') }}/bookings/{{ $booking->ownerrez_booking_id }}" target="_blank" rel="noopener">
                                فتح في OwnerRez — رقم {{ $booking->ownerrez_booking_id }}
                            </a>
                        </td>
                    </tr>
                @endif
            </table>

            <p style="text-align:center; color:#666;">
                هذا الحجز تمت مزامنته تلقائياً من OwnerRez ({{ $booking->booking_source }}) ولم يُرفض — الحجوزات الواردة
                تُزامَن دائماً بغض النظر عن حالة الحظر لتبقى بيانات التوفر لدينا مطابقة لتقويم OwnerRez. هذه رسالة
                مراجعة فقط، لا حاجة لإجراء آلي.
            </p>

            <table class="info">
                <tr><th colspan="2" style="text-align:center;">العميل</th></tr>
                <tr><td>الاسم:</td><td>{{ $customer->full_name }}</td></tr>
                <tr><td>الهاتف:</td><td><bdo dir="ltr">{{ $customer->phone }}</bdo></td></tr>
                <tr><td>سبب الحظر:</td><td>{{ $customer->block_reason ?: '—' }}</td></tr>
                <tr><td>تاريخ الحظر:</td><td>{{ $customer->blocked_at?->format('Y-m-d H:i') }}</td></tr>
            </table>

            <table class="info">
                <tr><th colspan="2" style="text-align:center;">الحجز</th></tr>
                <tr><td>الوحدة:</td><td>{{ $booking->apartment?->name_ar }}</td></tr>
                <tr><td>التواريخ:</td><td><bdo dir="ltr">{{ $booking->check_out }} ← {{ $booking->check_in }}</bdo></td></tr>
                <tr><td>مصدر الحجز:</td><td>{{ $booking->booking_source }}</td></tr>
            </table>

            <p style="text-align:center; color:#666; margin-top:20px;">
                راجع الحجز من لوحة الإدارة عند الحاجة. لا يوجد إلغاء آلي عبر API — أي إجراء على الحجز نفسه يكون يدوياً من واجهة OwnerRez.
            </p>
        </div>
    </div>
</body>
</html>
