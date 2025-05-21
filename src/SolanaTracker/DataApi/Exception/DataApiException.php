<?php

namespace SolanaTracker\DataApi\Exception;

/**
 * Base exception for Solana Tracker Data API errors
 */
class DataApiException extends \Exception
{
    /**
     * @var string Error code
     */
    protected string $errorCode;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string $errorCode API error code
     */
    public function __construct(string $message, int $code = 0, string $errorCode = 'API_ERROR')
    {
        parent::__construct($message, $code);
        $this->errorCode = $errorCode;
    }

    /**
     * Get the API error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
