<?php

namespace App\Exceptions\OwnerRez;

use Exception;

class AuthenticationException extends Exception
{
    public function __construct(string $message = 'OwnerRez authentication failed', int $code = 401, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
            ], $this->code);
        }

        return parent::render($request);
    }
}
