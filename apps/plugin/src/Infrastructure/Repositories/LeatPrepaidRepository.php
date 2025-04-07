<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Interfaces\LeatPrepaidRepositoryInterface;
use Leat\Domain\Services\ApiService;
use Leat\Utils\Logger;
use Piggy\Api\ApiClient;
use Piggy\Api\Models\Prepaid\PrepaidTransaction;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Prepaid\PrepaidBalance;
use DateTime;

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
                $this->logger->error("Failed to initialize API client for creating prepaid transaction.",);
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
            $errorBag = $e->getErrorBag();
            $this->logger->error("Piggy API request error creating prepaid transaction: " . $e->getMessage(), [
                'error_details' => $errorBag ? $errorBag->all() : 'No details available',
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

            $endpoint = "/api/v3/oauth/clients/prepaid-transactions/{$transaction_uuid}/reverse";
            $response = ApiClient::post($endpoint, []);

            if ($response) {
                $response_data = $response->getData();

                if (is_object($response_data)) {
                    try {
                        // Extract data from stdClass object
                        $uuid = $response_data->uuid ?? null;
                        $amountInCents = $response_data->amount_in_cents ?? null;
                        $balanceInCents = $response_data->prepaid_balance->balance_in_cents ?? null;
                        $createdAtString = $response_data->created_at ?? null;

                        if ($uuid === null || $amountInCents === null || $balanceInCents === null || $createdAtString === null) {
                            throw new \Exception("Missing required fields in API response for reversing transaction.");
                        }

                        $prepaidBalance = new PrepaidBalance((int)$balanceInCents);
                        $createdAt = new DateTime($createdAtString);

                        $reversedTransaction = new PrepaidTransaction(
                            (int)$amountInCents,
                            $prepaidBalance,
                            (string)$uuid,
                            $createdAt
                        );
                    } catch (\Exception $instantiationError) {
                        $errorMessage = "Failed to instantiate PrepaidTransaction from API response: " . $instantiationError->getMessage();
                        $context = ['transaction_uuid' => $transaction_uuid, 'response' => $response];
                        $this->logger->error($errorMessage, $context);
                        return null;
                    }

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
            } else {
                $errorMessage = "Failed to reverse prepaid transaction via API call. Invalid or empty response.";
                $context = ['transaction_uuid' => $transaction_uuid, 'response' => $response];
                $this->logger->error($errorMessage, $context);
                return null;
            }
        } catch (PiggyRequestException $e) {
            // Log specific Piggy exceptions
            $errorBag = $e->getErrorBag();
            $this->logger->error("Piggy API request error reversing prepaid transaction: " . $e->getMessage(), [
                'transaction_uuid' => $transaction_uuid,
                'error_details' => $errorBag ? $errorBag->all() : 'No details available',
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
