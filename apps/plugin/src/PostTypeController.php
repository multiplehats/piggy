<?php
namespace PiggyWP;

final class PostTypeController {
	const PREFIX = 'piggy';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action('init', array($this, 'register_earn_rules_post_type'));
		add_action('add_meta_boxes', array($this, 'add_earn_rules_metaboxes'));
		add_action('save_post', array($this, 'save_earn_rule_meta'), 10, 2);
		// add_filter('wp_insert_post_data', array($this, 'restrict_post_status'), 10, 2);
	}

	/**
	 * Register Earn Rules post type.
	 */
	public function register_earn_rules_post_type() {
		$labels = array(
			'name'                  => _x('Earn Rules', 'Post type general name', 'piggy'),
			'singular_name'         => _x('Earn Rule', 'Post type singular name', 'piggy'),
			'menu_name'             => _x('Earn Rules', 'Admin Menu text', 'piggy'),
			'name_admin_bar'        => _x('Earn Rule', 'Add New on Toolbar', 'piggy'),
			'add_new'               => __('Add New', 'piggy'),
			'add_new_item'          => __('Add New Earn Rule', 'piggy'),
			'new_item'              => __('New Earn Rule', 'piggy'),
			'edit_item'             => __('Edit Earn Rule', 'piggy'),
			'view_item'             => __('View Earn Rule', 'piggy'),
			'all_items'             => __('All Earn Rules', 'piggy'),
			'search_items'          => __('Search Earn Rules', 'piggy'),
			'parent_item_colon'     => __('Parent Earn Rules:', 'piggy'),
			'not_found'             => __('No earn rules found.', 'piggy'),
			'not_found_in_trash'    => __('No earn rules found in Trash.', 'piggy'),
			'featured_image'        => _x('Earn Rule Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'piggy'),
			'set_featured_image'    => _x('Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'piggy'),
			'remove_featured_image' => _x('Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'piggy'),
			'use_featured_image'    => _x('Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'piggy'),
			'archives'              => _x('Earn Rule archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'piggy'),
			'insert_into_item'      => _x('Insert into earn rule', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'piggy'),
			'uploaded_to_this_item' => _x('Uploaded to this earn rule', 'Overrides the “Uploaded to this post/page” phrase (used when viewing media attached to a post). Added in 4.4', 'piggy'),
			'filter_items_list'     => _x('Filter earn rules list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'piggy'),
			'items_list_navigation' => _x('Earn rules list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'piggy'),
			'items_list'            => _x('Earn rules list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'piggy'),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
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

	/**
	 * Restrict post status to draft and publish.
	 */
	public function restrict_post_status($data, $postarr) {
		if ($data['post_type'] == self::PREFIX . '_earn_rule' && !in_array($data['post_status'], array('draft', 'publish'))) {
			$data['post_status'] = 'draft';
		}
		return $data;
	}

	/**
	 * Add Earn Rules metaboxes.
	 */
	public function add_earn_rules_metaboxes() {
		add_meta_box(
			'earn_rule_details',
			__('Earn Rule Details', 'piggy'),
			array($this, 'render_earn_rule_details_metabox'),
			self::PREFIX . '_earn_rule',
			'normal',
			'high'
		);
	}

	/**
	 * Render Earn Rule Details metabox.
	 */
	public function render_earn_rule_details_metabox($post) {
		wp_nonce_field('save_earn_rule_meta', 'earn_rule_meta_nonce');

		$title = get_post_meta($post->ID, '_piggy_earn_rule_title', true);
		$type = get_post_meta($post->ID, '_piggy_earn_rule_type', true);
		$description = get_post_meta($post->ID, '_piggy_earn_rule_description', true);
		$startsAt = get_post_meta($post->ID, '_piggy_earn_rule_starts_at', true);
		$expiresAt = get_post_meta($post->ID, '_piggy_earn_rule_expires_at', true);
		$completed = get_post_meta($post->ID, '_piggy_earn_rule_completed', true);

		echo '<p><label for="piggy_earn_rule_type">' . __('Type', 'piggy') . '</label>';
		echo '<select name="piggy_earn_rule_type" id="piggy_earn_rule_type">';
		echo '<option value="LIKE_ON_FACEBOOK"' . selected($type, 'LIKE_ON_FACEBOOK', false) . '>' . __('Like on Facebook', 'piggy') . '</option>';
		echo '<option value="FOLLOW_ON_TIKTOK"' . selected($type, 'FOLLOW_ON_TIKTOK', false) . '>' . __('Follow on TikTok', 'piggy') . '</option>';
		echo '<option value="PLACE_ORDER"' . selected($type, 'PLACE_ORDER', false) . '>' . __('Place Order', 'piggy') . '</option>';
		echo '<option value="CELEBRATE_BIRTHDAY"' . selected($type, 'CELEBRATE_BIRTHDAY', false) . '>' . __('Celebrate Birthday', 'piggy') . '</option>';
		echo '<option value="FOLLOW_ON_INSTAGRAM"' . selected($type, 'FOLLOW_ON_INSTAGRAM', false) . '>' . __('Follow on Instagram', 'piggy') . '</option>';
		echo '<option value="CREATE_ACCOUNT"' . selected($type, 'CREATE_ACCOUNT', false) . '>' . __('Create Account', 'piggy') . '</option>';
		echo '</select></p>';

		echo '<p><label for="piggy_earn_rule_title">' . __('Customer facing title', 'piggy') . '</label>';
		echo '<input type="text" name="piggy_earn_rule_title" id="piggy_earn_rule_title" value="' . esc_attr($title) . '"></p>';

		echo '<p><label for="piggy_earn_rule_description">' . __('Description', 'piggy') . '</label>';
		echo '<textarea name="piggy_earn_rule_description" id="piggy_earn_rule_description" rows="4" cols="50">' . esc_textarea($description) . '</textarea></p>';

		echo '<p><label for="piggy_earn_rule_starts_at">' . __('Starts At', 'piggy') . '</label>';
		echo '<input type="datetime-local" name="piggy_earn_rule_starts_at" id="piggy_earn_rule_starts_at" value="' . esc_attr($startsAt) . '"></p>';

		echo '<p><label for="piggy_earn_rule_expires_at">' . __('Expires At', 'piggy') . '</label>';
		echo '<input type="datetime-local" name="piggy_earn_rule_expires_at" id="piggy_earn_rule_expires_at" value="' . esc_attr($expiresAt) . '"></p>';

		echo '<p><label for="piggy_earn_rule_completed">' . __('Completed', 'piggy') . '</label>';
		echo '<input type="checkbox" name="piggy_earn_rule_completed" id="piggy_earn_rule_completed" value="1"' . checked($completed, '1', false) . '></p>';

		$type_fields = array(
			'LIKE_ON_FACEBOOK' => array('credits', 'socialHandle'),
			'FOLLOW_ON_TIKTOK' => array('credits', 'socialHandle'),
			'FOLLOW_ON_INSTAGRAM' => array('credits', 'socialHandle'),
			'PLACE_ORDER' => array('excludedCollectionIds', 'excludedProductIds', 'minimumOrderAmount'),
			'CELEBRATE_BIRTHDAY' => array('credits'),
			'CREATE_ACCOUNT' => array('credits')
		);

		foreach ($type_fields as $rule_type => $fields) {
			echo '<div id="type_fields_' . $rule_type . '" style="display: ' . ($type === $rule_type ? 'block' : 'none') . ';">';
			foreach ($fields as $field) {
				$value = get_post_meta($post->ID, '_piggy_earn_rule_' . $field, true);
				$label = ucwords(str_replace('_', ' ', $field));
				echo '<p><label for="piggy_earn_rule_' . $field . '">' . __($label, 'piggy') . '</label>';
				if ($field === 'excludedCollectionIds' || $field === 'excludedProductIds') {
					echo '<textarea name="piggy_earn_rule_' . $field . '[]" id="piggy_earn_rule_' . $field . '" rows="4" cols="50">' . esc_textarea(is_array($value) ? implode(', ', $value) : $value) . '</textarea>';
				} else {
					echo '<input type="' . ($field === 'credits' || $field === 'minimumOrderAmount' ? 'number' : 'text') . '" name="piggy_earn_rule_' . $field . '" id="piggy_earn_rule_' . $field . '" value="' . esc_attr($value) . '"></p>';
				}
			}
			echo '</div>';
		}

		// Add some JavaScript to show/hide fields based on the selected type
		echo '<script>
			(function($) {
				$("#piggy_earn_rule_type").change(function() {
					var selectedType = $(this).val();
					$.each(' . json_encode(array_keys($type_fields)) . ', function(index, value) {
						$("#type_fields_" + value).hide();
					});
					$("#type_fields_" + selectedType).show();
				}).change();
			})(jQuery);
		</script>';
	}

	/**
	 * Save Earn Rule metadata.
	 */
	public function save_earn_rule_meta($post_id, $post) {
		if (!isset($_POST['earn_rule_meta_nonce']) || !wp_verify_nonce($_POST['earn_rule_meta_nonce'], 'save_earn_rule_meta')) {
			return $post_id;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		if ($post->post_type != self::PREFIX . '_earn_rule') {
			return $post_id;
		}

		$type = sanitize_text_field($_POST['piggy_earn_rule_type']);
		$description = sanitize_textarea_field($_POST['piggy_earn_rule_description']);
		$startsAt = sanitize_text_field($_POST['piggy_earn_rule_starts_at']);
		$expiresAt = sanitize_text_field($_POST['piggy_earn_rule_expires_at']);
		$completed = isset($_POST['piggy_earn_rule_completed']) ? '1' : '0';

		update_post_meta($post_id, '_piggy_earn_rule_type', $type);
		update_post_meta($post_id, '_piggy_earn_rule_description', $description);
		update_post_meta($post_id, '_piggy_earn_rule_starts_at', $startsAt);
		update_post_meta($post_id, '_piggy_earn_rule_expires_at', $expiresAt);
		update_post_meta($post_id, '_piggy_earn_rule_completed', $completed);

		$type_fields = array(
			'LIKE_ON_FACEBOOK' => array('credits', 'socialHandle'),
			'FOLLOW_ON_TIKTOK' => array('credits', 'socialHandle'),
			'FOLLOW_ON_INSTAGRAM' => array('credits', 'socialHandle'),
			'PLACE_ORDER' => array('excludedCollectionIds', 'excludedProductIds', 'minimumOrderAmount'),
			'CELEBRATE_BIRTHDAY' => array('credits'),
			'CREATE_ACCOUNT' => array('credits')
		);

		foreach ($type_fields as $rule_type => $fields) {
			if ($type === $rule_type) {
				foreach ($fields as $field) {
					if ($field === 'excludedCollectionIds' || $field === 'excludedProductIds') {
						$value = array_map('sanitize_text_field', explode(',', $_POST['piggy_earn_rule_' . $field]));
					} else {
						$value = sanitize_text_field($_POST['piggy_earn_rule_' . $field]);
					}
					update_post_meta($post_id, '_piggy_earn_rule_' . $field, $value);
				}
			}
		}
	}
}
