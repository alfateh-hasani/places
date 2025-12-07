<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GuestyService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GuestyController extends Controller
{
    public function __construct(private GuestyService $guestyService) {}

    public function token(Request $request): JsonResponse
    {
        try {
            $payload = $this->guestyService->authenticate($request->boolean('refresh'));

            return response()->json($payload);
        } catch (Throwable $exception) {
            return $this->handleGuestyFailure($exception);
        }
    }

    public function listings(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'limit',
                'skip',
                'cursor',
                'sort',
                'query',
                'status',
                'fields',
            ]);

            $listings = $this->guestyService->listings($filters);

            return response()->json($listings);
        } catch (Throwable $exception) {
            return $this->handleGuestyFailure($exception);
        }
    }

    public function reservations(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'limit',
                'skip',
                'cursor',
                'sort',
                'query',
                'from',
                'to',
                'listingId',
                'status',
            ]);

            $reservations = $this->guestyService->reservations($filters);

            return response()->json($reservations);
        } catch (Throwable $exception) {
            return $this->handleGuestyFailure($exception);
        }
    }

    public function showReservation(string $reservationId, Request $request): JsonResponse
    {
        try {
            $fields = $request->only('fields');
            $reservation = $this->guestyService->reservation($reservationId, $fields);

            return response()->json($reservation);
        } catch (Throwable $exception) {
            return $this->handleGuestyFailure($exception);
        }
    }

    private function handleGuestyFailure(Throwable $exception): JsonResponse
    {
        report($exception);

        if ($exception instanceof RequestException) {
            $status = $exception->response?->status() ?? Response::HTTP_BAD_GATEWAY;
            $errorPayload = $exception->response?->json();

            return response()->json([
                'message' => 'Guesty API request failed.',
                'error' => $errorPayload ?? $exception->getMessage(),
            ], $status);
        }

        return response()->json([
            'message' => 'Unable to communicate with Guesty.',
        ], Response::HTTP_BAD_GATEWAY);
    }
}
