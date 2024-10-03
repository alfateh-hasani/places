<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function successResponse($data = null, $message = 'Success', $status = 200 ): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $status );
    }

    public function errorResponse($errors= [] , $message = 'Error', $status = 400 ): JsonResponse
    {
        $response = [
            'success' => false,
            'errors' => $errors,
            'message' => $message,
            'data' => null,
        ];

        return response()->json($response, $status );
    }
}
