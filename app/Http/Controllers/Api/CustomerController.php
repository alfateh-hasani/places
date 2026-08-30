<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CustomerResource;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Support\Facades\Notification;
use App\Models\NotificationSeen;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Notification as CustomNotification;
use App\Models\Review;
use Carbon\Carbon;

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
            'email' => Customer::emailValidationRules($customer->id),
            // 'phone' => 'required|phone:SA|unique:customers,phone,'.$customer->id,
            'emergency_phone' => 'required|phone:SA',
            'job_title' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240',
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
            'booking_id' => 'required|exists:bookings,id',
        ]);
        $customer =  \Auth::guard('api')->user();
        if (Review::existsForBooking($customer->id, $validatedData['booking_id'])) {
            $message = __('api.review_already_exists');
            return $this->errorResponse([], $message, 400);
        }
        Review::create([
            'rating' => $validatedData['rating'],
            'review_text' => $validatedData['review_text'],
            'booking_id' => $validatedData['booking_id'],
            'apartment_id' => $validatedData['apartment_id'],
            'customer_id' => $customer->id,
        ]);
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
        $allBookings = Booking::where('customer_id', $customer->id)->where('status', '!=', 'pending')->latest()->get();
        $now = now();
        $nextCheckoutThreshold = $now;

        $pastBookings = $allBookings->filter(function ($booking) use ($nextCheckoutThreshold) {
            return Carbon::parse($booking->check_out)->setTime(12, 0, 0)->lessThanOrEqualTo($nextCheckoutThreshold);

        });

        $upcomingBookings = $allBookings->filter(function ($booking) use ($nextCheckoutThreshold) {
             return Carbon::parse($booking->check_out)->setTime(12, 0, 0)->greaterThanOrEqualTo($nextCheckoutThreshold);

        });
        $data = [
            'past_bookings' => BookingResource::collection($pastBookings->values()),
            'upcoming_bookings' =>  BookingResource::collection($upcomingBookings->values()),
        ];
        return $this->successResponse($data);
    }




    

    public function fmcToken(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'token' => 'required'
            ]);
            $customer = Customer::find($request->user()->id) ;
            $customer->update(['fcm_token'=>$request->input('token')]);
            return  $this->successResponse($customer, 'Token Updated Successfully!');
        } catch (ValidationException $e) {

            return  $this->errorResponse([], $e->getMessage());
        } catch (\Exception $e) {
            return  $this->errorResponse([], 'Token update failed. ' . $e->getMessage());
        }
    }


    public function getNotifications(Request $request)
    {
        try {
            $customer_id = 0;
            $user =  $request->user('sanctum') ;
            if($user){
                $customer_id = $user->id;
            }
            $notifications = CustomNotification::with('notification_seen')->where(function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id)
                    ->orWhere('type', 'all');
            })->orderBy('created_at','desc')->get();

            foreach ($notifications as $notification) {
                if ($notification->image) {
                    $notification->image = url('storage/' . $notification->image);
                }
            }

            $array = [];

            foreach($notifications as $one){
                $array[] = [
                    'id'=>(int) $one->id,
                    'title'=>(string) $one->{'title_'.app()->getLocale()},
                    'description'=>(string) $one->{'description_'.app()->getLocale()},
                    //'process_type'=>(string) $one->process_type,
                    //'process_status'=>(string) $one->process_status,
                    'image'      => $one->image,
                    'notification_seen'   => (isset($one->notification_seen->id)) ? true : false,
                    'date'       => $one->created_at->format('Y-m-d H:i:s'),
                ];
            }




            return  $this->successResponse($array,  __('api.notification_successfully'));
        } catch (ValidationException $e) {
            return  $this->errorResponse([], $e->getMessage(),422);
        } catch (\Exception $e) {
            return  $this->errorResponse([], 'failed ' . $e->getMessage(),500);
        }
    }

    public function getNotificationDetails(Request $request , $id)
    {
        try {

            $customer_id = 0;
            $user =  $request->user('sanctum') ;
            if($user){
                $customer_id = $user->id;
            }

            $notification = CustomNotification::where(function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id)
                    ->orWhere('type', 'all');
            })->where('id',$id)->first();

            if(!$notification){
                return  $this->errorResponse([], 'Not Found',404);
            }

            if($notification->image){
                $notification->image = url('storage/' . $notification->image);
            }

            return  $this->successResponse($notification,  __('api.notification_successfully'));
        } catch (ValidationException $e) {
            return $this->errorResponse([], $e->getMessage(),422);
        } catch (\Exception $e) {
            return $this->errorResponse([], 'failed ' . $e->getMessage(),500);
        }
    }



    public function markSeen(Request $request , $id)
    {
        try {

            $customer_id  = $request->user('sanctum')->id;
            $notification = CustomNotification::where(function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id)
                    ->orWhere('type', 'all');
            })->where('id',$id)->first();

            if(!$notification){
                return $this->errorResponse('Not Found!', 404,  []);
            }

            if(isset($notification->notification_seen->notification_id)){
                return $this->errorResponse('Already Seen!', 422,  []);
            }

            NotificationSeen::create([
                'notification_id'=>$notification->id,
                'customer_id'    =>    $request->user()->id,
            ]);

            return $this->successResponse($notification,'success');
        } catch (ValidationException $e) {
            return $this->errorResponse([], $e->getMessage() , 422);
        } catch (\Exception $e) {
            return $this->errorResponse([],   $e->getMessage() , 500);
        }
    }


    public function unreadCount(Request $request)
    {
        try {

            $customer_id = $request->user('sanctum')->id;

            $count = CustomNotification::where(function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id)
                    ->orWhere('type', 'all');
            })
                ->whereDoesntHave('notification_seen')
                ->count();

            $data['unread_notifications'] = (int) $count;

            return $this->successResponse($data ,__('api.notification_successfully'));
        } catch (ValidationException $e) {
            return$this->errorResponse('Validation error', 422, $e->validator->errors());
        } catch (\Exception $e) {
            return$this->errorResponse('failed ' . $e->getMessage(), 500);
        }
    }


    /**
     * Return bookings that have NOT yet ended (based on check_out >= now()).
     */
    public function currentBookings()
    {
        $customer = \Auth::guard('api')->user();

        // If you want “current” to mean bookings that haven’t ended:
        $current = Booking::where('customer_id', $customer->id)
            ->whereNotIn('status', ['pending', 'canceled', 'customer_canceled'])
            ->where('check_out', '>=', now())
            ->get();

        return $this->successResponse([
            'current_bookings' => BookingResource::collection($current),
        ], __('api.success'));
    }

    /**
     * Return past or previous bookings (based on check_out < now()).
     */
    public function previousBookings()
    {
        $customer = \Auth::guard('api')->user();

        $previous = Booking::where('customer_id', $customer->id)
            ->where('check_out', '<', now())
            ->orWhere(function($query) use ($customer) {
                $query->whereIn('status', ['canceled', 'customer_canceled'])
                    ->where('customer_id', $customer->id);
            })
            ->get();

        return $this->successResponse([
            'previous_bookings' => BookingResource::collection($previous),
        ], __('api.success'));
    }



}
