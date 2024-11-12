<?php
namespace Leat;

use Leat\Api\Connection;

final class PostTypeController {
	const PREFIX = 'leat';

	/**
	 * The Connection instance.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->connection = new Connection();
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action('init', array($this, 'register_earn_rules_post_type'));
		add_action('init', array($this, 'register_spend_rules_post_type'));
		add_action('init', array($this, 'schedule_daily_reward_sync'));
		// add_action('leat_daily_reward_sync', array($this, 'sync_rewards_cron'));
	}

	/**
	 * Register Earn Rules post type.
	 */
	public function register_earn_rules_post_type() {
		$labels = array(
			'name'                  => _x('Earn Rules', 'Post type general name', 'leat-crm'),
			'singular_name'         => _x('Earn Rule', 'Post type singular name', 'leat-crm'),
			'menu_name'             => _x('Earn Rules', 'Admin Menu text', 'leat-crm'),
			'name_admin_bar'        => _x('Earn Rule', 'Add New on Toolbar', 'leat-crm'),
			'add_new'               => __('Add New', 'leat-crm'),
			'add_new_item'          => __('Add New Earn Rule', 'leat-crm'),
			'new_item'              => __('New Earn Rule', 'leat-crm'),
			'edit_item'             => __('Edit Earn Rule', 'leat-crm'),
			'view_item'             => __('View Earn Rule', 'leat-crm'),
			'all_items'             => __('All Earn Rules', 'leat-crm'),
			'search_items'          => __('Search Earn Rules', 'leat-crm'),
			'parent_item_colon'     => __('Parent Earn Rules:', 'leat-crm'),
			'not_found'             => __('No earn rules found.', 'leat-crm'),
			'not_found_in_trash'    => __('No earn rules found in Trash.', 'leat-crm'),
			'featured_image'        => _x('Earn Rule Cover Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'archives'              => _x('Earn Rule archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'leat-crm'),
			'insert_into_item'      => _x('Insert into earn rule', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'leat-crm'),
			'uploaded_to_this_item' => _x('Uploaded to this earn rule', 'Overrides the "Uploaded to this post/page" phrase (used when viewing media attached to a post). Added in 4.4', 'leat-crm'),
			'filter_items_list'     => _x('Filter earn rules list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'leat-crm'),
			'items_list_navigation' => _x('Earn rules list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'leat-crm'),
			'items_list'            => _x('Earn rules list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'leat-crm'),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false, // Change this to false
			'query_var'          => true,
			'rewrite'            => array('slug' => self::PREFIX . '_earn_rule'),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array('title', 'editor', 'custom-fields'),
		);

		register_post_type(self::PREFIX . '_earn_rule', $args);
	}

	public function register_spend_rules_post_type() {
		$labels = array(
			'name'                  => _x('Spend Rules', 'Post type general name', 'leat-crm'),
			'singular_name'         => _x('Spend Rule', 'Post type singular name', 'leat-crm'),
			'menu_name'             => _x('Spend Rules', 'Admin Menu text', 'leat-crm'),
			'name_admin_bar'        => _x('Spend Rule', 'Add New on Toolbar', 'leat-crm'),
			'add_new'               => __('Add New', 'leat-crm'),
			'add_new_item'          => __('Add New Spend Rule', 'leat-crm'),
			'new_item'              => __('New Spend Rule', 'leat-crm'),
			'edit_item'             => __('Edit Spend Rule', 'leat-crm'),
			'view_item'             => __('View Spend Rule', 'leat-crm'),
			'all_items'             => __('All Spend Rules', 'leat-crm'),
			'search_items'          => __('Search Spend Rules', 'leat-crm'),
			'parent_item_colon'     => __('Parent Spend Rules:', 'leat-crm'),
			'not_found'             => __('No spend rules found.', 'leat-crm'),
			'not_found_in_trash'    => __('No spend rules found in Trash.', 'leat-crm'),
			'featured_image'        => _x('Spend Rule Cover Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'leat-crm'),
			'archives'              => _x('Spend Rule archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'leat-crm'),
			'insert_into_item'      => _x('Insert into spend rule', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'leat-crm'),
			'uploaded_to_this_item' => _x('Uploaded to this spend rule', 'Overrides the "Uploaded to this post/page" phrase (used when viewing media attached to a post). Added in 4.4', 'leat-crm'),
			'filter_items_list'     => _x('Filter spend rules list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'leat-crm'),
			'items_list_navigation' => _x('Spend rules list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'leat-crm'),
			'items_list'            => _x('Spend rules list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'leat-crm'),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false, // Change this to false
			'query_var'          => true,
			'rewrite'            => array('slug' => self::PREFIX . '_spend_rule'),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array('title', 'editor', 'custom-fields'),
		);

		register_post_type(self::PREFIX . '_spend_rule', $args);
	}

	public function schedule_daily_reward_sync() {
		if (!wp_next_scheduled('leat_daily_reward_sync')) {
			wp_schedule_event(time(), 'daily', 'leat_daily_reward_sync');
		}
	}

	public function sync_rewards_cron() {
		$this->connection->sync_rewards_with_spend_rules();
	}
}