<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
class PushNotificationService
{

    protected $notification;
    public function __construct()
    {
        $this->notification = Firebase::messaging();
    }

    public function send(Notification $notification) {
        if($notification->type == 'all'){
            return $this->push($notification);
        }

        if($notification->type == 'customer'){
            return $this->pushDevice($notification);
        }
    }

    private function pushDevice(Notification $notification){

        $user = Customer::where('id',$notification->customer_id)->first();
        if(!$user){
            return;
        }

        $token = $user->fcm_token ;
        if(!$token){
            return ;
        }




        $notificationArray = [];
        $notificationArray['title'] = $notification->title_ar;
        $notificationArray['body']  = $notification->description_ar;
        $notificationArray['title_ar'] = $notification->title_ar;
        $notificationArray['title_en'] = $notification->title_en;
        $notificationArray['description_ar'] = $notification->description_ar;
        $notificationArray['description_en'] = $notification->description_en;
        $notificationArray['process_type'] = $notification->process_type;
        $notificationArray['process_status'] = $notification->process_status;
        $notificationArray['date'] = $notification->created_at?->format('Y-m-d H:i:s');
        if($notification->image){
            $notificationArray['image'] = url('storage/uploads/app/notifications/'.$notification->image);
        }else{
            $notificationArray['image'] = null;
        }



        $message = CloudMessage::fromArray([
            'token' => $token,
            'notification' =>$notificationArray,
            'data' =>$notificationArray,
        ]);

        $re =  $this->notification->send($message);
        // dd($re);

    }


    private function push(Notification $notification){

        $notificationArray = [];
        $notificationArray['title'] = $notification->title_ar;
        $notificationArray['body']  = $notification->description_ar;
        $notificationArray['title_ar'] = $notification->title_ar;
        $notificationArray['title_en'] = $notification->title_en;
        $notificationArray['description_ar'] = $notification->description_ar;
        $notificationArray['description_en'] = $notification->description_en;
        $notificationArray['process_type'] = $notification->process_type;
        $notificationArray['process_status'] = $notification->process_status;

        $notificationArray['date'] = $notification->created_at?->format('Y-m-d H:i:s');
        if($notification->image){
            $notificationArray['image'] = url('storage/uploads/app/notifications/'.$notification->image);
        }else{
            $notificationArray['image'] = null;
        }

        $message = CloudMessage::fromArray([
            'topic' => 'all',
            'notification' =>$notificationArray,
            'data' =>$notificationArray,
        ]);

        $re =  $this->notification->send($message);
        //dd($re);

    }


}
