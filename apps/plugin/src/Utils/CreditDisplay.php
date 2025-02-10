<?php

namespace Leat\Utils;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Utils\TranslatedText;
use Leat\Utils\Common;

class CreditDisplay
{
    /**
     * Connection instance.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param Connection $connection Connection instance.
     * @param Settings $settings Settings instance.
     */
    public function __construct(Connection $connection, Settings $settings)
    {
        $this->connection = $connection;
        $this->settings = $settings;
    }

    /**
     * Get formatted credit display for a user
     *
     * @param int $user_id WordPress user ID
     * @param string|null $format Optional custom format string
     * @param bool $hide_zero Whether to hide zero credit balances
     * @return string|null Formatted credit display or null if no display should be shown
     */
    public function get_formatted_credits($user_id, ?string $format = null, bool $hide_zero = false): ?string
    {
        if (!is_user_logged_in() && $user_id === 0) {
            return null;
        }

        $cache_key = 'contact_data_' . $user_id;
        $contact = wp_cache_get($cache_key);

        if (false === $contact) {
            $contact = $this->connection->get_contact_by_wp_id($user_id);

            if ($contact) {
                // Cache for 5 minutes
                wp_cache_set($cache_key, $contact, '', 300);
            }
        }

        if (!$contact) {
            return null;
        }

        $points = $contact['balance']['credits'];

        if ($points === 0 && $hide_zero) {
            return null;
        }

        $credits_currency_setting = $this->settings->get_setting_value_by_id('credits_name');
        $credits_currency = TranslatedText::get_text($credits_currency_setting);

        if (!$format) {
            $default_format = $this->settings->get_setting_value_by_id('dashboard_title_logged_in');

            $format = TranslatedText::get_text($default_format);
        }

        $replacements = array(
            'credits' => $points,
            'credits_currency' => $credits_currency
        );

        return Common::replace_placeholders($format, $replacements);
    }
}
