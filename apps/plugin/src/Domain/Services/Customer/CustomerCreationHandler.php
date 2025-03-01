<?php

namespace Leat\Domain\Services\Customer;

use Leat\Api\Connection;
use Leat\Domain\Services\EarnRules;
use Leat\Utils\Logger;

/**
 * Handles the creation and setup of new customers in the system.
 *
 * This class manages the customer creation process, including contact creation,
 * user attribute synchronization, and initial account credit allocation.
 *

 */
class CustomerCreationHandler
{
    /**
     * API connection instance.
     *

     * @var Connection
     */
    private $connection;

    /**
     * Earn rules service instance.
     *

     * @var EarnRules
     */
    private $earn_rules;

    /**
     * Logger instance.
     *

     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *

     *
     * @param Connection $connection API connection instance.
     * @param EarnRules  $earn_rules Earn rules service instance.
     * @param Logger     $logger     Logger instance.
     */
    public function __construct(
        Connection $connection,
        EarnRules $earn_rules,
        Logger $logger
    ) {
        $this->connection = $connection;
        $this->earn_rules = $earn_rules;
        $this->logger = $logger;
    }

    /**
     * Handles the creation of a new customer.
     *
     * Creates a contact, syncs user attributes, and processes initial account credits
     * for newly created customers.
     *

     *
     * @param int   $wp_user_id       WordPress user ID.
     * @param array $new_customer_data Customer data including user_email.
     * @return void
     */
    public function handle_customer_creation($wp_user_id, $new_customer_data): void
    {
        $this->logger->info("Handling customer creation for user ID: $wp_user_id");

        $client = $this->connection->init_client();

        if (!$client) {
            return;
        }

        $email = $new_customer_data['user_email'];

        if (!$email) {
            $this->logger->error("No email provided for user ID: $wp_user_id");
            return;
        }

        $contact = $this->connection->create_contact($email);

        if (!$contact) {
            $this->logger->error("Failed to create contact for user ID: $wp_user_id, email: $email");
            return;
        }

        $uuid = $contact['uuid'];
        $this->connection->sync_user_attributes($wp_user_id, $uuid);

        $this->process_create_account_credits($wp_user_id, $uuid);
    }

    /**
     * Processes and applies initial account creation credits.
     *
     * Retrieves and applies any credits configured for new account creation
     * based on the CREATE_ACCOUNT earn rule type.
     *

     *
     * @param int    $wp_user_id WordPress user ID.
     * @param string $uuid       Customer UUID.
     * @return void
     */
    private function process_create_account_credits(int $wp_user_id, string $uuid): void
    {
        $earn_rules = $this->earn_rules->get_earn_rules_by_type('CREATE_ACCOUNT');

        if (!$earn_rules) {
            return;
        }

        $earn_rule = $earn_rules[0];
        if ($earn_rule['credits']['value'] > 0) {
            $credits = $earn_rule['credits']['value'];

            $this->logger->info("Applying $credits credits to user $wp_user_id");

            $result = $this->connection->apply_credits($uuid, $credits);
            $this->connection->add_reward_log($wp_user_id, $earn_rule['id'], $credits);

            if (!$result) {
                $this->logger->error("Failed to apply $credits credits to user $wp_user_id");
            } else {
                $this->logger->info("Successfully applied $credits credits to user $wp_user_id");
            }
        }
    }
}
