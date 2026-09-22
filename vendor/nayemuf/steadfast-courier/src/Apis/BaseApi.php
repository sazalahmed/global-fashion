<?php

namespace Nayemuf\SteadfastCourier\Apis;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Nayemuf\SteadfastCourier\Exceptions\SteadfastException;

abstract class BaseApi
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * Rate limiting: max requests per minute
     */
    protected const RATE_LIMIT_PER_MINUTE = 60;

    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'steadfast_courier_';

    /**
     * BaseApi constructor.
     *
     * @param string $apiKey
     * @param string $secretKey
     * @param string|null $baseUrl
     */
    public function __construct(
        string $apiKey,
        string $secretKey,
        ?string $baseUrl = null
    ) {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        // Store base URL without trailing slash
        $this->baseUrl = rtrim($baseUrl ?? 'https://portal.packzy.com/api/v1', '/');

        // Use base URI without path - we'll construct full URLs manually
        $baseUri = parse_url($this->baseUrl, PHP_URL_SCHEME) . '://' . parse_url($this->baseUrl, PHP_URL_HOST);
        $port = parse_url($this->baseUrl, PHP_URL_PORT);
        if ($port) {
            $baseUri .= ':' . $port;
        }

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 30,
            'http_errors' => false, // We'll handle errors manually
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Check and enforce rate limiting
     *
     * @param string $endpoint
     * @return void
     * @throws SteadfastException
     */
    protected function checkRateLimit(string $endpoint): void
    {
        $cacheKey = self::CACHE_PREFIX . 'rate_limit_' . md5($endpoint);
        $currentMinute = now()->format('Y-m-d-H-i');
        $minuteKey = $cacheKey . '_' . $currentMinute;

        $requestCount = Cache::get($minuteKey, 0);

        if ($requestCount >= self::RATE_LIMIT_PER_MINUTE) {
            throw new SteadfastException('Rate limit exceeded. Maximum ' . self::RATE_LIMIT_PER_MINUTE . ' requests per minute.');
        }

        // Increment counter (expires in 2 minutes to be safe)
        Cache::put($minuteKey, $requestCount + 1, now()->addMinutes(2));
    }

    /**
     * Make API request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws SteadfastException
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        // Check rate limit
        $this->checkRateLimit($endpoint);

        $options = [
            'headers' => [
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ],
        ];

        if (!empty($data)) {
            // Ensure cod_amount is properly formatted as float in JSON
            $jsonPayload = json_encode($data, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
            $options['body'] = $jsonPayload;
        }

        try {
            // Construct full path including base URL path
            $endpoint = '/' . ltrim($endpoint, '/');
            $basePath = parse_url($this->baseUrl, PHP_URL_PATH);
            $fullPath = rtrim($basePath, '/') . $endpoint;
            $fullUrl = $this->baseUrl . $endpoint;
            
            Log::debug('SteadFast API Request Details', [
                'method' => $method,
                'endpoint' => $endpoint,
                'base_path' => $basePath,
                'full_path' => $fullPath,
                'base_url' => $this->baseUrl,
                'full_url' => $fullUrl,
            ]);
            
            // Use the full path (including /api/v1) as the endpoint
            $response = $this->client->request($method, $fullPath, $options);
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);
            
            // Check for error status codes (since http_errors is false, we need to check manually)
            if ($statusCode >= 400) {
                $body = $responseBody ?? [];
                
                // Build a more descriptive error message
                $message = $body['message'] ?? '';
                
                if (empty($message)) {
                    // Provide default messages based on status code
                    switch ($statusCode) {
                        case 404:
                            $message = "Endpoint not found at: {$fullUrl}. Please verify the API endpoint path is correct according to SteadFast API documentation.";
                            break;
                        case 401:
                            $message = "Unauthorized. Please check your API Key and Secret Key.";
                            break;
                        case 403:
                            $message = "Forbidden. Your API credentials may not have permission for this operation.";
                            break;
                        case 422:
                            $message = "Validation error. Please check your request data.";
                            break;
                        case 500:
                            $message = "Server error on SteadFast's side. Please try again later or contact support.";
                            break;
                        default:
                            $message = "API request failed with status code {$statusCode}.";
                    }
                }
                
                Log::error('SteadFast API Error', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'status' => $statusCode,
                    'message' => $message,
                    'response' => $body,
                    'full_url' => $fullUrl,
                    'base_url' => $this->baseUrl,
                ]);

                throw new SteadfastException($message, $statusCode, $body['errors'] ?? []);
            }
            
            // Log successful requests
            Log::debug('SteadFast API Request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'full_url' => $fullUrl,
            ]);
            
            return $responseBody ?? [];
        } catch (SteadfastException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (GuzzleException $e) {
            Log::error('SteadFast Network Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new SteadfastException('Network error: ' . $e->getMessage(), $e->getCode());
        }
    }
}

