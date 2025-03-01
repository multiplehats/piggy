<?php

namespace Leat\Domain\Services\Customer;

use Leat\Api\Connection;
use Leat\Utils\Logger;

/**
 * Handles synchronization of customer attributes during login/logout events.
 *
 * @package Leat\Domain\Services\Customer

 */
class CustomerAttributeSync
{
    /**
     * API connection instance.
     *

     * @var Connection
     */
    private $connection;

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
     * @param Logger     $logger     Logger instance.
     */
    public function __construct(Connection $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Synchronizes user attributes when a user logs in.
     *
     * Updates the last login timestamp and syncs user attributes with the external service.
     *

     *
     * @param string   $user_login The user's login name.
     * @param \WP_User $user      WordPress user object.
     * @return void
     */
    public function sync_attributes_on_login($user_login, $user): void
    {
        try {
            $client = $this->connection->init_client();

            if (!$client) {
                return;
            }

            $user_id = $user->ID;
            $contact = $this->connection->get_contact_by_wp_id($user_id);
            $uuid = $contact['uuid'];

            $this->update_last_login($user_id);
            $this->connection->sync_user_attributes($user_id, $uuid);
        } catch (\Throwable $th) {
            $this->logger->error('Error syncing attributes on login: ' . $th->getMessage());
        }
    }

    /**
     * Synchronizes user attributes when a user logs out.
     *

     *
     * @return void
     */
    public function sync_attributes_on_logout(): void
    {
        try {
            $user_id = get_current_user_id();

            if (!$user_id) {
                return;
            }

            $contact = $this->connection->get_contact_by_wp_id($user_id);
            $uuid = $contact['uuid'];

            $this->connection->sync_user_attributes($user_id, $uuid);
        } catch (\Throwable $th) {
            $this->logger->error('Error syncing attributes on logout: ' . $th->getMessage());
        }
    }

    /**
     * Updates the user's last login timestamp.
     *

     *
     * @param int $user_id WordPress user ID.
     * @return string MySQL formatted timestamp of the last login.
     */
    private function update_last_login(int $user_id): string
    {
        $last_login = current_time('mysql');
        update_user_meta($user_id, 'leat_last_login', $last_login);
        return $last_login;
    }
}
