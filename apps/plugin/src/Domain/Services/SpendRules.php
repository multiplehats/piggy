<?php

namespace Leat\Domain\Services;

use Leat\Utils\Logger;

/**
 * Class SpendRules
 *
 * @internal
 */
class SpendRules {

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	public function __construct() {
		$this->logger = new Logger();
	}

	private function get_post_meta_data( $post_id, $key, $fallback_value = null ) {
		$value = get_post_meta( $post_id, $key, true );
		return empty( $value ) ? $fallback_value : $value;
	}

	/**
	 * Get spend rules by type.
	 *
	 * @param string|null $type The type of spend rule.
	 * @param array       $post_status The status of the spend rule.
	 * @return array|null The spend rules, or null if none found.
	 */
	public function get_spend_rules_by_type( $type, $post_status = [ 'publish' ] ) {
		$args = [
			'post_type'        => 'leat_spend_rule',
			'post_status'      => $post_status,
			'suppress_filters' => false,
		];

		if ( $type ) {
			$args['meta_query'] = [
				[
					'key'   => '_leat_spend_rule_type',
					'value' => $type,
				],
			];
		}

		$cache_key = 'leat_spend_rules_' . md5( wp_json_encode( $args ) );
		$posts     = wp_cache_get( $cache_key );

		if ( false === $posts ) {
			$posts = get_posts( $args );
			wp_cache_set( $cache_key, $posts, '', 3600 );
		}

		if ( empty( $posts ) ) {
			return null;
		}

		$posts = array_map( [ $this, 'get_formatted_post' ], $posts );

		return $posts;
	}

	/**
	 * Get a spend rule by its ID.
	 *
	 * @param int $id Spend Rule ID.
	 * @return array|null
	 */
	public function get_by_id( $id ) {
		$post = get_post( $id );

		if ( empty( $post ) ) {
			return null;
		}

		return $this->get_formatted_post( $post );
	}

