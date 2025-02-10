<?php

namespace Leat\Domain\Services\Customer;

use Leat\Api\Connection;

/**
 * Handles the display of customer profile information in WordPress admin.
 *
 * This class is responsible for rendering various customer-related information
 * such as claimed rewards and unique identifiers on the WordPress user profile page.
 *

 */
class CustomerProfileDisplay
{
    /**
     * API Connection instance.
     *

     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *

     * @param Connection $connection API connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Displays the claimed rewards section on user profile.
     *
     * Renders a table showing all rewards claimed by the user, including
     * earn rule IDs, credits earned, and timestamps.
     *

     * @param WP_User $user WordPress user object.
     * @return void
     */
    public function show_claimed_rewards_on_profile($user): void
    {
        $reward_logs = $this->connection->get_user_reward_logs($user->ID);
?>
        <h3><?php esc_html_e('Leat Claimed Rewards', 'leat-crm'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="leat_claimed_rewards"><?php esc_html_e('Claimed Rewards', 'leat-crm'); ?></label></th>
                <td>
                    <?php
                    if (!empty($reward_logs)) {
                        echo '<ul>';
                        foreach ($reward_logs as $log) {
                            echo '<li>';
                            echo esc_html__('Earn Rule ID: ', 'leat-crm') . esc_html($log['earn_rule_id']) . '<br>';
                            echo esc_html__('Credits: ', 'leat-crm') . esc_html($log['credits']) . '<br>';
                            echo esc_html__('Timestamp: ', 'leat-crm') . esc_html(gmdate('Y-m-d H:i:s', (int) $log['timestamp']));
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        esc_html_e('No claimed rewards.', 'leat-crm');
                    }
                    ?>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Displays the Leat UUID on user profile.
     *
     * Renders a table showing the user's unique Contact ID (UUID) from
     * the Leat CRM system.
     *

     * @param WP_User $user WordPress user object.
     * @return void
     */
    public function show_uuid_on_profile($user): void
    {
    ?>
        <h3><?php esc_html_e('Leat', 'leat-crm'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="leat_uuid"><?php esc_html_e('Contact ID', 'leat-crm'); ?></label></th>
                <td>
                    <?php
                    $contact = $this->connection->get_contact_by_wp_id($user->ID);
                    $uuid = $contact['uuid'];
                    echo esc_html($uuid ? $uuid : '—');
                    ?>
                </td>
            </tr>
        </table>
<?php
    }
}
