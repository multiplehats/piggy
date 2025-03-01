<?php

namespace Leat\Infrastructure\Formatters;

use Leat\Utils\Post;
use Leat\Infrastructure\Constants\WPSpendRuleMetaKeys;

class WPSpendRuleFormatter
{
    /**
     * Formats a spend rule post for API response.
     *
     * Converts a WordPress post object into a structured array containing
     * all spend rule settings and metadata.
     *
     * @param \WP_Post $post The spend rule post object to format.
     * @return array Formatted spend rule data.
     */
    public function format($post)
    {
        $type = Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::TYPE, null);

        $spend_rule = [
            'id'                 => (int) $post->ID,
            'createdAt'          => $post->post_date,
            'updatedAt'          => $post->post_modified,
            'status'             => [
                'id'          => 'status',
                'label'       => __('Status', 'leat-crm'),
                'default'     => 'publish',
                'value'       => $post->post_status,
                'options'     => [
                    'publish' => ['label' => __('Active', 'leat-crm')],
                    'draft'   => ['label' => __('Inactive', 'leat-crm')],
                ],
                'type'        => 'select',
                'description' => __('Set the status of the rule. Inactive spend rules will not be displayed to users.', 'leat-crm'),
            ],
            'title'              => [
                'id'          => 'title',
                'label'       => __('Title', 'leat-crm'),
                'default'     => null,
                'value'       => $post->post_title,
                'type'        => 'text',
                'description' => __('This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat-crm'),
            ],
            'type'               => [
                'id'          => 'type',
                'label'       => __('Type', 'leat-crm'),
                'default'     => 'FREE_PRODUCT',
                'value'       => $type,
                'type'        => 'select',
                'options'     => [
                    'FREE_PRODUCT'   => ['label' => __('Free / Discounted Product', 'leat-crm')],
                    'ORDER_DISCOUNT' => ['label' => __('Order Discount', 'leat-crm')],
                    'FREE_SHIPPING'  => ['label' => __('Free Shipping', 'leat-crm')],
                    'CATEGORY'       => ['label' => __('Category Discount', 'leat-crm')],
                ],
                'description' => __('The type of spend rule.', 'leat-crm'),
            ],
            'startsAt'           => [
                'id'          => 'starts_at',
                'label'       => __('Starts at', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::STARTS_AT, null),
                'type'        => 'date',
                'description' => __('Optional date for when the rule should start.', 'leat-crm'),
            ],
            'expiresAt'          => [
                'id'          => 'expires_at',
                'label'       => __('Expires at', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::EXPIRES_AT, null),
                'type'        => 'date',
                'description' => __('Optional date for when the rule should expire.', 'leat-crm'),
            ],
            'completed'          => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::COMPLETED, null),
            'creditCost'         => [
                'id'          => 'credit_cost',
                'label'       => __('Credit cost', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::CREDIT_COST, null),
                'type'        => 'number',
                'description' => __('The amount of credits it will cost to redeem the reward. This is managed in the Leat dashboard.', 'leat-crm'),
            ],
            'selectedReward'     => [
                'id'          => 'selected_reward',
                'label'       => __('Selected reward', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::SELECTED_REWARD, null),
                'type'        => 'text',
                'description' => __('The reward that is selected for the spend rule.', 'leat-crm'),
            ],
            'image'              => [
                'id'          => 'image',
                'label'       => __('Image', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::IMAGE, null),
                'type'        => 'text',
                'description' => __('The image that is displayed for the spend rule.', 'leat-crm'),
            ],
            'description'        => [
                'id'          => 'description',
                'label'       => __('Description', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::DESCRIPTION, null),
                'type'        => 'translatable_text',
                'description' => $this->get_description_placeholder($type),
            ],
            'instructions'       => [
                'id'          => 'instructions',
                'label'       => __('Instructions', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::INSTRUCTIONS, null),
                'type'        => 'translatable_text',
                'description' => $this->get_instructions_placeholder(),
            ],
            'fulfillment'        => [
                'id'          => 'fulfillment',
                'label'       => __('Fulfillment description', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::FULFILLMENT, null),
                'type'        => 'translatable_text',
                'description' => $this->get_fulfillment_placeholder($type),
            ],
            'leatRewardUuid'     => [
                'id'          => 'leat_reward_uuid',
                'label'       => __('Leat Reward UUID', 'leat-crm'),
                'default'     => null,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::UUID, null),
                'type'        => 'text',
                'description' => __('The UUID of the corresponding Leat reward.', 'leat-crm'),
            ],
            'selectedCategories' => [
                'id'          => 'selected_categories',
                'label'       => __('Selected category', 'leat-crm'),
                'default'     => [],
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::SELECTED_CATEGORIES, []),
                'type'        => 'categories_select',
                'description' => __('The category that the user can spent their credits in.', 'leat-crm'),
            ],
            'limitUsageToXItems' => [
                'id'          => 'limit_usage_to_x_items',
                'label'       => __('Limit usage to X items', 'leat-crm'),
                'default'     => 1,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::LIMIT_USAGE_TO_X_ITEMS, 0),
                'type'        => 'number',
                'description' => __('Limit the discount to a specific number of items. Set to 0 for unlimited. If you set it to 0 be aware that this will allow the customer to use the discount on all items in the cart.', 'leat-crm'),
            ],
            'label' => [
                'id'          => 'label',
                'label'       => __('Label', 'leat-crm'),
                'default'     => $this->get_default_label($type),
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::LABEL),
                'type'        => 'translatable_text',
                'description' => $this->get_label_description($type),
            ],
            'selectedProducts' => [
                'id'          => 'selected_products',
                'label'       => __('Selected products', 'leat-crm'),
                'default'     => [],
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::SELECTED_PRODUCTS, []),
                'type'        => 'products_select',
                'description' => __('The products that are selected for the spend rule.', 'leat-crm'),
            ],
            'discountValue' => [
                'id'          => 'discount_value',
                'label'       => __('Discount value', 'leat-crm'),
                'default'     => 10,
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::DISCOUNT_VALUE, null),
                'type'        => 'number',
                'description' => __('The value of the discount.', 'leat-crm'),
            ],
            'discountType' => [
                'id'          => 'discount_type',
                'label'       => __('Discount type', 'leat-crm'),
                'default'     => 'percentage',
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::DISCOUNT_TYPE, 'percentage'),
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
                'value'       => Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::MINIMUM_PURCHASE_AMOUNT, 0),
                'type'        => 'number',
                'description' => __('The minimum purchase amount required to redeem the reward.', 'leat-crm'),
            ],
        ];

        return $spend_rule;
    }

    /**
     * Gets the description text for spend rule labels.
     *
     * @param string $type The spend rule type.
     * @return string The formatted description text with placeholder information.
     */
    private function get_label_description($type)
    {
        $placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

        /* translators: %s: List of available placeholders that can be used in the label text. */
        return sprintf(__("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm'), $placeholders);
    }

    /**
     * Gets the default label template for a spend rule type.
     *
     * @param string $type The spend rule type.
     * @return array Default label configuration.
     */
    private function get_default_label($type)
    {
        return [
            'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}',
        ];
    }

    /**
     * Gets the description text for spend rule descriptions.
     *
     * @param string $type The spend rule type.
     * @return string The formatted description text with placeholder information.
     */
    private function get_description_placeholder($type)
    {
        $placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

        /* translators: %s: List of available placeholders that can be used in the description text. */
        return sprintf(__('Add a description of the reward. Available placeholders: %s', 'leat-crm'), $placeholders);
    }

    /**
     * Gets the instructions text for spend rule instructions.
     *
     * @return string The formatted instructions text with placeholder information.
     */
    private function get_instructions_placeholder()
    {
        return __('Add instructions on how to redeem the reward', 'leat-crm');
    }

    /**
     * Gets the fulfillment text for spend rule fulfillments.
     *
     * @param string $type The spend rule type.
     * @return string The formatted fulfillment text with placeholder information.
     */
    private function get_fulfillment_placeholder($type)
    {
        $placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

        /* translators: %s: List of available placeholders that can be used in the fulfillment text. */
        return sprintf(__('Add instructions on how fulfillment will be handled. Available placeholders: %s', 'leat-crm'), $placeholders);
    }
}
