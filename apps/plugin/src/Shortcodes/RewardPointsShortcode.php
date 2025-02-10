<?php

namespace Leat\Shortcodes;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Shortcodes\AbstractShortcode;
use Leat\Utils\TranslatedText;
use Leat\Utils\CreditDisplay;

class RewardPointsShortcode extends AbstractShortcode
{
    /**
     * Shortcode name within this namespace.
     *
     * @var string
     */
    protected $shortcode_name = 'reward_points';

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
     * Credit display helper.
     *
     * @var CreditDisplay
     */
    private $credit_display;

    /**
     * Constructor.
     *
     * @param AssetApi $asset_api Instance of the asset API.
     * @param Connection $connection Instance of Connection.
     * @param Settings $settings Instance of Settings.
     */
    public function __construct($asset_api, Connection $connection, Settings $settings)
    {
        parent::__construct($asset_api);
        $this->connection = $connection;
        $this->settings = $settings;

        $this->credit_display = new CreditDisplay($connection, $settings);
    }

    public function get_assets()
    {
        return array();
    }

    public function init_hooks() {}

    /**
     * Sets the shortcode default attributes.
     *
     * @return array Default attributes for the shortcode.
     */
    public function get_shortcode_type_attributes(): array
    {
        // Get default format from settings and ensure it's a string
        $default_format = $this->settings->get_setting_value_by_id('dashboard_title_logged_in');
        $default_format = TranslatedText::get_text($default_format);

        return array(
            'user_id' => get_current_user_id(),
            'format' => $default_format,
            'wrapper_class' => 'leat-reward-points',
            'hide_zero' => 'no',
        );
    }

    public function shortcode_output($attributes, $content = '')
    {
        $formatted_text = $this->credit_display->get_formatted_credits(
            absint($attributes['user_id']),
            $attributes['format'],
            $attributes['hide_zero'] === 'yes'
        );

        if (!$formatted_text) {
            return '';
        }

        return sprintf(
            '<div class="%s">%s</div>',
            esc_attr($attributes['wrapper_class']),
            esc_html($formatted_text)
        );
    }
}
