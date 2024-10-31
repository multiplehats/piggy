<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules as PromotionRuleService;
use Leat\Utils\Logger;
use Piggy\Api\Models\Vouchers\Voucher;

class VoucherSync {
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PromotionRuleService
     */
    private $promotion_rule_service;

    public function __construct(Connection $connection) {
        error_log('VoucherSync constructor');

        $this->connection = $connection;
        $this->promotion_rule_service = new PromotionRuleService();
        $this->logger = new Logger();
        $this->register_hooks();
    }

    public function register_hooks() {
        // Register cron schedule if not already scheduled
        if (!wp_next_scheduled('leat_sync_vouchers')) {
            wp_schedule_event(time(), 'daily', 'leat_sync_vouchers');
        }

        // Register cron hook
        add_action('leat_sync_vouchers', [$this, 'sync_vouchers']);

        // Add hook for promotion rule save
        add_action('init', [$this, 'handle_promotion_rule_save'], 10, 1);
    }

    public function sync_vouchers() {
        $this->logger->info('Starting voucher sync');

        // Get all active promotion rules
        $active_promotions = $this->get_active_promotions();

        // Get all users with a Leat UUID
        $users = get_users([
            'meta_key' => 'leat_uuid',
            'fields' => ['ID', 'user_email']
        ]);

        foreach ($active_promotions as $promotion_uuid) {
            foreach ($users as $user) {
                $this->sync_user_vouchers($user, $promotion_uuid);
            }
        }

        $this->logger->info('Voucher sync completed');
    }

    private function get_active_promotions() {
        $args = array(
            'post_type' => 'leat_promotion_rule',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $posts = get_posts($args);
        $promotion_uuids = [];

        foreach ($posts as $post) {
            $uuid = get_post_meta($post->ID, '_leat_promotion_uuid', true);
            if ($uuid) {
                $promotion_uuids[] = $uuid;
            }
        }

        return $promotion_uuids;
    }

    private function sync_user_vouchers($user, $promotion_uuid) {
        $contact_uuid = get_user_meta($user->ID, 'leat_uuid', true);
        if (!$contact_uuid) {
            return;
        }

        $client = $this->connection->init_client();

        if (!$client) {
            return;
        }

        try {
            $page = 1;
            $per_page = 100;

            do {
                $response = Voucher::list([
                    'contact_uuid' => $contact_uuid,
                    'promotion_uuid' => $promotion_uuid,
                    'limit' => $per_page,
                    'page' => $page
                ]);

                if (!$response || !isset($response['data'])) {
                    break;
                }

                $vouchers = $response['data'];
                $this->update_user_vouchers($user->ID, $vouchers);

                $page++;

                // Check if we've processed all pages
                if (!isset($response['meta']['next_page']) || $response['meta']['next_page'] === null) {
                    break;
                }
            } while (true);

        } catch (\Throwable $th) {
            $this->logger->error("Error syncing vouchers for user {$user->ID}: " . $th->getMessage());
        }
    }

    private function update_user_vouchers($user_id, $vouchers) {
        $existing_vouchers = get_user_meta($user_id, 'leat_vouchers', true) ?: [];
        $updated_vouchers = [];

        foreach ($vouchers as $voucher) {
            $updated_vouchers[$voucher['uuid']] = [
                'code' => $voucher['code'],
                'status' => $voucher['status'],
                'name' => $voucher['name'],
                'description' => $voucher['description'],
                'expiration_date' => $voucher['expiration_date'],
                'activation_date' => $voucher['activation_date'],
                'redeemed_at' => $voucher['redeemed_at'],
                'is_redeemed' => $voucher['is_redeemed'],
                'promotion_uuid' => $voucher['promotion']['uuid'],
                'promotion_name' => $voucher['promotion']['name'],
            ];
        }

        update_user_meta($user_id, 'leat_vouchers', $updated_vouchers);
    }

    public function handle_promotion_rule_save($post_id) {
        $this->sync_vouchers();
    }
}
