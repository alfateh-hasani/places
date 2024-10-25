<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\NotificationSeen;
use App\Otp\CustomerRegistrationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use SadiqSalau\LaravelOtp\Facades\Otp;

class AuthController extends Controller
{
    public function requestOtp(Request $request)
    {
        try {
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
            ]);
            $validatedData = $request->validate([
                'phone'=> 'required|phone:SA',
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse([], $e->getMessage());
        }
        $customer = Customer::where('phone', $request->phone)->first();
        try {
            $otp = Otp::identifier('otp_'.$request->phone)->send(
                new CustomerRegistrationOtp(
                    phone: $request->phone),
                Notification::route('sms', $request->phone)
            );
            Log::info('OTP Requested', ['otp' => $otp]);
            if($otp['status'] == Otp::OTP_SENT){
                $data = [
                    'has_account' => (bool)$customer
                ];
                return $this->successResponse($data, trans($otp['status']), 200);
            }
            return $this->errorResponse([], trans('api.error_happened'));
        } catch (\Throwable $th) {
            return $this->errorResponse([], $th->getMessage(), 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
                'otp' => convertArabicNumbers($request->otp),
            ]);
            $customer = Customer::where('phone', $request->phone)->first();
            $rules = [
                'phone' => 'required|phone:SA',
                'otp'   => 'required|digits:4',
            ];
            if (!$customer) {
                $rules['first_name'] = 'required|string|max:255';
                $rules['last_name'] = 'required|string|max:255';
                $rules['email'] = 'required|email|max:255|unique:customers';
                $rules['phone'] = 'required|phone:SA|unique:customers';
            }

            $validatedData = $request->validate($rules);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), trans('api.validation_exception'));
        }
        try {
            $otp = Otp::identifier('otp_'.$request->phone)->attempt($request->otp);
            Log::info('OTP Verified', ['otp' => $otp]);
            if ($otp['status'] == Otp::OTP_MISMATCHED || $otp['status'] == Otp::OTP_EMPTY) {
                return $this->errorResponse([], trans($otp['status']));
            }

            if ($otp['status'] == Otp::OTP_PROCESSED) {
                if (!$customer) {
                    $customer = Customer::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                        'phone' => $request->phone,
                    ]);
                }
                $data['customer'] = new CustomerResource($customer);
                $data['token'] =$customer->createToken('Places_APP')->plainTextToken;
                return $this->successResponse($data, trans($otp['status']), 200);
            }

            return $this->errorResponse([], trans('api.error_happened'));
        } catch (\Throwable $th) {
            return $this->errorResponse([], $th->getMessage(), 500);
        }
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
            $notifications = Notification::with('notification_seen')->where(function ($query) use ($customer_id) {
                $query->where('user_id', $customer_id)
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
                    'title'=>(string) $one->title,
                    'description'=>(string) $one->description,
                    'process_type'=>(string) $one->process_type,
                    'process_status'=>(string) $one->process_status,
                    'image'      => $one->image,
                    'notification_seen'      => (isset($one->notification_seen->id)) ? true : false,
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

            $notification = Notification::where(function ($query) use ($customer_id) {
                $query->where('user_id', $customer_id)
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
            $notification = Notification::where(function ($query) use ($customer_id) {
                $query->where('user_id', $customer_id)
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
                'user_id'    =>    $request->user()->id,
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

            $count = Notification::where(function ($query) use ($customer_id) {
                $query->where('user_id', $customer_id)
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


}
