 
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الحجز</title>
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
        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .map-link {
            color: #007bff;
            text-decoration: none;
        }
        .map-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body style="direction: rtl;" dir="rtl">
    <h1 style="text-align: center;">تفاصيل الحجز</h1>
    <table>
        <tr>
            <th colspan="2" style="text-align: center;">بيانات الشخص</th>
        </tr>
        <tr>
            <td>الاسم:</td>
            <td>{{ $reservation->customer_full_name }}</td>
        </tr>
        <tr>
            <td>البريد الإلكتروني:</td>
            <td>{{ $reservation->customer_email }}</td>
        </tr>
        <tr>
            <td>رقم الهاتف:</td>
            <td>{{ $reservation->customer?->phone }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="2" style="text-align: center;">بيانات الغرفة</th>
        </tr>
        @if($reservation->apartment?->hasMedia('image'))
            <tr>
                <td>صور الغرفة:</td>
                <td>
                    <img src="{{$reservation->apartment->getFirstMediaUrl('image')}}" alt="صورة الغرفة" style="max-width: 200px;">
                </td>
            </tr>
        @endif
      
        <tr>
            <td>اسم الوحدة:</td>
            <td>{{ $reservation->apartment?->name_ar }}</td>
        </tr>
        <tr>
            <td>عنوان الوحدة:</td>
            <td>{{ $reservation->apartment?->building?->address_ar }}</td>
        </tr>
        <tr>
            <td>رابط الوحدة على الخريطة:</td>
            <td>
                <a href="{{ $reservation->apartment?->building?->map }}" class="map-link" target="_blank">
                    اضغط هنا للعرض على الخريطة
                </a>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="2" style="text-align: center;">تفاصيل الحجز</th>
        </tr>
        <tr>
            <td>تاريخ الدخول:</td>
            <td>{{ $reservation->check_in->format('Y-m-d') }}</td>  
        </tr>
        <tr>
            <td>وقت الدخول:</td>
            <td>{{ $reservation->check_in_time->format('H:i') }}</td> 
        </tr>
        <tr>
            <td>تاريخ الخروج:</td>
            <td>{{ $reservation->check_out->format('Y-m-d') }}</td>  
        </tr>
        <tr>
            <td>وقت الخروج:</td>
            <td>{{ $reservation->check_out_time->format('H:i') }}</td>  
        </tr>
        <tr>
            <td>عدد الليالي:</td>
            <td>{{ $reservation->number_of_nights }}</td>
        </tr>
        <tr>
            <td>المبلغ المدفوع:</td>
            <td>{{ $reservation->total_price }}</td>
        </tr>
    </table>
</body>
</html>