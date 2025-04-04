<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Interfaces\LeatPrepaidRepositoryInterface;
use Leat\Domain\Services\ApiService;
use Leat\Utils\Logger;
use Piggy\Api\Models\Prepaid\PrepaidTransaction;
use Piggy\Api\Exceptions\PiggyRequestException;

/**
 * Class LeatPrepaidRepository
 *
 * Leat-specific implementation for prepaid transaction data access.
 *
 * @package Leat\Infrastructure\Repositories
 */
class LeatPrepaidRepository implements LeatPrepaidRepositoryInterface
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Leat API service instance.
     *
     * @var ApiService
     */
    private ApiService $apiService;

    /**
     * Constructor.
     *
     * @param ApiService $apiService The Leat API service instance.
     */
    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->logger = new Logger();
    }

    /**
     * Create prepaid transaction for a contact.
     *
     * @param string $contact_uuid The contact's UUID.
     * @param int $amount_in_cents The amount in cents.
     * @param string $shop_uuid The shop UUID.
     * @return PrepaidTransaction|null The created transaction or null on failure.
     */
    public function create_transaction(string $contact_uuid, int $amount_in_cents, string $shop_uuid): ?PrepaidTransaction
    {
        try {
            $client = $this->apiService->init_client();
            if (! $client) {
                $this->logger->error("Failed to initialize API client for creating prepaid transaction.");
                return null;
            }

            $this->logger->info("Creating prepaid transaction", [
                'contact_uuid' => $contact_uuid,
                'amount_in_cents' => $amount_in_cents,
                'shop_uuid' => $shop_uuid
            ]);

            // Use the SDK's static create method
            $transaction = PrepaidTransaction::create([
                'contact_uuid' => $contact_uuid,
                'amount_in_cents' => $amount_in_cents,
                'shop_uuid' => $shop_uuid,
            ]);

            $this->logger->info("Prepaid transaction created successfully", ['transaction_uuid' => $transaction->getUuid()]);
            return $transaction;
        } catch (PiggyRequestException $e) {
            // Log specific Piggy exceptions
            $this->logger->error("Piggy API request error creating prepaid transaction: " . $e->getMessage(), [
                'response_body' => $e->getResponseBody(), // Linter might complain, but method likely exists
                'status_code' => $e->getStatusCode(),
            ]);
            $this->apiService->log_exception($e, 'Error creating prepaid transaction');
            return null;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error creating prepaid transaction');
            return null;
        }
    }

    /**
     * Reverse a prepaid transaction.
     *
     * @param string $transaction_uuid The UUID of the transaction to reverse.
     * @return PrepaidTransaction|null The new reversal transaction or null on failure.
     */
    public function reverse_transaction(string $transaction_uuid): ?PrepaidTransaction
    {
        try {
            $client = $this->apiService->init_client();
            if (! $client) {
                $this->logger->error("Failed to initialize API client for reversing prepaid transaction.");
                return null;
            }

            $this->logger->info("Reversing prepaid transaction", ['transaction_uuid' => $transaction_uuid]);

            // Construct the API endpoint
            $endpoint = "/prepaid-transactions/{$transaction_uuid}/reverse";

            // Use the initialized client's POST method
            // Assuming the client has a method like `post(endpoint, body)` and returns decoded JSON array
            $response = $client->post($endpoint); // Send POST request

            if ($response && isset($response['data']) && is_array($response['data'])) {
                // Manually create a PrepaidTransaction object from the response data
                // Need to ensure constructor parameters match the actual SDK class
                $reversedTransaction = new PrepaidTransaction(
                    $response['data']['uuid'] ?? null,
                    $response['data']['amount_in_cents'] ?? null,
                    $response['data']['prepaid_balance'] ?? null, // This structure might differ slightly
                    $response['data']['created_at'] ?? null,
                    $response['data']['shop'] ?? null,
                    $response['data']['contact_identifier'] ?? null
                    // Potentially add contact details if returned and needed by constructor
                    // $response['data']['contact'] ?? null
                );

                // Check if UUID was successfully populated
                if ($reversedTransaction->getUuid()) {
                    $this->logger->info("Prepaid transaction reversed successfully via API call", [
                        'original_transaction_uuid' => $transaction_uuid,
                        'reversal_transaction_uuid' => $reversedTransaction->getUuid()
                    ]);
                    return $reversedTransaction;
                } else {
                    $errorMessage = "Failed to reverse prepaid transaction: Could not instantiate transaction from API response.";
                    $context = ['transaction_uuid' => $transaction_uuid, 'response' => $response];
                    $this->logger->error($errorMessage, $context);
                    return null;
                }
            } else {
                $errorMessage = "Failed to reverse prepaid transaction via API call. Invalid or empty response.";
                $context = ['transaction_uuid' => $transaction_uuid, 'response' => $response];
                $this->logger->error($errorMessage, $context);
                return null;
            }
        } catch (PiggyRequestException $e) {
            // Log specific Piggy exceptions
            $this->logger->error("Piggy API request error reversing prepaid transaction: " . $e->getMessage(), [
                'transaction_uuid' => $transaction_uuid,
                'response_body' => $e->getResponseBody(), // Linter might complain, but method likely exists
                'status_code' => $e->getStatusCode(),
            ]);
            $this->apiService->log_exception($e, 'Error reversing prepaid transaction');
            return null;
        } catch (\Exception $e) {
            $this->apiService->log_exception($e, 'Error reversing prepaid transaction');
            return null;
        }
    }
}
