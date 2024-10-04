<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;

class CustomerController extends Controller
{
    public function myProfile()
    {
       $customer = auth()->user();
       $data['customer'] = new CustomerResource($customer);
       return $this->successResponse($data);
    }
}
