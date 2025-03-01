<?php

namespace Leat\Infrastructure\Formatters;

use Leat\Utils\Post;
use Leat\Infrastructure\Constants\WPPromotionRuleMetaKeys;

class WPPromotionRuleFormatter
{
    /**
     * Formats a promotion rule post for API response.
     *
     * Converts a WordPress post object into a structured array containing
     * all promotion rule settings and metadata.
     *
     * @param \WP_Post $post The promotion rule post object.
     * @return array Formatted promotion rule data.
     */
    public function format($post)
    {
        $promotion_rule = [
            'id'                => (int) $post->ID,
            'createdAt'         => $post->post_date,
            'updatedAt'         => $post->post_modified,
            'status'            => [
                'id'          => 'status',
                'label'       => __('Status', 'leat-crm'),
                'default'     => 'publish',
                'value'       => $post->post_status,
                'options'     => [
                    'publish' => ['label' => __('Active', 'leat-crm')],
                    'draft'   => ['label' => __('Inactive', 'leat-crm')],
                ],
                'type'        => 'select',
                'description' => __('Set the status of the rule. Inactive promotion rules will not be displayed to users.', 'leat-crm'),
            ],
            'label'             => [
                'id'          => 'label',
                'label'       => __('Label', 'leat-crm'),
                'default'     => (function () {
                    return [
                        'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}',
                    ];
                })(),
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::LABEL),
                'type'        => 'translatable_text',
                'description' => (function () {
                    $placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

                    return sprintf(
                        /* translators: %s: List of available placeholders that can be used in the promotion label text */
                        __("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm'),
                        $placeholders
                    );
                })(),
            ],
            'title'             => [
                'id'          => 'title',
                'label'       => __('Title', 'leat-crm'),
                'default'     => null,
                'value'       => $post->post_title,
                'type'        => 'text',
                'description' => __('This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat-crm'),
            ],
            'leatPromotionUuid' => [
                'id'          => 'leat_promotion_uuid',
                'label'       => __('Leat Promotion UUID', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::UUID, null),
                'type'        => 'text',
                'description' => __('The UUID of the corresponding Leat promotion.', 'leat-crm'),
            ],
            'image'             => [
                'id'          => 'image',
                'label'       => __('Image', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::IMAGE, null),
                'type'        => 'text',
                'description' => __('The image that is displayed for the promotion rule.', 'leat-crm'),
            ],
            'selectedProducts' => [
                'id'          => 'selected_products',
                'label'       => __('Selected products', 'leat-crm'),
                'optional'    => true,
                'default'     => [],
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::SELECTED_PRODUCTS, []),
                'type'        => 'products_select',
                'description' => __('The products that are selected for the promotion rule.', 'leat-crm'),
            ],
            'discountValue' => [
                'id'          => 'discount_value',
                'label'       => __('Discount value', 'leat-crm'),
                'default'     => 10,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::DISCOUNT_VALUE, null),
                'type'        => 'number',
                'description' => __('The value of the discount.', 'leat-crm'),
            ],
            'discountType' => [
                'id'          => 'discount_type',
                'label'       => __('Discount type', 'leat-crm'),
                'default'     => 'percentage',
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::DISCOUNT_TYPE, 'percentage'),
                'type'        => 'select',
                'options'     => [
                    'percentage' => ['label' => __('Percentage', 'leat-crm')],
                    'fixed'      => ['label' => __('Fixed amount', 'leat-crm')],
                ],
                'description' => __('The type of discount.', 'leat-crm'),
            ],
            'minimumPurchaseAmount' => [
                'id'          => 'minimum_purchase_amount',
                'label'       => __('Minimum purchase amount', 'leat-crm'),
                'default'     => 0,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::MINIMUM_PURCHASE_AMOUNT, 0),
                'type'        => 'number',
                'description' => __('The minimum purchase amount required to redeem the promotion.', 'leat-crm'),
            ],
            'voucherLimit' => [
                'id'          => 'voucher_limit',
                'label'       => __('Voucher limit', 'leat-crm'),
                'default'     => 0,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::VOUCHER_LIMIT, 0),
                'type'        => 'number',
                'description' => __('The maximum number of vouchers that can be issued for this promotion. 0 means unlimited.', 'leat-crm'),
            ],
            'individualUse' => [
                'id'          => 'individual_use',
                'label'       => __('Individual Use', 'leat-crm'),
                'default'     => 'off',
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::INDIVIDUAL_USE, 'off'),
                'type'        => 'switch',
                'description' => __('Check this box if the coupon cannot be used in conjunction with other coupons.', 'leat-crm'),
            ],
            'limitPerContact' => [
                'id'          => 'limit_per_contact',
                'label'       => __('Limit Per Contact', 'leat-crm'),
                'default'     => 0,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::LIMIT_PER_CONTACT, 1),
                'type'        => 'number',
                'description' => __('The maximum number of times a single contact can use this promotion. 0 means unlimited.', 'leat-crm'),
            ],
            'expirationDuration' => [
                'id'          => 'expiration_duration',
                'label'       => __('Expiration Duration', 'leat-crm'),
                'default'     => 0,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::EXPIRATION_DURATION, 0),
                'type'        => 'number',
                'description' => __('The number of days after which the promotion expires. 0 means no expiration.', 'leat-crm'),
            ],
            'redemptionsPerVoucher' => [
                'id'          => 'redemptions_per_voucher',
                'label'       => __('Redemptions Per Voucher', 'leat-crm'),
                'default'     => 0,
                'value'       => Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::REDEMPTIONS_PER_VOUCHER, 0),
            ],
        ];

        return $promotion_rule;
    }
}
