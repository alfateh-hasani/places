<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CustomerResource;
use App\Models\Booking;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function myProfile()
    {
       $customer = \Auth::guard('api')->user();
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
            // 'phone' => 'required|phone:SA|unique:customers,phone,'.$customer->id,
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

    //Add review

    public function addReview(Request $request)
    {
        $validatedData = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string',
            'apartment_id' => 'required|exists:apartments,id',
        ]);
        $customer =  \Auth::guard('api')->user();
        $existingReview = $customer->reviews()->where('apartment_id', $validatedData['apartment_id'])->first();

        if ($existingReview) {
            $message = __('api.review_already_exists');
            return $this->errorResponse([], $message, 400);
        }
        $customer->reviews()->create($validatedData);
        $massage = __('api.review_added');
        return $this->successResponse([],$massage);
    }

    //myFavorite
    public function myFavorite(Request $request)
    {
        $customer =  \Auth::guard('api')->user();
        $apartments = $customer->favoriteApartments()->get();
        $data['apartments'] = ApartmentResource::collection($apartments);
        return $this->successResponse($data);
    }

    //add favorite

    public function addFavorite(Request $request)
    {
        $validatedData = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
        ]);
        $customer =  \Auth::guard('api')->user();
        $customer->favoriteApartments()->syncWithoutDetaching($validatedData['apartment_id']);
        $massage = __('api.favorite_added');
        return $this->successResponse([],$massage);
    }


    //remove favorite

    public function removeFavorite(Request $request)
    {
        $validatedData = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
        ]);
        $customer =  \Auth::guard('api')->user();
        $customer->favoriteApartments()->detach($validatedData['apartment_id']);
        $massage = __('api.favorite_removed');
        return $this->successResponse([],$massage);
    }

    //getAllBookings

    public function getAllBookings()
    {
        $customer = \Auth::guard('api')->user();
        $allBookings = Booking::where('customer_id', $customer->id)->get();
        $pastBookings = $allBookings->filter(function ($booking) {
            return $booking->check_out < now();
        });
        $upcomingBookings = $allBookings->filter(function ($booking) {
            return $booking->check_out >= now();
        });
        $data = [
            'past_bookings' => BookingResource::collection($pastBookings->values()),
            'upcoming_bookings' =>  BookingResource::collection($upcomingBookings->values()),
        ];
        return $this->successResponse($data);
    }




}
