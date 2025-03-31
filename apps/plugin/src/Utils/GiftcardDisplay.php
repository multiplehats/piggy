<?php

namespace Leat\Utils;

use Leat\Settings;
use Leat\Utils\TranslatedText;
use Leat\Utils\Common;

class GiftcardDisplay
{
    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings Settings instance.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get the formatted success message for an applied gift card.
     *
     * @param string $coupon_code The gift card code.
     * @param string|null $formatted_balance The formatted balance string (e.g., "€ 100,00") or null/placeholder if balance check failed.
     * @return string|null The formatted success message or null if the setting is empty.
     */
    public function get_formatted_success_message(string $coupon_code, ?string $formatted_balance): ?string
    {
        $message_setting = $this->settings->get_setting_value_by_id('giftcard_applied_success_message');

        if (empty($message_setting)) {
            // Fallback or return null if setting is not configured
            // Using a default similar to the original hardcoded one for safety
            /* translators: %1$s: gift card code, %2$s: gift card balance */
            $format = __('✅ Gift card %1$s applied. Balance: %2$s', 'leat-crm');
            return sprintf($format, $coupon_code, $formatted_balance ?? __('N/A', 'leat-crm'));
        }

        $format = TranslatedText::get_text($message_setting);

        if (empty($format)) {
            // Fallback if translation is empty
            /* translators: %1$s: gift card code, %2$s: gift card balance */
            $format_fallback = __('✅ Gift card {{ code }} applied. Balance: {{ balance }}', 'leat-crm');
            $replacements = [
                'code' => $coupon_code,
                'balance' => $formatted_balance ?? __('N/A', 'leat-crm'),
            ];
            return Common::replace_placeholders($format_fallback, $replacements);
        }

        $replacements = [
            'code' => $coupon_code,
            'balance' => $formatted_balance ?? __('N/A', 'leat-crm'), // Handle potential null balance
        ];

        return Common::replace_placeholders($format, $replacements);
    }
}
