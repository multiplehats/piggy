<?php

namespace Leat\Utils;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Utils\TranslatedText;

class PotentialCreditDisplay
{
    private $connection;
    private $settings;
    private $cache_duration = 60; // 1 minute cache

    public function __construct(Connection $connection, Settings $settings)
    {
        $this->connection = $connection;
        $this->settings = $settings;
    }

    /**
     * Get potential credits for cart total
     */
    public function get_potential_credits($cart_total)
    {
        $cache_key = 'potential_credits_' . md5($cart_total . get_current_user_id());
        $credits = wp_cache_get($cache_key);

        if (false === $credits) {
            $contact = null;

            if (is_user_logged_in()) {
                $contact = $this->connection->get_contact_by_wp_id(get_current_user_id(), false);
            }

            $credits = $this->connection->calculate_credits(
                $cart_total,
                $contact ? $contact['uuid'] : null
            );

            if ($credits !== null) {
                wp_cache_set($cache_key, $credits, '', $this->cache_duration);
            }
        }

        return $credits;
    }

    /**
     * Get formatted display message
     */
    public function get_display_message($credits)
    {
        $credits_name = TranslatedText::get_text(
            $this->settings->get_setting_value_by_id('credits_name')
        );

        if (is_user_logged_in()) {
            $format = TranslatedText::get_text(
                $this->settings->get_setting_value_by_id('dashboard_title_logged_in')
            );
        } else {
            $format = TranslatedText::get_text(
                $this->settings->get_setting_value_by_id('dashboard_title_logged_out')
            );
        }

        return Common::replace_placeholders($format, [
            'credits' => $credits,
            'credits_currency' => $credits_name
        ]);
    }

    /**
     * Get join program button text
     */
    public function get_join_button_text()
    {
        return TranslatedText::get_text(
            $this->settings->get_setting_value_by_id('dashboard_join_cta')
        );
    }
}
