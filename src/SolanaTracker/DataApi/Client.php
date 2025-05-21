<?php

namespace SolanaTracker\DataApi;

use SolanaTracker\DataApi\Exception\DataApiException;
use SolanaTracker\DataApi\Exception\RateLimitException;
use SolanaTracker\DataApi\Exception\ValidationException;

/**
 * Client for the Solana Tracker Data API
 */
class Client
{
    /**
     * @var string API key from solanatracker.io
     */
    private string $apiKey;

    /**
     * @var string Base URL for the API
     */
    private string $baseUrl;

    /**
     * @var array Valid timeframes for various endpoints
     */
    private array $validTimeframes = ['5m', '15m', '30m', '1h', '6h', '12h', '24h'];

    /**
     * Creates a new instance of the Solana Tracker Data API client
     *
     * @param string $apiKey Your API key from solanatracker.io
     * @param string|null $baseUrl Optional base URL override
     */
    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? 'https://data.solanatracker.io';
    }

    /**
     * Makes a request to the API
     *
     * @param string $endpoint The API endpoint
     * @param array $options Additional request options
     * @return array The API response
     * @throws DataApiException|RateLimitException|ValidationException
     */
    private function request(string $endpoint, array $options = []): array
    {
        $method = $options['method'] ?? 'GET';
        $body = $options['body'] ?? null;
        $headers = $options['headers'] ?? [];
        $disableLogs = $options['disableLogs'] ?? false;

        // Set up headers
        $headers = array_merge([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ], $headers);

        // Set up cURL options
        $curl = curl_init();
        $url = "{$this->baseUrl}{$endpoint}";

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
        ];

        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            if ($body !== null) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($body);
            }
        }

        curl_setopt_array($curl, $curlOptions);

        // Execute the request
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // Handle errors
        if ($error) {
            throw new DataApiException("cURL Error: $error", 0, 'CURL_ERROR');
        }

        // Check for rate limiting
        if ($statusCode === 429) {
            $responseData = json_decode($response, true);
            $retryAfter = $responseData['retryAfter'] ?? 1;

            if (!$disableLogs) {
                error_log("Rate limit exceeded for $endpoint. Retry after: $retryAfter seconds");
            }

            throw new RateLimitException("Rate limit exceeded", 429, 'RATE_LIMIT_EXCEEDED', $retryAfter);
        }

        // Handle other errors
        if ($statusCode >= 400) {
            $responseData = json_decode($response, true);
            $message = $responseData['message'] ?? "API request failed: $statusCode";
            $code = $responseData['code'] ?? 'API_ERROR';

            if ($statusCode === 400) {
                throw new ValidationException($message, 400, 'VALIDATION_ERROR');
            }

            throw new DataApiException($message, $statusCode, $code);
        }

        // Return the response
        return json_decode($response, true);
    }

    /**
     * Format headers for cURL
     *
     * @param array $headers
     * @return array
     */
    private function formatHeaders(array $headers): array
    {
        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            $formattedHeaders[] = "$key: $value";
        }
        return $formattedHeaders;
    }

    /**
     * Validates a public key
     *
     * @param string $address
     * @param string $paramName
     * @return void
     * @throws ValidationException
     */
    private function validatePublicKey(string $address, string $paramName): void
    {
        // Basic validation - a more robust implementation would use a proper Solana public key validator
        if (!$address || !preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address)) {
            throw new ValidationException("Invalid {$paramName}: {$address}", 400, 'VALIDATION_ERROR');
        }
    }

    // ======== TOKEN ENDPOINTS ========

    /**
     * Get comprehensive information about a specific token
     *
     * @param string $tokenAddress The token's mint address
     * @return array Detailed token information
     * @throws DataApiException|ValidationException
     */
    public function getTokenInfo(string $tokenAddress): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');
        return $this->request("/tokens/{$tokenAddress}");
    }

    /**
     * Get token information by searching with a pool address
     *
     * @param string $poolAddress The pool address
     * @return array Detailed token information
     * @throws DataApiException|ValidationException
     */
    public function getTokenByPool(string $poolAddress): array
    {
        $this->validatePublicKey($poolAddress, 'poolAddress');
        return $this->request("/tokens/pool/{$poolAddress}");
    }

    /**
     * Get token holders information
     *
     * @param string $tokenAddress The token's mint address
     * @return array Information about token holders
     * @throws DataApiException|ValidationException
     */
    public function getTokenHolders(string $tokenAddress): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');
        return $this->request("/tokens/{$tokenAddress}/holders");
    }

    /**
     * Get top 20 token holders
     *
     * @param string $tokenAddress The token's mint address
     * @return array Top holders information
     * @throws DataApiException|ValidationException
     */
    public function getTopHolders(string $tokenAddress): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');
        return $this->request("/tokens/{$tokenAddress}/holders/top");
    }

    /**
     * Get the all-time high price for a token
     *
     * @param string $tokenAddress The token's mint address
     * @return array All-time high price data
     * @throws DataApiException|ValidationException
     */
    public function getTokenAth(string $tokenAddress): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');
        return $this->request("/tokens/{$tokenAddress}/ath");
    }

    /**
     * Get tokens created by a specific wallet
     *
     * @param string $wallet The deployer wallet address
     * @return array List of tokens created by the deployer
     * @throws DataApiException|ValidationException
     */
    public function getTokensByDeployer(string $wallet): array
    {
        $this->validatePublicKey($wallet, 'wallet');
        return $this->request("/deployer/{$wallet}");
    }

    /**
     * Search for tokens with flexible filtering options
     *
     * @param array $params Search parameters and filters
     * @return array Search results
     * @throws DataApiException
     */
    public function searchTokens(array $params): array
    {
        $queryParams = http_build_query($params);
        return $this->request("/search?{$queryParams}");
    }

    /**
     * Get latest tokens with pagination
     *
     * @param int $page Page number (1-10)
     * @return array List of latest tokens
     * @throws DataApiException|ValidationException
     */
    public function getLatestTokens(int $page = 1): array
    {
        if ($page < 1 || $page > 10) {
            throw new ValidationException("Page must be between 1 and 10", 400, 'VALIDATION_ERROR');
        }
        return $this->request("/tokens/latest?page={$page}");
    }

    /**
     * Get information about multiple tokens
     *
     * @param array $tokenAddresses Array of token addresses
     * @return array Information about multiple tokens
     * @throws DataApiException|ValidationException
     */
    public function getMultipleTokens(array $tokenAddresses): array
    {
        if (count($tokenAddresses) > 20) {
            throw new ValidationException("Maximum of 20 tokens per request", 400, 'VALIDATION_ERROR');
        }

        foreach ($tokenAddresses as $address) {
            $this->validatePublicKey($address, 'tokenAddress');
        }

        return $this->request("/tokens/multi", [
            'method' => 'POST',
            'body' => [
                'tokens' => $tokenAddresses
            ]
        ]);
    }

    /**
     * Get trending tokens
     *
     * @param string|null $timeframe Optional timeframe for trending calculation
     * @return array List of trending tokens
     * @throws DataApiException|ValidationException
     */
    public function getTrendingTokens(?string $timeframe = null): array
    {
        if ($timeframe !== null && !in_array($timeframe, $this->validTimeframes)) {
            throw new ValidationException(
                "Invalid timeframe. Must be one of: " . implode(', ', $this->validTimeframes),
                400,
                'VALIDATION_ERROR'
            );
        }

        $endpoint = $timeframe ? "/tokens/trending/{$timeframe}" : "/tokens/trending";
        return $this->request($endpoint);
    }

    /**
     * Get tokens sorted by volume
     *
     * @param string|null $timeframe Optional timeframe for volume calculation
     * @return array List of tokens sorted by volume
     * @throws DataApiException|ValidationException
     */
    public function getTokensByVolume(?string $timeframe = null): array
    {
        if ($timeframe !== null && !in_array($timeframe, $this->validTimeframes)) {
            throw new ValidationException(
                "Invalid timeframe. Must be one of: " . implode(', ', $this->validTimeframes),
                400,
                'VALIDATION_ERROR'
            );
        }

        $endpoint = $timeframe ? "/tokens/volume/{$timeframe}" : "/tokens/volume";
        return $this->request($endpoint);
    }

    /**
     * Get an overview of latest, graduating, and graduated tokens
     *
     * @return array Token overview
     * @throws DataApiException
     */
    public function getTokenOverview(): array
    {
        return $this->request("/tokens/overview");
    }

    /**
     * Get list of graduated tokens
     *
     * @return array List of graduated tokens
     * @throws DataApiException
     */
    public function getGraduatedTokens(): array
    {
        return $this->request("/tokens/multi/graduated");
    }

    // ======== PRICE ENDPOINTS ========

    /**
     * Get price information for a token
     *
     * @param string $tokenAddress The token's mint address
     * @param bool $priceChanges Include price change percentages
     * @return array Price data
     * @throws DataApiException|ValidationException
     */
    public function getPrice(string $tokenAddress, bool $priceChanges = false): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');

        return $this->request("/price", [
            'method' => 'POST',
            'body' => [
                'token' => $tokenAddress,
                'priceChanges' => $priceChanges
            ]
        ]);
    }

    /**
     * Get price at a specific timestamp
     *
     * @param string $tokenAddress The token's mint address
     * @param int $timestamp Unix timestamp
     * @return array Price at the specified timestamp
     * @throws DataApiException|ValidationException
     */
    public function getPriceAtTimestamp(string $tokenAddress, int $timestamp): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');
        return $this->request("/price/history/timestamp?token={$tokenAddress}&timestamp={$timestamp}");
    }

    /**
     * Get lowest and highest price in a time range
     *
     * @param string $tokenAddress The token's mint address
     * @param int $timeFrom Start time (unix timestamp)
     * @param int $timeTo End time (unix timestamp)
     * @return array Price range data
     * @throws DataApiException|ValidationException
     */
    public function getPriceRange(string $tokenAddress, int $timeFrom, int $timeTo): array
    {
        $this->validatePublicKey($tokenAddress, 'tokenAddress');
        return $this->request("/price/history/range?token={$tokenAddress}&time_from={$timeFrom}&time_to={$timeTo}");
    }

    /**
     * Get price information for multiple tokens
     *
     * @param array $tokenAddresses Array of token addresses
     * @param bool $priceChanges Include price change percentages
     * @return array Price data for multiple tokens
     * @throws DataApiException|ValidationException
     */
    public function getMultiplePrices(array $tokenAddresses, bool $priceChanges = false): array
    {
        if (count($tokenAddresses) > 100) {
            throw new ValidationException("Maximum of 100 tokens per request", 400, 'VALIDATION_ERROR');
        }

        foreach ($tokenAddresses as $address) {
            $this->validatePublicKey($address, 'tokenAddress');
        }

        $query = $priceChanges ? 'priceChanges=true' : '';
        return $this->request("/price/multi?tokens=" . implode(',', $tokenAddresses) . "&{$query}");
    }

    /**
     * Get price information for multiple tokens using POST
     *
     * @param array $tokenAddresses Array of token addresses
     * @param bool $priceChanges Include price change percentages
     * @return array Price data for multiple tokens
     * @throws DataApiException|ValidationException
     */
    public function postMultiplePrices(array $tokenAddresses, bool $priceChanges = false): array
    {
        if (count($tokenAddresses) > 100) {
            throw new ValidationException("Maximum of 100 tokens per request", 400, 'VALIDATION_ERROR');
        }

        foreach ($tokenAddresses as $address) {
            $this->validatePublicKey($address, 'tokenAddress');
        }

        return $this->request("/price/multi", [
            'method' => 'POST',
            'body' => [
                'tokens' => $tokenAddresses,
                'priceChanges' => $priceChanges
            ]
        ]);
    }

    // ======== WALLET ENDPOINTS ========

    /**
     * Get basic wallet information
     *
     * @param string $owner Wallet address
     * @return array Basic wallet data
     * @throws DataApiException|ValidationException
     */
    public function getWallet(string $owner): array
    {
        $this->validatePublicKey($owner, 'owner');
        return $this->request("/wallet/{$owner}");
    }

    /**
     * Get wallet token holdings
     *
     * @param string $owner Wallet address
     * @return array Wallet token holdings
     * @throws DataApiException|ValidationException
     */
    public function getWalletTokens(string $owner): array
    {
        $this->validatePublicKey($owner, 'owner');
        return $this->request("/wallet/{$owner}/tokens");
    }

    /**
     * Get wallet trades
     *
     * @param string $owner Wallet address
     * @param int $limit Number of trades to return (max 100)
     * @param int|null $before Timestamp to get trades before
     * @return array Wallet trades
     * @throws DataApiException|ValidationException
     */
    public function getWalletTrades(string $owner, int $limit = 20, ?int $before = null): array
    {
        $this->validatePublicKey($owner, 'owner');

        if ($limit < 1 || $limit > 100) {
            throw new ValidationException("Limit must be between 1 and 100", 400, 'VALIDATION_ERROR');
        }

        $query = "limit={$limit}";
        if ($before !== null) {
            $query .= "&before={$before}";
        }

        return $this->request("/wallet/{$owner}/trades?{$query}");
    }
}
