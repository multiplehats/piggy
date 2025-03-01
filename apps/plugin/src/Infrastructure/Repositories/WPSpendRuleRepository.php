<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Interfaces\WPSpendRuleRepositoryInterface;
use Leat\Infrastructure\Constants\WPPostTypes;
use Leat\Infrastructure\Formatters\WPSpendRuleFormatter;
use Leat\Infrastructure\Constants\WPSpendRuleMetaKeys;
use Leat\Utils\Logger;
use Leat\Utils\Post;

/**
 * WPSpendRuleRepository class.
 *
 * @package Leat\Infrastructure\Repositories
 */
class WPSpendRuleRepository implements WPSpendRuleRepositoryInterface
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Formatter instance.
     *
     * @var WPSpendRuleFormatter
     */
    private $formatter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger();
        $this->formatter = new WPSpendRuleFormatter();
    }

    /**
     * Retrieves a spend rule by its ID.
     *
     * @param int $id The spend rule post ID.
     * @return array|null Formatted spend rule data or null if not found.
     */
    public function get_by_id($id): ?array
    {
        $post = get_post($id);

        if (empty($post) || WPPostTypes::SPEND_RULE !== $post->post_type) {
            return null;
        }

        return $this->formatter->format($post);
    }

    /**
     * Retrieves a spend rule by its Leat UUID.
     *
     * @param string $uuid The Leat reward UUID.
     * @return array|null Formatted spend rule data or null if not found.
     */
    public function get_by_uuid($uuid): ?array
    {
        $cache_key = 'leat_spend_rule_' . md5($uuid);
        $posts = wp_cache_get($cache_key);

        if (false === $posts) {
            $posts = get_posts([
                'post_type'      => WPPostTypes::SPEND_RULE,
                'meta_key'       => WPSpendRuleMetaKeys::UUID,
                'meta_value'     => $uuid,
                'posts_per_page' => 1,
            ]);
            wp_cache_set($cache_key, $posts, '', 3600);
        }

        if (!empty($posts)) {
            return $this->formatter->format($posts[0]);
        }

        return null;
    }

    public function get_rules(array $status = ['publish']): array
    {
        $args = array(
            'post_type'      => WPPostTypes::SPEND_RULE,
            'post_status'    => $status,
            'posts_per_page' => -1,
        );

        $formatted_posts = [];
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $uuid = Post::get_post_meta_data($post->ID, WPSpendRuleMetaKeys::UUID, null, true);

            if ($uuid) {
                $formatted_posts[] = $this->formatter->format($post);
            }
        }

        return $formatted_posts;
    }


    /**
     * Retrieves spend rules filtered by type.
     *
     * @param string|null $type Optional. The type of spend rule to filter by.
     * @param array $status Optional. Array of post statuses to include. Default ['publish'].
     * @return array|null Array of formatted spend rules or null if none found.
     */
    public function get_rules_by_type($type, array $status = ['publish']): ?array
    {
        $args = [
            'post_type'        => WPPostTypes::SPEND_RULE,
            'post_status'      => $status,
            'suppress_filters' => false,
            'posts_per_page'   => -1,
        ];

        if ($type) {
            $args['meta_query'] = [
                [
                    'key'   => WPSpendRuleMetaKeys::TYPE,
                    'value' => $type,
                ],
            ];
        }

        $cache_key = 'leat_spend_rules_' . md5(wp_json_encode($args));
        $posts     = wp_cache_get($cache_key);

        if (false === $posts) {
            $posts = get_posts($args);
            wp_cache_set($cache_key, $posts, '', 3600);
        }

        if (empty($posts)) {
            return null;
        }

        $formatted_posts = [];
        foreach ($posts as $post) {
            $formatted_posts[] = $this->formatter->format($post);
        }

        return $formatted_posts;
    }

    /**
     * Retrieves all active spend rules.
     *
     * @return array|null Array of formatted active spend rules.
     */
    public function get_active_rules(): ?array
    {
        return $this->get_rules_by_type(null, ['publish']);
    }

    /**
     * Creates or updates a spend rule based on a Leat reward.
     *
     * @param array $reward The Leat reward data.
     * @param int|null $existing_post_id Optional. Existing post ID to update.
     * @return void
     */
    public function create_or_update_from_reward(array $reward, ?int $existing_post_id = null): void
    {
        $post_data = array(
            'post_type'  => WPPostTypes::SPEND_RULE,
            'post_title' => $reward['title'],
            'meta_input' => array(
                WPSpendRuleMetaKeys::CREDIT_COST     => $reward['required_credits'],
                WPSpendRuleMetaKeys::UUID            => $reward['uuid'],
                WPSpendRuleMetaKeys::SELECTED_REWARD => $reward['uuid'],
            ),
        );

        if (isset($reward['media'])) {
            $post_data['meta_input'][WPSpendRuleMetaKeys::IMAGE] = $reward['media']['value'];
        }

        if ($existing_post_id) {
            $post_data['ID'] = $existing_post_id;
            wp_update_post($post_data);
        } else {
            $post_data['post_status'] = 'draft';
            $post_data['meta_input'][WPSpendRuleMetaKeys::TYPE] = "ORDER_DISCOUNT";
            wp_insert_post($post_data);
        }
    }

    public function create_or_update(array $promotion, ?int $existing_post_id = null): void
    {
        $post_data = array(
            'post_type'  => WPPostTypes::SPEND_RULE,
            'post_title' => $promotion['title'],
            'meta_input' => array(),
        );

        // Set post status if provided
        if (isset($promotion['status'])) {
            $post_data['post_status'] = $promotion['status'];
        }

        // Only set UUID for new posts, not for updates
        if (!$existing_post_id) {
            $post_data['meta_input'][WPSpendRuleMetaKeys::UUID] = $promotion['uuid'];
        }

        $optional_meta_fields = [
            'label' => WPSpendRuleMetaKeys::LABEL,
            'type' => WPSpendRuleMetaKeys::TYPE,
            'uuid' => WPSpendRuleMetaKeys::UUID,
            'image' => WPSpendRuleMetaKeys::IMAGE,
            'starts_at' => WPSpendRuleMetaKeys::STARTS_AT,
            'expires_at' => WPSpendRuleMetaKeys::EXPIRES_AT,
            'completed' => WPSpendRuleMetaKeys::COMPLETED,
            'credit_cost' => WPSpendRuleMetaKeys::CREDIT_COST,
            'selected_reward' => WPSpendRuleMetaKeys::SELECTED_REWARD,
            'description' => WPSpendRuleMetaKeys::DESCRIPTION,
            'instructions' => WPSpendRuleMetaKeys::INSTRUCTIONS,
            'fulfillment' => WPSpendRuleMetaKeys::FULFILLMENT,
            'discount_value' => WPSpendRuleMetaKeys::DISCOUNT_VALUE,
            'discount_type' => WPSpendRuleMetaKeys::DISCOUNT_TYPE,
            'minimum_purchase_amount' => WPSpendRuleMetaKeys::MINIMUM_PURCHASE_AMOUNT,
            'limit_usage_to_x_items' => WPSpendRuleMetaKeys::LIMIT_USAGE_TO_X_ITEMS,
            'selected_products' => WPSpendRuleMetaKeys::SELECTED_PRODUCTS,
            'selected_categories' => WPSpendRuleMetaKeys::SELECTED_CATEGORIES,
        ];

        foreach ($optional_meta_fields as $key => $meta_key) {
            if (isset($promotion[$key])) {
                $post_data['meta_input'][$meta_key] = $promotion[$key];
            }
        }

        if ($existing_post_id) {
            $post_data['ID'] = $existing_post_id;
            wp_update_post($post_data);
        } else {
            $post_data['post_status'] = 'draft';
            wp_insert_post($post_data);
        }
    }


    /**
     * Deletes a spend rule by its Leat UUID.
     *
     * @param string $uuid The Leat reward UUID to find and delete.
     * @return void
     */
    public function delete_by_uuid($uuid): void
    {
        $posts = get_posts([
            'post_type'              => WPPostTypes::SPEND_RULE,
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'meta_key'               => WPSpendRuleMetaKeys::UUID,
            'meta_value'             => $uuid,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        if (!empty($posts)) {
            wp_delete_post($posts[0], true);
        }
    }

    /**
     * Gets the applicable spend rule for a given credit amount.
     *
     * Finds the spend rule with the highest credit cost that is still within
     * the user's available credit amount.
     *
     * @param int $credit_amount The available credit amount.
     * @return array|null The applicable spend rule, or null if none found.
     */
    public function get_applicable_rule($credit_amount): ?array
    {
        $spend_rules = $this->get_rules_by_type(null);

        if (!$spend_rules) {
            return null;
        }

        $applicable_rule     = null;
        $highest_credit_cost = 0;

        foreach ($spend_rules as $rule) {
            $credit_cost = $rule['creditCost']['value'] ?? PHP_INT_MAX;

            if ($credit_amount >= $credit_cost && $credit_cost > $highest_credit_cost) {
                $applicable_rule     = $rule;
                $highest_credit_cost = $credit_cost;
            }
        }

        return $applicable_rule;
    }
}
