<?php

namespace Leat\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Leat\Api\Connection;
use Leat\Settings;
use Leat\Assets\Api as AssetApi;

abstract class AbstractBlockIntegration implements IntegrationInterface
{
    protected AssetApi $asset_api;
    protected Connection $connection;
    protected Settings $settings;

    public function __construct(AssetApi $asset_api, Connection $connection, Settings $settings)
    {
        $this->asset_api = $asset_api;
        $this->connection = $connection;
        $this->settings = $settings;
    }

    abstract public function get_name(): string;

    public function initialize(): void
    {
        $this->register_block_assets();
    }

    protected function register_block_assets(): void
    {
        $block_name = $this->get_name();

        try {
            $asset_path = plugin_dir_path(LEAT_PLUGIN_FILE) . "src/Blocks/{$block_name}Block/build/index.asset.php";

            if (!file_exists($asset_path)) {
                return;
            }

            $asset_file = require $asset_path;

            wp_register_script(
                "leat-{$block_name}",
                plugins_url("src/Blocks/{$block_name}Block/build/index.js", LEAT_PLUGIN_FILE),
                $asset_file['dependencies'],
                $asset_file['version'],
                true
            );

            wp_register_style(
                "leat-{$block_name}-style",
                plugins_url("src/Blocks/{$block_name}Block/build/style.css", LEAT_PLUGIN_FILE),
                [],
                $asset_file['version']
            );
        } catch (\Exception $e) {
            // Log error or handle gracefully
            return;
        }
    }

    public function get_script_handles(): array
    {
        return ["leat-{$this->get_name()}"];
    }

    public function get_editor_script_handles(): array
    {
        return [];
    }

    public function get_script_data(): array
    {
        return [];
    }
}
