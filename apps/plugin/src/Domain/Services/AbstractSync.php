<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Utils\Logger;

/**
 * Abstract class for syncing data from Leat to WordPress.
 * This is a simple sync for items that are not related to each other and don't have
 * complex relationships or dependencies. Best suited for straightforward, independent
 * data structures where items can be processed individually.
 */
abstract class AbstractSync
{
    protected Connection $connection;
    protected Logger $logger;
    protected string $prefix = 'leat';
    protected const BATCH_SIZE = 20;

    protected array $stats = [
        'total_items' => 0,
        'items_processed' => 0,
        'items_updated' => 0,
        'items_created' => 0,
        'items_deleted' => 0,
        'is_processing' => false,
        'last_sync' => null,
    ];

    /**
     * Constructor.
     *
     * @param Connection $connection The Connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->logger = new Logger();
    }

    /**
     * Initialize the sync. Also add any hooks here.
     *
     * @return void
     */
    abstract public function init(): void;

    /**
     * Get the post type for this sync.
     *
     * @return string
     */
    abstract protected function get_post_type(): string;

    /**
     * Get the action name for this sync.
     *
     * @return string
     */
    abstract protected function get_action_name(): string;

    /**
     * Format the data from API for storage.
     *
     * @param array $item
     * @return array
     */
    abstract protected function format_data(array $item): array;

    /**
     * Get the UUID meta key.
     *
     * @return string
     */
    abstract protected function get_uuid_meta_key(): string;

    /**
     * Create or update an item.
     *
     * @param array $data
     * @param int|null $existing_id
     * @return void
     */
    abstract protected function upsert_item(array $data, ?int $existing_id = null): void;

    /**
     * Get information about the current sync process
     *
     * @return array{
     *     is_processing: bool,
     *     total_items: int,
     *     items_processed: int,
     *     items_updated: int,
     *     items_created: int,
     *     items_deleted: int,
     *     last_sync: ?array
     * }
     */
    public function get_sync_status(): array
    {
        return [
            'type' => 'sync',
            'is_processing' => $this->stats['is_processing'],
            'total_items' => $this->stats['total_items'],
            'items_processed' => $this->stats['items_processed'],
            'items_updated' => $this->stats['items_updated'],
            'items_created' => $this->stats['items_created'],
            'items_deleted' => $this->stats['items_deleted'],
            'last_sync' => $this->stats['last_sync']
        ];
    }

    /**
     * Start the sync process.
     *
     * @return bool
     * @throws \Throwable
     */
    public function start_sync(): bool
    {
        try {
            $this->stats['is_processing'] = true;

            $client = $this->connection->init_client();
            if (!$client) {
                $this->logger->error('Failed to initialize client for sync');
                $this->update_sync_failure('Failed to initialize client');
                return false;
            }

            $items = $this->connection->get_items_for_sync($this->get_action_name());
            if (!$items) {
                $this->logger->info("No items found for {$this->get_action_name()} sync");
                $this->update_sync_success();
                return true;
            }

            $this->stats['total_items'] = count($items);
            $this->logger->info("Starting {$this->get_action_name()} sync. Total items: " . count($items));

            // Get all UUIDs from the API response
            $api_uuids = array_column($items, 'uuid');

            // Get all existing posts
            $existing_posts = get_posts([
                'post_type' => $this->get_post_type(),
                'posts_per_page' => -1,
                'post_status' => ['publish', 'draft', 'pending'],
            ]);

            // Delete posts that don't exist in the API response
            foreach ($existing_posts as $post) {
                $post_uuid = get_post_meta($post->ID, $this->get_uuid_meta_key(), true);
                if (!in_array($post_uuid, $api_uuids, true)) {
                    $this->logger->info("Deleting orphaned post ID: {$post->ID} with UUID: {$post_uuid}");
                    if (wp_delete_post($post->ID, true)) {
                        $this->stats['items_deleted']++;
                    }
                }
            }

            // Process items in batches
            $chunks = array_chunk($items, static::BATCH_SIZE);

            foreach ($chunks as $batch) {
                $this->process_batch($batch);

                // Add a small delay between batches to prevent overwhelming
                if (count($chunks) > 1) {
                    usleep(100000); // 100ms delay
                }
            }

            // Move duplicate handling to after all items are processed
            $this->handleDuplicates($api_uuids);

            $this->logger->info("Sync completed. Updated: {$this->stats['items_updated']}, Created: {$this->stats['items_created']}, Deleted: {$this->stats['items_deleted']}");
            $this->update_sync_success();
            return true;
        } catch (\Throwable $th) {
            $this->update_sync_failure($th->getMessage());
            $this->logger->error("Failed to start {$this->get_action_name()} sync: " . $th->getMessage());
            throw $th;
        }
    }

    protected function process_batch(array $batch): void
    {
        // Get existing items for this batch
        $formatted_batch = array_map([$this, 'format_data'], $batch);
        $batch_uuids = array_column($formatted_batch, 'uuid');

        $existing_items = get_posts([
            'post_type' => $this->get_post_type(),
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
            'meta_query' => [
                [
                    'key' => $this->get_uuid_meta_key(),
                    'value' => $batch_uuids,
                    'compare' => 'IN'
                ]
            ]
        ]);

        $existing_uuids = array_column($existing_items, $this->get_uuid_meta_key(), 'ID');

        foreach ($formatted_batch as $item) {
            $existing_post_id = array_search($item['uuid'], $existing_uuids, true);

            if (false !== $existing_post_id) {
                if ($this->upsert_item($item, $existing_post_id)) {
                    $this->stats['items_updated']++;
                }
            } else {
                if ($this->upsert_item($item)) {
                    $this->stats['items_created']++;
                }
            }
            $this->stats['items_processed']++;
        }
    }

    protected function handleDuplicates(array $uuids): void
    {
        $this->logger->info("Starting duplicate handling for " . count($uuids) . " UUIDs");

        foreach ($uuids as $uuid) {
            // Use get_posts to find all posts with this UUID
            $posts = get_posts([
                'post_type' => $this->get_post_type(),
                'posts_per_page' => -1,
                'post_status' => ['publish', 'draft', 'pending'],
                'meta_query' => [
                    [
                        'key' => $this->get_uuid_meta_key(),
                        'value' => $uuid,
                        'compare' => '='
                    ]
                ],
                'orderby' => 'ID',
                'order' => 'DESC'
            ]);

            if (count($posts) > 1) {
                $keep_post = array_shift($posts); // Keep the most recent post (highest ID)
                $this->logger->info("Found duplicate posts for UUID: $uuid. Keeping post ID: {$keep_post->ID}, deleting: " . implode(', ', array_map(fn($p) => $p->ID, $posts)));

                foreach ($posts as $post) {
                    $result = wp_delete_post($post->ID, true);
                    if ($result) {
                        $this->stats['items_deleted']++;
                    } else {
                        $this->logger->error("Failed to delete duplicate post ID: {$post->ID}");
                    }
                }
            }
        }

        $this->logger->info("Completed duplicate handling. Total deleted: {$this->stats['items_deleted']}");
    }

    protected function update_sync_success(): void
    {
        $this->stats['is_processing'] = false;
        $this->stats['last_sync'] = [
            'timestamp' => current_time('mysql'),
            'success' => true
        ];
    }

    protected function update_sync_failure(string $error): void
    {
        $this->stats['is_processing'] = false;
        $this->stats['last_sync'] = [
            'timestamp' => current_time('mysql'),
            'success' => false,
            'error' => $error
        ];
    }
}
