<?php

namespace App\Exceptions\OwnerRez;

use Exception;

class OwnerRezApiException extends Exception
{
    protected $endpoint;

    protected $responseData;

    protected $statusCode;

    public function __construct(
        string $message = '',
        string $endpoint = '',
        array $responseData = [],
        int $statusCode = 500,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->endpoint = $endpoint;
        $this->responseData = $responseData;
        $this->statusCode = $statusCode;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'endpoint' => $this->endpoint,
            ], 500);
        }

        return parent::render($request);
    }
}