	/**
	 * Convert a Spend Rule post into an object suitable for a WP REST API response.
	 *
	 * @param \WP_Post $post Spend Rule post object.
	 * @return array
	 */
	public function get_formatted_post( $post ) {
		$type = $this->get_post_meta_data( $post->ID, '_leat_spend_rule_type', null );

		$spend_rule = [
			'id'                 => (int) $post->ID,
			'createdAt'          => $post->post_date,
			'updatedAt'          => $post->post_modified,
			'status'             => [
				'id'          => 'status',
				'label'       => __( 'Status', 'leat-crm' ),
				'default'     => 'publish',
				'value'       => $post->post_status,
				'options'     => [
					'publish' => [ 'label' => __( 'Active', 'leat-crm' ) ],
					'draft'   => [ 'label' => __( 'Inactive', 'leat-crm' ) ],
				],
				'type'        => 'select',
				'description' => __( 'Set the status of the rule. Inactive spend rules will not be displayed to users.', 'leat-crm' ),
			],
			'title'              => [
				'id'          => 'title',
				'label'       => __( 'Title', 'leat-crm' ),
				'default'     => null,
				'value'       => $post->post_title,
				'type'        => 'text',
				'description' => __( 'This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat-crm' ),
			],
			'type'               => [
				'id'          => 'type',
				'label'       => __( 'Type', 'leat-crm' ),
				'default'     => 'FREE_PRODUCT',
				'value'       => $type,
				'type'        => 'select',
				'options'     => [
					'FREE_PRODUCT'   => [ 'label' => __( 'Free / Discounted Product', 'leat-crm' ) ],
					'ORDER_DISCOUNT' => [ 'label' => __( 'Order Discount', 'leat-crm' ) ],
					'FREE_SHIPPING'  => [ 'label' => __( 'Free Shipping', 'leat-crm' ) ],
					'CATEGORY'       => [ 'label' => __( 'Category Discount', 'leat-crm' ) ],
				],
				'description' => __( 'The type of spend rule.', 'leat-crm' ),
			],
			'startsAt'           => [
				'id'          => 'starts_at',
				'label'       => __( 'Starts at', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_starts_at', null ),
				'type'        => 'date',
				'description' => __( 'Optional date for when the rule should start.', 'leat-crm' ),
			],
			'expiresAt'          => [
				'id'          => 'expires_at',
				'label'       => __( 'Expires at', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_expires_at', null ),
				'type'        => 'date',
				'description' => __( 'Optional date for when the rule should expire.', 'leat-crm' ),
			],
			'completed'          => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_completed', null ),
			'creditCost'         => [
				'id'          => 'credit_cost',
				'label'       => __( 'Credit cost', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_credit_cost', null ),
				'type'        => 'number',
				'description' => __( 'The amount of credits it will cost to redeem the reward. This is managed in the Leat dashboard.', 'leat-crm' ),
			],
			'selectedReward'     => [
				'id'          => 'selected_reward',
				'label'       => __( 'Selected reward', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_selected_reward', null ),
				'type'        => 'text',
				'description' => __( 'The reward that is selected for the spend rule.', 'leat-crm' ),
			],
			'image'              => [
				'id'          => 'image',
				'label'       => __( 'Image', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_image', null ),
				'type'        => 'text',
				'description' => __( 'The image that is displayed for the spend rule.', 'leat-crm' ),
			],
			'description'        => [
				'id'          => 'description',
				'label'       => __( 'Description', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_description', null ),
				'type'        => 'translatable_text',
				'description' => $this->get_description_placeholder( $type ),
			],
			'instructions'       => [
				'id'          => 'instructions',
				'label'       => __( 'Instructions', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_instructions', null ),
				'type'        => 'translatable_text',
				'description' => $this->get_instructions_placeholder( $type ),
			],
			'fulfillment'        => [
				'id'          => 'fulfillment',
				'label'       => __( 'Fulfillment description', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_fulfillment', null ),
				'type'        => 'translatable_text',
				'description' => $this->get_fulfillment_placeholder( $type ),
			],
			'leatRewardUuid'     => [
				'id'          => 'leat_reward_uuid',
				'label'       => __( 'Leat Reward UUID', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_reward_uuid', null ),
				'type'        => 'text',
				'description' => __( 'The UUID of the corresponding Leat reward.', 'leat-crm' ),
			],
			'selectedCategories' => [
				'id'          => 'selected_categories',
				'label'       => __( 'Selected category', 'leat-crm' ),
				'default'     => [],
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_selected_categories', [] ),
				'type'        => 'categories_select',
				'description' => __( 'The category that the user can spent their credits in.', 'leat-crm' ),
			],
			'limitUsageToXItems' => [
				'id'          => 'limit_usage_to_x_items',
				'label'       => __( 'Limit usage to X items', 'leat-crm' ),
				'default'     => 1,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_limit_usage_to_x_items', 0 ),
				'type'        => 'number',
				'description' => __( 'Limit the discount to a specific number of items. Set to 0 for unlimited. If you set it to 0 be aware that this will allow the customer to use the discount on all items in the cart.', 'leat-crm' ),
			],
		];

		$spend_rule['label'] = [
			'id'          => 'label',
			'label'       => __( 'Label', 'leat-crm' ),
			'default'     => $this->get_default_label( $type ),
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_label' ),
			'type'        => 'translatable_text',
			'description' => $this->get_label_description( $type ),
		];

		$spend_rule['selectedProducts'] = [
			'id'          => 'selected_products',
			'label'       => __( 'Selected products', 'leat-crm' ),
			'default'     => [],
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_selected_products', [] ),
			'type'        => 'products_select',
			'description' => __( 'The products that are selected for the spend rule.', 'leat-crm' ),
		];

		$spend_rule['discountValue'] = [
			'id'          => 'discount_value',
			'label'       => __( 'Discount value', 'leat-crm' ),
			'default'     => 10,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_discount_value', null ),
			'type'        => 'number',
			'description' => __( 'The value of the discount.', 'leat-crm' ),
		];

		$spend_rule['discountType'] = [
			'id'          => 'discount_type',
			'label'       => __( 'Discount type', 'leat-crm' ),
			'default'     => 'percentage',
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_discount_type', 'percentage' ),
			'type'        => 'select',
			'options'     => [
				'percentage' => [ 'label' => __( 'Percentage', 'leat-crm' ) ],
				'fixed'      => [ 'label' => __( 'Fixed amount', 'leat-crm' ) ],
			],
			'description' => __( 'The type of discount.', 'leat-crm' ),
		];

		$spend_rule['minimumPurchaseAmount'] = [
			'id'          => 'minimum_purchase_amount',
			'label'       => __( 'Minimum purchase amount', 'leat-crm' ),
			'default'     => 0,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_minimum_purchase_amount', 0 ),
			'type'        => 'number',
			'description' => __( 'The minimum purchase amount required to redeem the reward.', 'leat-crm' ),
		];

		return $spend_rule;
	}

	private function get_label_description( $type ) {
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the label text. */
		return sprintf( __( "The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm' ), $placeholders );
	}

	private function get_default_label( $type ) {
		return [
			'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}',
		];
	}

	private function get_description_placeholder( $type ) {
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the description text. */
		return sprintf( __( 'Add a description of the reward. Available placeholders: %s', 'leat-crm' ), $placeholders );
	}

	private function get_instructions_placeholder( $type ) {
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the instructions text. */
		return sprintf( __( 'Add instructions on how to redeem the reward. Available placeholders: %s', 'leat-crm' ), $placeholders );
	}

	private function get_fulfillment_placeholder( $type ) {
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the fulfillment text. */
		return sprintf( __( 'Add instructions on how fulfillment will be handled. Available placeholders: %s', 'leat-crm' ), $placeholders );
	}

	public function delete_spend_rule_by_leat_uuid( $uuid ) {
		$posts = get_posts(
			[
				'post_type'              => 'leat_spend_rule',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'meta_key'               => '_leat_reward_uuid',
				'meta_value'             => $uuid,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
			);

		if ( ! empty( $posts ) ) {
			wp_delete_post( $posts[0] );
		}
	}

	/**
	 * Get the applicable spend rule for a given credit amount.
	 *
	 * @param int $credit_amount The available credit amount.
	 * @return array|null The applicable spend rule, or null if none found.
	 */
	public function get_applicable_spend_rule( $credit_amount ) {
		$spend_rules = $this->get_spend_rules_by_type( null );

		if ( ! $spend_rules ) {
			return null;
		}

		$applicable_rule     = null;
		$highest_credit_cost = 0;

		foreach ( $spend_rules as $rule ) {
			$credit_cost = $rule['creditCost']['value'] ?? PHP_INT_MAX;

			if ( $credit_amount >= $credit_cost && $credit_cost > $highest_credit_cost ) {
				$applicable_rule     = $rule;
				$highest_credit_cost = $credit_cost;
			}
		}

		return $applicable_rule;
	}

	public function create_or_update_spend_rule_from_reward( $reward, $existing_post_id = null ) {
		$post_data = array(
			'post_type'  => 'leat_spend_rule',
			'post_title' => $reward['title'],
			'meta_input' => array(
				'_leat_spend_rule_credit_cost'     => $reward['requiredCredits'],
				'_leat_reward_uuid'                => $reward['uuid'],
				'_leat_spend_rule_selected_reward' => $reward['uuid'],
			),
		);

		if ( isset( $reward['image'] ) ) {
			$post_data['meta_input']['_leat_spend_rule_image'] = $reward['image'];
		}

		if ( $existing_post_id ) {
			$post_data['ID'] = $existing_post_id;
			wp_update_post( $post_data );
		} else {
			// New rules are always draft by default.
			$post_data['post_status'] = 'draft';

			// _leat_spend_rule_type
			$post_data['meta_input']['_leat_spend_rule_type'] = $reward['type'];

			wp_insert_post( $post_data );
		}
	}

	public function get_spend_rule_by_leat_uuid( $uuid ) {
		$cache_key = 'leat_spend_rule_' . md5( $uuid );
		$posts     = wp_cache_get( $cache_key );

		if ( false === $posts ) {
			$posts = get_posts(
				[
					'post_type'      => 'leat_spend_rule',
					'meta_key'       => '_leat_reward_uuid',
					'meta_value'     => $uuid,
					'posts_per_page' => 1,
				]
				);
			wp_cache_set( $cache_key, $posts, '', 3600 );
		}

		if ( ! empty( $posts ) ) {
			return $this->get_formatted_post( $posts[0] );
		}

		return null;
	}

	public function delete_spend_rules_by_uuids( $uuids_to_delete ) {
		foreach ( $uuids_to_delete as $post_id => $uuid ) {
			wp_delete_post( $post_id, true );
		}
	}

	public function handle_duplicated_spend_rules( $uuids ) {
		global $wpdb;

		$this->logger->info( 'Handling duplicated spend rules for UUIDs: ' . implode( ', ', $uuids ) );

		foreach ( $uuids as $uuid ) {
			$query = $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s ORDER BY post_id DESC",
				'_leat_reward_uuid',
				$uuid
			);

			$cache_key = 'leat_duplicate_rules_' . md5( $uuid );
			$post_ids  = wp_cache_get( $cache_key );

			if ( false === $post_ids ) {
				// @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_ids = $wpdb->get_col( $query );
				wp_cache_set( $cache_key, $post_ids, '', 3600 );
			}

			if ( count( $post_ids ) > 1 ) {
				$keep_id = array_shift( $post_ids );
				$this->logger->info( "Keeping spend rule with post ID: $keep_id for UUID: $uuid" );

				foreach ( $post_ids as $post_id ) {
					$this->logger->info( "Deleting duplicate spend rule with post ID: $post_id for UUID: $uuid" );
					wp_delete_post( $post_id, true );
				}
			}
		}

		$this->logger->info( 'Finished handling duplicated spend rules' );
	}

	public function delete_spend_rules_with_empty_uuid() {
		$cache_key = 'leat_empty_uuid_rules';
		$posts     = wp_cache_get( $cache_key );

		if ( false === $posts ) {
			$posts = get_posts(
				[
					'post_type'      => 'leat_spend_rule',
					'posts_per_page' => -1,
					'post_status'    => [ 'publish', 'draft' ],
					'meta_query'     => [
						'relation' => 'OR',
						[
							'key'     => '_leat_reward_uuid',
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => '_leat_reward_uuid',
							'compare' => 'NOT EXISTS',
						],
					],
				]
				);
			wp_cache_set( $cache_key, $posts, '', 3600 );
		}

		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		return count( $posts );
	}

	public function get_spend_rule_by_id( $id ) {
		$post = get_post( $id );

		if ( ! $post ) {
			return null;
		}

		return $this->get_formatted_post( $post );
	}

	private function get_discount_type( $value ) {
		if ( 'percentage' === $value ) {
			return 'percent';
		} elseif ( 'fixed' === $value ) {
			return 'fixed_product';
		}

		return null;
	}

	public function create_coupon_for_spend_rule( $formatted_spend_rule, $user_id ) {
		$coupon_code = wp_generate_uuid4();

		$existing_coupon = new \WC_Coupon( $coupon_code );

		if ( $existing_coupon ) {
			$coupon_code = wp_generate_uuid4();
		}

		$coupon = new \WC_Coupon();
		$coupon->set_code( $coupon_code );
		$coupon->set_description( 'Leat Spend Rule: ' . $formatted_spend_rule['title']['value'] );
		$coupon->set_usage_limit( 1 );
		$coupon->set_individual_use( true );

		$coupon->add_meta_data( '_leat_spend_rule_coupon', 'true', true );
		$coupon->add_meta_data( '_leat_spend_rule_id', $formatted_spend_rule['id'], true );

		$discount_type = $this->get_discount_type( $formatted_spend_rule['discountType']['value'] );
		if ( $discount_type ) {
			$coupon->set_discount_type( $discount_type );
		}

		if ( $user_id ) {
			$user       = get_user_by( 'id', $user_id );
			$user_email = $user->user_email;

			$coupon->add_meta_data( '_leat_user_id', $user_id, true );
			$coupon->set_email_restrictions( [ $user_email ] );
		}

		switch ( $formatted_spend_rule['type']['value'] ) {
			case 'FREE_PRODUCT':
			case 'ORDER_DISCOUNT':
				$coupon->set_amount( 0 );
				break;

			case 'FREE_SHIPPING':
				$coupon->set_free_shipping( true );
				break;

			case 'CATEGORY':
				$coupon->set_amount( $formatted_spend_rule['discountValue']['value'] );

				// Set product categories.
				if ( ! empty( $formatted_spend_rule['selectedCategories']['value'] ) ) {
					$coupon->set_product_categories( $formatted_spend_rule['selectedCategories']['value'] );
				}

				// Add limit usage to X items if set.
				if ( isset( $formatted_spend_rule['limitUsageToXItems']['value'] ) ) {
					$limit = $formatted_spend_rule['limitUsageToXItems']['value'];

					if ( $limit ) {
						$coupon->set_limit_usage_to_x_items( intval( $limit ) );
					} else {
						// By default, always limit usage to 1 item.
						$coupon->set_limit_usage_to_x_items( 1 );
					}
				}

				break;
		}

		// Check for minimum purchase amount.
		if ( isset( $formatted_spend_rule['minimumPurchaseAmount'] ) &&
			is_numeric( $formatted_spend_rule['minimumPurchaseAmount']['value'] ) ) {
			$min_amount = floatval( $formatted_spend_rule['minimumPurchaseAmount']['value'] );
			if ( $min_amount > 0 ) {
				$coupon->set_minimum_amount( $min_amount );
			}
		}

		$coupon->save();

		return $coupon_code;
	}

	/**
	 * Query coupons by user ID.
	 *
	 * @param int $user_id The user ID.
	 * @return array The list of coupons associated with the user ID.
	 */
	public function get_coupons_by_user_id( $user_id ) {
		$args = [
			'post_type'      => 'shop_coupon',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_leat_user_id',
					'value'   => $user_id,
					'compare' => '=',
				],
			],
		];

		$coupons = get_posts( $args );

		$coupon_codes = [];

		foreach ( $coupons as $coupon ) {
			$spend_rule_id = get_post_meta( $coupon->ID, '_leat_spend_rule_id', true );
			$spend_rule    = $this->get_spend_rule_by_id( $spend_rule_id );

			if ( ! $spend_rule ) {
				continue;
			}

			$coupon_codes[] = array(
				'code'       => $coupon->post_title,
				'spend_rule' => $spend_rule,
			);
		}

		return $coupon_codes;
	}
}
