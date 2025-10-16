<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Onboarding;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;

class OnboardingController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the onboarding items.
     */
    public function index(): JsonResponse
    {
        $onboarding = Onboarding::ordered()->get();

        $data = $onboarding->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'image' => $item->image ?: asset('assets/images/onboarding_two.png'),
            ];
        });
     
        
        return $this->successResponse($data, 'تم جلب بيانات التعريف بنجاح');
    }

     
}
