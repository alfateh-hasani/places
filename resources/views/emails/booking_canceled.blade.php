 
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إلغاء الحجز</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .canceled-badge {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body style="direction: rtl;" dir="rtl">
    <h1 style="text-align: center; color: #dc3545;">إلغاء الحجز</h1>
    <div style="text-align: center; margin-bottom: 20px;">
        <span class="canceled-badge">تم إلغاء الحجز رقم: {{ $booking->number_of_booking }}</span>
    </div>

    <table>
        <tr>
            <th colspan="2" style="text-align: center;">بيانات الشخص</th>
        </tr>
        <tr>
            <td>الاسم:</td>
            <td>{{ $booking->customer_full_name }}</td>
        </tr>
        <tr>
            <td>البريد الإلكتروني:</td>
            <td>{{ $booking->customer_email }}</td>
        </tr>
        <tr>
            <td>رقم الهاتف:</td>
            <td>{{ $booking->customer?->phone }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="2" style="text-align: center;">بيانات الغرفة</th>
        </tr>
        <tr>
            <td>اسم الوحدة:</td>
            <td>{{ $booking->apartment?->name_ar }}</td>
        </tr>
        <tr>
            <td>عنوان الوحدة:</td>
            <td>{{ $booking->apartment?->building?->address_ar }}</td>
        </tr>
        <tr>
            <td>المبنى:</td>
            <td>{{ $booking->apartment?->building?->name_ar }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="2" style="text-align: center;">تفاصيل الحجز الملغي</th>
        </tr>
        <tr>
            <td>رقم الحجز:</td>
            <td>{{ $booking->number_of_booking }}</td>
        </tr>
        <tr>
            <td>تاريخ الدخول:</td>
            <td>{{ $booking->check_in->format('Y-m-d') }}</td>  
        </tr>
        <tr>
            <td>وقت الدخول:</td>
            <td>{{ $booking->check_in_time->format('H:i') }}</td> 
        </tr>
        <tr>
            <td>تاريخ الخروج:</td>
            <td>{{ $booking->check_out->format('Y-m-d') }}</td>  
        </tr>
        <tr>
            <td>وقت الخروج:</td>
            <td>{{ $booking->check_out_time->format('H:i') }}</td>  
        </tr>
        <tr>
            <td>عدد الليالي:</td>
            <td>{{ $booking->number_of_nights }}</td>
        </tr>
        <tr>
            <td>المبلغ المطلوب استرداده:</td>
            <td style="color: #dc3545; font-weight: bold;">{{ number_format($booking->refund_amount, 2) }} SAR</td>
        </tr>
        <tr>
            <td>حالة الاسترداد:</td>
            <td>
                @if($booking->refund_status === 'pending')
                    <span style="color: #ffc107; font-weight: bold;">في انتظار الاسترداد</span>
                @elseif($booking->refund_status === 'approved')
                    <span style="color: #28a745; font-weight: bold;">تم الاسترداد</span>
                @elseif($booking->refund_status === 'rejected')
                    <span style="color: #dc3545; font-weight: bold;">مرفوض</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>تاريخ الإلغاء:</td>
            <td>{{ $booking->created_at->format('Y-m-d H:i') }}</td>
        </tr>
    </table>
    
    <p style="text-align: center; color: #666; margin-top: 20px;">
        يرجى مراجعة تفاصيل الحجز الملغي والمبلغ المطلوب استرداده.
    </p>
</body>
</html>

