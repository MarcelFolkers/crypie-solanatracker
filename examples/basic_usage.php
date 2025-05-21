<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SolanaTracker\DataApi\Client;
use SolanaTracker\DataApi\Exception\DataApiException;
use SolanaTracker\DataApi\Exception\RateLimitException;
use SolanaTracker\DataApi\Exception\ValidationException;

// Replace with your actual API key from solanatracker.io
$apiKey = 'YOUR_API_KEY';

// Create a new client instance
$client = new Client($apiKey);

try {
    // Example 1: Get information about a specific token
    $tokenAddress = 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v'; // USDC token address
    $tokenInfo = $client->getTokenInfo($tokenAddress);
    echo "Token Info for {$tokenAddress}:\n";
    print_r($tokenInfo);
    echo "\n";

    // Example 2: Get trending tokens
    $trendingTokens = $client->getTrendingTokens('1h'); // Get trending tokens in the last hour
    echo "Trending Tokens (1h):\n";
    print_r($trendingTokens);
    echo "\n";

    // Example 3: Search for tokens
    $searchParams = [
        'query' => 'sol',
        'limit' => 5
    ];
    $searchResults = $client->searchTokens($searchParams);
    echo "Search Results for 'sol':\n";
    print_r($searchResults);
    echo "\n";

    // Example 4: Get price information for a token
    $priceInfo = $client->getPrice($tokenAddress, true); // Include price changes
    echo "Price Info for {$tokenAddress}:\n";
    print_r($priceInfo);
    echo "\n";

    // Example 5: Get multiple token prices
    $tokenAddresses = [
        'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v', // USDC
        'So11111111111111111111111111111111111111112'   // SOL
    ];
    $multiplePrices = $client->getMultiplePrices($tokenAddresses, true);
    echo "Multiple Token Prices:\n";
    print_r($multiplePrices);
    echo "\n";
} catch (ValidationException $e) {
    echo "Validation Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
} catch (RateLimitException $e) {
    echo "Rate Limit Error: " . $e->getMessage() . "\n";
    echo "Retry After: " . $e->getRetryAfter() . " seconds\n";
} catch (DataApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getCode() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
