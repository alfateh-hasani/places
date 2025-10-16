<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\PaymentMethods\TabbyPayment;
use App\Services\PaymentMethods\TapPayment;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Support\Facades\Auth;

class ProcessPaymentService
{
    //tap , tamara , cardit , for calass

    //protected $customer;

    // public function __construct(Customer $customer)
    // {
    //     $this->customer = $customer;
    // }

    protected function getMethod($method)
    {

        switch ($method) {
            case 'tabby':
                return (new TabbyPayment());
            case 'tap':
                return (new TapPayment());
            case 'geidea':
                return (new GeideaPayment());
                //tap
                //tamara
                //cardit
                //for calass
            default:
        }
    }

    public function processPayment($data, $method)
    {

        $paymentMethod = $this->getMethod($method);

        return $paymentMethod->process($data);
    }

    //add transactions
    public function addTransaction($data, $price, $customer, $platform = 'web')
    {

        $apartment_data = [
            'apartment_id' => $data['apartment_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'coupon_code' => $data['coupon_code'] ?? null,
            'adults_count' => $data['adults_count'],
            'children_count' => $data['children_count'],
            'payment_method_code' => $data['payment_method_code'],
            'total_price' => $price['total_price'],
            'discount' => $price['discount'],
            'final_price' => $price['final_price'],
            'vat' => $price['vat'] ?? 0,
            'booking_source' => \Request()->header('BookingSource') ?? 'web',
        ];
        return Transaction::create([
            'customer_id' => $customer->id,
            'apartment_id' => $data['apartment_id'],
            'booking_data' =>  json_encode($apartment_data),
            'transaction_reference' => time() .  uniqid(),
            'amount' => (float) $price['final_price'], // السعر مع الضريبة
            'currency' => 'SAR',
            'status' => 'pending',
            'type' => 'deposit',
            'payment_gateway' => $data['payment_method_code'],
            'payment_gateway_response' => null,
            'platform' => $platform
        ]);
    }

    public function addTransactionV2(Booking $booking, $payment_gateway)
    {
        // استخراج بيانات الشقة من الحجز
        $apartmentData = [
            'apartment_id'        => $booking->apartment_id,
            'check_in'            => $booking->check_in,
            'check_out'           => $booking->check_out,
            'coupon_code'         => $booking->coupon_code,
            'adults_count'        => $booking->adults_count,
            'children_count'      => $booking->children_count,
            'payment_method_code' => $booking->payment_method_code,
            'total_price'         => $booking->total_price,
            'discount'            => $booking->discount,
            'final_price'         => $booking->final_price,
            'vat'                 => $booking->tax ?? 0,
            'booking_source'      => $booking->booking_source ?? request()->header('BookingSource') ?? 'web',
            'booking_id'          => $booking->id
        ];

        $transaction = Transaction::create([
            'customer_id'            => $booking->customer_id,
            'apartment_id'           => $booking->apartment_id,
            'booking_id'             => $booking->id,
            'booking_data'           => json_encode($apartmentData),
            'transaction_reference'  => time() .  uniqid(),
            'amount'                 => (float) $booking->final_price, // السعر النهائي بعد الخصم (شامل الضريبة)
            'currency'               => 'SAR',
            'status'                 => 'pending',
            'type'                   => 'deposit',
            'payment_gateway'        => $payment_gateway,
            'payment_gateway_response' => null,
            'platform'               => $booking->booking_source ?? 'web',
        ]);
        $booking->update([
            'transaction_id' => $transaction->id
        ]);
        return $transaction;
    }


    public function handleCallBack($method, $data)
    {
        $paymentMethod = $this->getMethod($method);

        $response = $paymentMethod->handlePayment($data);

        return $response;
    }
}
