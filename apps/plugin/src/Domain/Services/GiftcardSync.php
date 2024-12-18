<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Utils\Logger;
use Piggy\Api\Models\Giftcards\Giftcard;
use WC_Coupon;

class GiftcardSync {
    private Connection $connection;
    private Logger $logger;

    private const HOOK_SYNC_GIFTCARDS = 'leat_sync_giftcards';
    private const HOOK_PROCESS_BATCH = 'leat_process_giftcard_batch';
    private const BATCH_SIZE = 10;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
        $this->logger = new Logger();

        add_action('init', [$this, 'register_hooks']);
    }

    public function register_hooks() {
        if (!class_exists('ActionScheduler_DataController')) {
            return;
        }

        if (!as_next_scheduled_action(self::HOOK_SYNC_GIFTCARDS)) {
            as_schedule_recurring_action(time(), DAY_IN_SECONDS, self::HOOK_SYNC_GIFTCARDS);
        }

        add_action(self::HOOK_SYNC_GIFTCARDS, [$this, 'start_sync']);
        add_action(self::HOOK_PROCESS_BATCH, [$this, 'process_batch'], 10, 2);
        add_action('admin_post_cancel_giftcard_sync', [$this, 'handle_cancel_sync']);
    }

    public function start_sync() {
        try {
            $this->logger->info('Starting giftcard sync');
            $this->cancel_pending_batches();

            // Get all giftcard programs
            $programs = $this->connection->get('giftcard-programs');
            $this->logger->info('Found ' . count($programs['data']) . ' giftcard programs');

            foreach ($programs['data'] as $program) {
                $this->schedule_program_sync($program['uuid']);
            }

        } catch (\Throwable $th) {
            $this->logger->error('Failed to start giftcard sync: ' . $th->getMessage());
            throw $th;
        }
    }

    private function schedule_program_sync(string $program_uuid) {
        $page = 1;
        $limit = 100;

        do {
            $giftcards = $this->connection->get('giftcards', [
                'giftcard_program_uuid' => $program_uuid,
                'page' => $page,
                'limit' => $limit
            ]);

            $batch = array_chunk($giftcards['data'], self::BATCH_SIZE);

            foreach ($batch as $giftcard_batch) {
                as_enqueue_async_action(
                    self::HOOK_PROCESS_BATCH,
                    ['giftcards' => $giftcard_batch],
                    'leat-giftcard-sync'
                );
            }

            $page++;
        } while ($page <= $giftcards['meta']['last_page']);
    }

    public function process_batch(array $giftcards) {
        foreach ($giftcards as $giftcard) {
            try {
                $this->sync_giftcard_to_coupon($giftcard);
            } catch (\Throwable $th) {
                $this->logger->error("Error processing giftcard {$giftcard['hash']}: " . $th->getMessage());
                continue;
            }
        }
    }

    private function sync_giftcard_to_coupon(array $giftcard) {
        $coupon_code = $giftcard['hash'];
        $coupon = new WC_Coupon($coupon_code);

        if (!$coupon->get_id()) {
            $coupon->set_code($coupon_code);
        }

        // Set coupon data
        $coupon->set_amount($giftcard['amount_in_cents'] / 100); // Convert cents to whole currency
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);

        if ($giftcard['expiration_date']) {
            $coupon->set_date_expires(strtotime($giftcard['expiration_date']));
        }

        // Set custom meta
        $coupon->update_meta_data('_leat_giftcard_uuid', $giftcard['uuid']);
        $coupon->update_meta_data('_leat_giftcard_program_uuid', $giftcard['giftcard_program']['uuid']);
        $coupon->update_meta_data('_leat_giftcard_type', $giftcard['type']);

        // Set status
        $coupon->set_virtual(true);
        if (!$giftcard['active']) {
            $coupon->set_status('trash');
        }

        $coupon->save();

        $this->logger->info("Synced giftcard {$giftcard['hash']} to coupon");
    }

    public function handle_cancel_sync() {
        check_admin_referer('cancel_giftcard_sync');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $this->cancel_pending_batches();

        wp_redirect(admin_url('admin.php?page=your-plugin&sync=cancelled'));
        exit;
    }

    private function cancel_pending_batches() {
        as_unschedule_all_actions(self::HOOK_PROCESS_BATCH, [], 'leat-giftcard-sync');
        $this->logger->info('Cancelled all pending giftcard sync batches');
    }

    private function is_sync_running(): bool {
        return as_has_scheduled_action(self::HOOK_PROCESS_BATCH, [], 'leat-giftcard-sync');
    }
}