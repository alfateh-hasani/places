<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\OwnerRez\SyncPropertyBookingsJob;
use App\Models\Booking;
use App\Models\OwnerRezPropertyMapping;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $validated = $request->validate([
            'apartment_id' => 'required|integer|exists:apartments,id',
        ]);

        $bookings = Booking::where('apartment_id', $validated['apartment_id'])
            ->select('id', 'number_of_booking', 'check_in', 'check_out', 'is_airbnb_booking', 'customer_full_name', 'customer_email', 'total_price', 'status')
            ->orderBy('check_in')
            ->get();

        $events = $bookings->map(function (Booking $booking) {
            $sourceColor = $booking->is_airbnb_booking ? '#FF5733' : '#2ECC71';

            $statusColors = [
                'pending' => '#FFC107',
                'approved' => '#28A745',
                'rejected' => '#DC3545',
                'booked' => '#007BFF',
            ];
            $statusColor = $statusColors[$booking->status] ?? '#6C757D';
            $finalColor = "linear-gradient(135deg, {$sourceColor} 50%, {$statusColor} 50%)";

            return [
                'id' => $booking->id,
                'title' => "Booking #{$booking->number_of_booking}",
                'start' => $booking->check_in?->format('Y-m-d'),
                'end' => $booking->check_out?->format('Y-m-d'),
                'backgroundColor' => $finalColor,
                'borderColor' => $sourceColor,
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'booking',
                    'customer_name' => $booking->customer_full_name,
                    'customer_email' => $booking->customer_email,
                    'total_price' => $booking->total_price,
                    'status' => ucfirst((string) $booking->status),
                    'source' => $booking->is_airbnb_booking ? 'Airbnb' : 'Website',
                ],
            ];
        })->values();

        return $this->successResponse([
            'apartment_id' => $validated['apartment_id'],
            'events' => $events,
        ], 'Apartment calendar bookings fetched successfully');
    }

    public function test2(Request $request)
    {
        $apartmentId = $request->input('apartment_id', $request->input('apartment-id'));

        if ($apartmentId) {
            $request->merge(['apartment_id' => $apartmentId]);
            $validated = $request->validate([
                'apartment_id' => 'required|integer|exists:apartments,id',
            ]);

            $mapping = OwnerRezPropertyMapping::where('apartment_id', $validated['apartment_id'])->first();

            if (! $mapping) {
                return $this->errorResponse([
                    'apartment_id' => $validated['apartment_id'],
                ], "OwnerRez property mapping not found for apartment: {$validated['apartment_id']}", 404);
            }

            SyncPropertyBookingsJob::dispatch($mapping->id);

            return $this->successResponse([
                'mode' => 'single',
                'apartment_id' => $mapping->apartment_id,
                'mapping_id' => $mapping->id,
                'ownerrez_property_id' => $mapping->ownerrez_property_id,
                'ownerrez_property_name' => $mapping->ownerrez_property_name,
                'job' => SyncPropertyBookingsJob::class,
                'dispatched' => true,
            ], 'Sync job dispatched');
        }

        $mappings = OwnerRezPropertyMapping::where('sync_enabled', true)->get();

        $dispatched = [];

        foreach ($mappings as $mapping) {
            SyncPropertyBookingsJob::dispatch($mapping->id);
            $dispatched[] = [
                'apartment_id' => $mapping->apartment_id,
                'mapping_id' => $mapping->id,
                'ownerrez_property_id' => $mapping->ownerrez_property_id,
                'ownerrez_property_name' => $mapping->ownerrez_property_name,
            ];
        }

        return $this->successResponse([
            'mode' => 'all_enabled',
            'total' => $mappings->count(),
            'job' => SyncPropertyBookingsJob::class,
            'dispatched' => $dispatched,
        ], "Syncing bookings for {$mappings->count()} properties");
    }
}
