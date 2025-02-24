<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Interfaces\WPPromotionRuleRepositoryInterface;
use Leat\Infrastructure\Constants\WPPostTypes;
use Leat\Infrastructure\Formatters\WPPromotionRuleFormatter;
use Leat\Infrastructure\Constants\WPPromotionRuleMetaKeys;
use Leat\Utils\Logger;
use Leat\Utils\Post;

/**
 * WPPromotionRuleRepository class.
 *
 * @package Leat\Infrastructure\Repositories
 */
class WPPromotionRuleRepository implements WPPromotionRuleRepositoryInterface
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
     * @var WPPromotionRuleFormatter
     */
    private $formatter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger();
        $this->formatter = new WPPromotionRuleFormatter();
    }

    public function get_by_id($id): ?array
    {
        $post = get_post($id);

        if (empty($post) || WPPostTypes::PROMOTION_RULE !== $post->post_type) {
            return null;
        }

        return $this->formatter->format($post);
    }

    public function get_by_uuid($uuid): ?array
    {
        $args = array(
            'post_type'      => WPPostTypes::PROMOTION_RULE,
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => '_leat_promotion_uuid',
                    'value'   => $uuid,
                    'compare' => '=',
                ),
            ),
        );

        $posts = get_posts($args);

        if (!empty($posts)) {
            return $this->formatter->format($posts[0]);
        }

        return null;
    }

    public function get_rules(array $status = ['publish']): array
    {
        $args = array(
            'post_type'      => WPPostTypes::PROMOTION_RULE,
            'post_status'    => $status,
            'posts_per_page' => -1,
        );

        $formatted_posts = [];
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $uuid = Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::UUID, null, true);

            if ($uuid) {
                $formatted_posts[] = $this->formatter->format($post);
            }
        }

        return $formatted_posts;
    }

    public function get_active_rules(): array
    {
        $args = array(
            'post_type'      => WPPostTypes::PROMOTION_RULE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $formatted_posts = [];
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $uuid = Post::get_post_meta_data($post->ID, WPPromotionRuleMetaKeys::UUID, null, true);

            if ($uuid) {
                $formatted_posts[] = $this->formatter->format($post);
            }
        }

        return $formatted_posts;
    }

    public function create_or_update(array $promotion, ?int $existing_post_id = null): void
    {
        $post_data = array(
            'post_type'  => WPPostTypes::PROMOTION_RULE,
            'post_title' => $promotion['title'],
            'meta_input' => array(),
        );

        // Set post status if provided
        if (isset($promotion['status'])) {
            $post_data['post_status'] = $promotion['status'];
        }

        // Only set UUID for new posts, not for updates
        if (!$existing_post_id) {
            $post_data['meta_input'][WPPromotionRuleMetaKeys::UUID] = $promotion['uuid'];
        }

        $optional_meta_fields = [
            'label' => WPPromotionRuleMetaKeys::LABEL,
            'image' => WPPromotionRuleMetaKeys::IMAGE,
            'selected_products' => WPPromotionRuleMetaKeys::SELECTED_PRODUCTS,
            'discount_value' => WPPromotionRuleMetaKeys::DISCOUNT_VALUE,
            'discount_type' => WPPromotionRuleMetaKeys::DISCOUNT_TYPE,
            'minimum_purchase_amount' => WPPromotionRuleMetaKeys::MINIMUM_PURCHASE_AMOUNT,
            'voucher_limit' => WPPromotionRuleMetaKeys::VOUCHER_LIMIT,
            'individual_use' => WPPromotionRuleMetaKeys::INDIVIDUAL_USE,
            'limit_per_contact' => WPPromotionRuleMetaKeys::LIMIT_PER_CONTACT,
            'expiration_duration' => WPPromotionRuleMetaKeys::EXPIRATION_DURATION,
            'redemptions_per_voucher' => WPPromotionRuleMetaKeys::REDEMPTIONS_PER_VOUCHER,
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

    public function delete($uuid): void
    {
        $args = array(
            'post_type'      => WPPostTypes::PROMOTION_RULE,
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => WPPromotionRuleMetaKeys::UUID,
                    'value'   => $uuid,
                    'compare' => '=',
                ),
            ),
        );

        $posts = get_posts($args);

        if (!empty($posts)) {
            wp_delete_post($posts[0]->ID);
        }
    }

    public function handle_duplicates(array $uuids): void
    {
        $this->logger->info('Handling duplicated promotion rules for UUIDs: ' . implode(', ', $uuids));

        foreach ($uuids as $uuid) {
            $args = array(
                'post_type'      => WPPostTypes::PROMOTION_RULE,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'DESC',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => WPPromotionRuleMetaKeys::UUID,
                        'value'   => $uuid,
                        'compare' => '=',
                    ),
                ),
            );

            $post_ids = get_posts($args);

            if (count($post_ids) > 1) {
                $keep_id = array_shift($post_ids);
                $this->logger->info("Keeping promotion rule with post ID: $keep_id for UUID: $uuid");

                foreach ($post_ids as $post_id) {
                    $this->logger->info("Deleting duplicate promotion rule with post ID: $post_id for UUID: $uuid");
                    wp_delete_post($post_id, true);
                }
            }
        }

        $this->logger->info('Finished handling duplicated promotion rules');
    }

    public function delete_with_empty_uuid(): int
    {
        $args = array(
            'post_type'      => WPPostTypes::PROMOTION_RULE,
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft'),
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => WPPromotionRuleMetaKeys::UUID,
                    'value'   => '',
                    'compare' => '=',
                ),
                array(
                    'key'     => WPPromotionRuleMetaKeys::UUID,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $posts = get_posts($args);

        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }

        return count($posts);
    }
}
