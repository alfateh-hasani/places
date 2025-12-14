<?php

namespace App\Exceptions\OwnerRez;

use Exception;

class BookingConflictException extends Exception
{
    protected $conflictDetails;

    public function __construct(string $message = '', array $conflictDetails = [], int $code = 409, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->conflictDetails = $conflictDetails;
    }

    public function getConflictDetails(): array
    {
        return $this->conflictDetails;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'conflict_details' => $this->conflictDetails,
            ], $this->code);
        }

        return response()->view('errors.booking-conflict', [
            'message' => $this->getMessage(),
            'details' => $this->conflictDetails,
        ], $this->code);
    }
}
