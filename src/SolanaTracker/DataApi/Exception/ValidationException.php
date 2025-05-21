<?php

namespace SolanaTracker\DataApi\Exception;

/**
 * Exception thrown when input validation fails
 */
class ValidationException extends DataApiException
{
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string $errorCode API error code
     */
    public function __construct(string $message, int $code = 400, string $errorCode = 'VALIDATION_ERROR')
    {
        parent::__construct($message, $code, $errorCode);
    }
}
