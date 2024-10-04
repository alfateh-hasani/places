<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function myProfile()
    {
       $customer = auth()->user();
       $data['customer'] = new CustomerResource($customer);
       return $this->successResponse($data);
    }




    public function logout()
    {
        $user = \Auth::guard('api')->user();
        $user->tokens()->delete();
        $massage = __('api.logout');
        return $this->successResponse([],$massage);
    }

    //update profile

    public function updateProfile(Request $request)
    {
        $customer =  \Auth::guard('api')->user();
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,'.$customer->id,
            'phone' => 'required|phone:SA|unique:customers,phone,'.$customer->id,
            'emergency_phone' => 'required|phone:SA',
            'job_title' => 'nullable|string|max:255',
            'image' => 'nullable|image',
        ]);
        $customer->update($validatedData);
        if ($request->has('image')) {
            $file = $request->file('image');
            $this->uploadFile($customer, $file, 'profile');
        }
        $massage = __('api.profile_updated');
        $data['customer'] = new CustomerResource($customer);
        return $this->successResponse($data,$massage);
    }

    public function deleteProfile()
    {
        $customer =  \Auth::guard('api')->user();
        $customer->delete();
        $massage = __('api.profile_deleted');
        return $this->successResponse([],$massage);
    }
}
