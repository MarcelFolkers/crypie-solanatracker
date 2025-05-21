<?php

namespace SolanaTracker\DataApi\Exception;

/**
 * Exception thrown when API rate limit is exceeded
 */
class RateLimitException extends DataApiException
{
    /**
     * @var int Time in seconds to wait before retrying
     */
    private int $retryAfter;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string $errorCode API error code
     * @param int $retryAfter Time in seconds to wait before retrying
     */
    public function __construct(string $message, int $code = 429, string $errorCode = 'RATE_LIMIT_EXCEEDED', int $retryAfter = 1)
    {
        parent::__construct($message, $code, $errorCode);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the time in seconds to wait before retrying
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
