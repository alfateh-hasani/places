<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{

    protected function addAppInfoToResponse(JsonResponse $response): JsonResponse
    {
        $appVersion = env('APP_VERSION', '1.0'); 
        $updateRequired  = env('UPDATE_REQUIRED', 'false');

        return $response->header('x-app-version', $appVersion)
                        ->header('x-update-required', $updateRequired);
    }


    public function successResponse($data = null, $message = 'Success', $status = 200 ): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return $this->addAppInfoToResponse(response()->json($response, $status));
    }

    public function errorResponse($errors= [] , $message = 'Error', $status = 400 ): JsonResponse
    {
        $response = [
            'success' => false,
            'errors' => $errors,
            'message' => $message,
            'data' => null,
        ];

        return $this->addAppInfoToResponse(response()->json($response, $status));
    }
}
