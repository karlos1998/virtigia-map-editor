<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling external API calls related to attribute points
 *
 * This service encapsulates all communication with external APIs for:
 * - Fetching available attribute points
 * - Calculating scaled attributes based on item parameters
 */
final class ApiAttributePointService
{
    /*
    |--------------------------------------------------------------------------
    | External API Integration
    |--------------------------------------------------------------------------
    |
    | Methods for communicating with external attribute scaling API
    |
    */

    /**
     * Fetch available attribute points from external API
     *
     * Returns a list of all available attribute points that can be assigned
     * to items, including both regular and manual attribute categories.
     *
     * @return object The attribute points data from external API
     * @throws \Exception When API call fails or returns invalid data
     */
    public function getAttributePoints(): object
    {
        try {
            $apiUrl = config('services.margatron_api.base_url') . '/attribute-points';
            $timeout = config('services.margatron_api.timeout', 30);

            $response = Http::timeout($timeout)->get($apiUrl);

            if ($response->failed()) {
                throw new \Exception(
                    "External API returned error status: {$response->status()}"
                );
            }

            return (object)$response->json();

        } catch (\Exception $e) {
            Log::error('Failed to fetch attribute points from external API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Error fetching attribute points: ' . $e->getMessage());
        }
    }

    /**
     * Calculate scaled item attributes based on parameters
     *
     * Sends item parameters and attribute point allocations to external API
     * to calculate final scaled attribute values for the item.
     *
     * @param array $parameters Query parameters including:
     *                          - lvl: Item level requirement
     *                          - itemCategory: Item category (armors, weapons, etc.)
     *                          - rarity: Item rarity (common, unique, etc.)
     *                          - itemProfessions: Required professions (comma-separated)
     *                          - Various attribute point values (armor, criticalChance, etc.)
     *
     * @return object The scaled attribute values from external API
     * @throws \Exception When API call fails or returns invalid data
     */
    public function getScaleAttributes(array $parameters = []): object
    {
        try {
            // Sanitize parameters to ensure proper format
            $sanitizedParameters = $this->sanitizeApiParameters($parameters);

            // Build the API request URL
            $apiUrl = $this->buildApiUrl('scale-attributes', $sanitizedParameters);

            // Log the request for debugging
            $this->logApiRequest($apiUrl, $sanitizedParameters);

            // Make the API call
            $response = Http::timeout(config('services.margatron_api.timeout', 30))->get($apiUrl);

            // Handle the response
            return $this->handleApiResponse($response, 'scale-attributes', $sanitizedParameters);

        } catch (\Exception $e) {
            Log::error('Failed to fetch scale attributes from external API', [
                'parameters' => $parameters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Error fetching scale attributes: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helper Methods for API Integration
    |--------------------------------------------------------------------------
    */

    /**
     * Sanitize API parameters to ensure proper formatting
     *
     * @param array $parameters Raw parameters from request
     * @return array Sanitized parameters ready for API call
     */
    private function sanitizeApiParameters(array $parameters): array
    {
        $sanitized = [];

        foreach ($parameters as $key => $value) {
            // Convert null values to empty strings for API compatibility
            if ($value === null) {
                $sanitized[$key] = '';
            } else {
                $sanitized[$key] = $value;
            }
        }

        // Ensure itemProfessions always exists as empty string if not set
        if (!array_key_exists('itemProfessions', $sanitized)) {
            $sanitized['itemProfessions'] = '';
        }

        return $sanitized;
    }

    /**
     * Build complete API URL with query parameters
     *
     * @param string $endpoint The API endpoint (without base URL)
     * @param array $parameters Query parameters
     * @return string Complete URL with query string
     */
    private function buildApiUrl(string $endpoint, array $parameters): string
    {
        $baseUrl = config('services.margatron_api.base_url') . "/{$endpoint}";

        // Manually build query string to preserve empty values
        $queryParts = [];
        foreach ($parameters as $key => $value) {
            $queryParts[] = $key . '=' . urlencode((string)$value);
        }

        $queryString = implode('&', $queryParts);

        return $baseUrl . '?' . $queryString;
    }

    /**
     * Log API request details for debugging
     *
     * @param string $url Complete API URL
     * @param array $parameters Request parameters
     * @return void
     */
    private function logApiRequest(string $url, array $parameters): void
    {
        Log::info('External API request initiated', [
            'url' => $url,
            'parameters' => $parameters
        ]);
    }

    /**
     * Handle and validate API response
     *
     * @param \Illuminate\Http\Client\Response $response HTTP response object
     * @param string $endpoint API endpoint name for logging
     * @param array $parameters Original request parameters for logging
     * @return object Validated response data
     * @throws \Exception When response is invalid
     */
    private function handleApiResponse($response, string $endpoint, array $parameters): object
    {
        // Log response details
        Log::info("External API response for {$endpoint}", [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        // Check for API errors
        if ($response->failed()) {
            Log::error("External API call failed for {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
                'parameters' => $parameters
            ]);

            throw new \Exception(
                "External API returned error status: {$response->status()} - {$response->body()}"
            );
        }

        // Validate and return response data
        $data = $response->json();

        if ($data === null) {
            throw new \Exception('External API returned invalid JSON response');
        }

        return (object)$data;
    }
}
