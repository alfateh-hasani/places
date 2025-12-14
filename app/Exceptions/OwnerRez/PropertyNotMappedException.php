<?php

namespace App\Exceptions\OwnerRez;

use Exception;

class PropertyNotMappedException extends Exception
{
    protected $apartmentId;

    public function __construct(int $apartmentId, string $message = '', int $code = 404, ?Exception $previous = null)
    {
        $message = $message ?: "Property with apartment_id {$apartmentId} is not mapped to OwnerRez";
        parent::__construct($message, $code, $previous);
        $this->apartmentId = $apartmentId;
    }

    public function getApartmentId(): int
    {
        return $this->apartmentId;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'apartment_id' => $this->apartmentId,
            ], $this->code);
        }

        return parent::render($request);
    }
}
