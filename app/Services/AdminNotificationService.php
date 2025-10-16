<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\ServiceBooking;
use App\Models\Customer;
use App\Notifications\ServiceRequestNotification;

class AdminNotificationService
{
    /**
     * Send notification to apartment supervisor when service is requested
     */
    public function sendServiceRequestNotification(ServiceBooking $serviceBooking)
    {
        try {
            // Get the apartment and its building
            $apartment = $serviceBooking->apartment;
            $building = $apartment->building;
            
            // Get the supervisor (admin) of the building
            $supervisor = $building->supervisor;
            
            if (!$supervisor) {
                \Log::warning("No supervisor found for building ID: {$building->id}");
                return false;
            }

            // Get customer information
            $customer = $serviceBooking->customer;

            // Create notification in database
            $notification = Notification::create([
                'title_ar' => 'طلب خدمة جديد',
                'title_en' => 'New Service Request',
                'description_ar' => "تم طلب خدمة {$serviceBooking->service->name_ar} من العميل {$customer->name} في العقار {$apartment->name_ar}",
                'description_en' => "Service {$serviceBooking->service->name_en} requested by customer {$customer->name} for apartment {$apartment->name_en}",
                'type' => 'admin',
                'process_type' => 'service_request',
                'process_status' => 'pending',
                'user_id' => $supervisor->id, // Admin user ID
                'booking_id' => $serviceBooking->booking_id,
                'service_booking_id' => $serviceBooking->id,
                'apartment_id' => $serviceBooking->apartment_id,
                'customer_id' => $serviceBooking->customer_id,
            ]);

            // Send notification to admin
            $supervisor->notify(new ServiceRequestNotification($serviceBooking, $customer));

            \Log::info("Service request notification sent to supervisor ID: {$supervisor->id} for service booking ID: {$serviceBooking->id}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to send service request notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all admins (if needed)
     */
    public function sendServiceRequestNotificationToAllAdmins(ServiceBooking $serviceBooking)
    {
        try {
            $apartment = $serviceBooking->apartment;
            $customer = $serviceBooking->customer;

            // Get all admin users
            $admins = User::role('admin')->get();

            foreach ($admins as $admin) {
                // Create notification in database
                $notification = Notification::create([
                    'title_ar' => 'طلب خدمة جديد',
                    'title_en' => 'New Service Request',
                    'description_ar' => "تم طلب خدمة {$serviceBooking->service->name_ar} من العميل {$customer->name} في العقار {$apartment->name_ar}",
                    'description_en' => "Service {$serviceBooking->service->name_en} requested by customer {$customer->name} for apartment {$apartment->name_en}",
                    'type' => 'admin',
                    'process_type' => 'service_request',
                    'process_status' => 'pending',
                    'user_id' => $admin->id,
                    'booking_id' => $serviceBooking->booking_id,
                    'service_booking_id' => $serviceBooking->id,
                    'apartment_id' => $serviceBooking->apartment_id,
                    'customer_id' => $serviceBooking->customer_id,
                ]);

                // Send notification to admin
                $admin->notify(new ServiceRequestNotification($serviceBooking, $customer));
            }

            \Log::info("Service request notification sent to all admins for service booking ID: {$serviceBooking->id}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to send service request notification to all admins: " . $e->getMessage());
            return false;
        }
    }
}
