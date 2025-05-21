# Solana Tracker Data API SDK for PHP

A PHP client for the [Solana Tracker Data API](https://github.com/solanatracker/data-api-sdk). This SDK provides a simple and intuitive way to interact with the Solana Tracker Data API from your PHP applications.

## Features

- Complete coverage of all Solana Tracker Data API endpoints
- Proper error handling with specific exception types
- Input validation for API parameters
- Comprehensive documentation and examples
- PHP 7.4+ compatibility with type hints

## Installation

### Requirements

- PHP 7.4 or higher
- Composer

### Via Composer

```bash
composer require crypie/solanatracker-api-sdk
```

## Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use SolanaTracker\DataApi\Client;
use SolanaTracker\DataApi\Exception\DataApiException;

// Initialize the client with your API key
$apiKey = 'YOUR_API_KEY';
$client = new Client($apiKey);

try {
    // Get information about a specific token
    $tokenInfo = $client->getTokenInfo('EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v');
    print_r($tokenInfo);
    
    // Get trending tokens in the last hour
    $trendingTokens = $client->getTrendingTokens('1h');
    print_r($trendingTokens);
    
} catch (DataApiException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getCode() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
}
```

## Available Methods

### Token Endpoints

- `getTokenInfo(string $tokenAddress): array` - Get comprehensive information about a specific token
- `getTokenByPool(string $poolAddress): array` - Get token information by searching with a pool address
- `getTokenHolders(string $tokenAddress): array` - Get token holders information
- `getTopHolders(string $tokenAddress): array` - Get top 20 token holders
- `getTokenAth(string $tokenAddress): array` - Get the all-time high price for a token
- `getTokensByDeployer(string $wallet): array` - Get tokens created by a specific wallet
- `searchTokens(array $params): array` - Search for tokens with flexible filtering options
- `getLatestTokens(int $page = 1): array` - Get latest tokens with pagination
- `getMultipleTokens(array $tokenAddresses): array` - Get information about multiple tokens
- `getTrendingTokens(?string $timeframe = null): array` - Get trending tokens
- `getTokensByVolume(?string $timeframe = null): array` - Get tokens sorted by volume
- `getTokenOverview(): array` - Get an overview of latest, graduating, and graduated tokens
- `getGraduatedTokens(): array` - Get list of graduated tokens

### Price Endpoints

- `getPrice(string $tokenAddress, bool $priceChanges = false): array` - Get price information for a token
- `getPriceAtTimestamp(string $tokenAddress, int $timestamp): array` - Get price at a specific timestamp
- `getPriceRange(string $tokenAddress, int $timeFrom, int $timeTo): array` - Get lowest and highest price in a time range
- `getMultiplePrices(array $tokenAddresses, bool $priceChanges = false): array` - Get price information for multiple tokens
- `postMultiplePrices(array $tokenAddresses, bool $priceChanges = false): array` - Get price information for multiple tokens using POST

### Wallet Endpoints

- `getWallet(string $owner): array` - Get basic wallet information
- `getWalletTokens(string $owner): array` - Get wallet token holdings
- `getWalletTrades(string $owner, int $limit = 20, ?int $before = null): array` - Get wallet trades

## Error Handling

The SDK provides specific exception types for different error scenarios:

- `DataApiException` - Base exception for all API errors
- `ValidationException` - Thrown when input validation fails
- `RateLimitException` - Thrown when API rate limit is exceeded

Example of handling different exception types:

```php
try {
    $tokenInfo = $client->getTokenInfo($tokenAddress);
    // Process the response...
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation Error: " . $e->getMessage() . "\n";
} catch (RateLimitException $e) {
    // Handle rate limit errors
    echo "Rate Limit Error: " . $e->getMessage() . "\n";
    echo "Retry After: " . $e->getRetryAfter() . " seconds\n";
} catch (DataApiException $e) {
    // Handle other API errors
    echo "API Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getCode() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
}
```

## Advanced Configuration

You can customize the base URL for the API if needed:

```php
// Use a custom base URL
$client = new Client($apiKey, 'https://custom-api-url.example.com');
```

## Examples

Check the `examples` directory for more usage examples.

## License

MIT
