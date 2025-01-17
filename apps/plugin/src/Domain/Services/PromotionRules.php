<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Utils\Logger;
use Piggy\Api\Models\Vouchers\Voucher;

/**
 * Class PromotionRules
 */
class PromotionRules {
	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Connection instance.
	 *
	 * @var Connection
	 */
	private $connection;

	public function __construct( Connection $connection ) {
		$this->logger     = new Logger();
		$this->connection = $connection;
	}

	private function get_post_meta_data( $post_id, $key, $fallback_value = null ) {
		$value = get_post_meta( $post_id, $key, true );

		return empty( $value ) ? $fallback_value : $value;
	}

	/**
	 * Get a promotion rule by its ID.
	 *
	 * @param int $id Promotion Rule ID.
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
	 * Convert a Promotion Rule post into an object suitable for a WP REST API response.
	 *
	 * @param \WP_Post $post Promotion Rule post object.
	 * @return array
	 */
	public function get_formatted_post( $post ) {
		$promotion_rule = [
			'id'                => (int) $post->ID,
			'createdAt'         => $post->post_date,
			'updatedAt'         => $post->post_modified,
			'status'            => [
				'id'          => 'status',
				'label'       => __( 'Status', 'leat-crm' ),
				'default'     => 'publish',
				'value'       => $post->post_status,
				'options'     => [
					'publish' => [ 'label' => __( 'Active', 'leat-crm' ) ],
					'draft'   => [ 'label' => __( 'Inactive', 'leat-crm' ) ],
				],
				'type'        => 'select',
				'description' => __( 'Set the status of the rule. Inactive promotion rules will not be displayed to users.', 'leat-crm' ),
			],
			'label'             => [
				'id'          => 'label',
				'label'       => __( 'Label', 'leat-crm' ),
				'default'     => $this->get_default_label(),
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_spend_rule_label' ),
				'type'        => 'translatable_text',
				'description' => $this->get_label_description(),
			],
			'title'             => [
				'id'          => 'title',
				'label'       => __( 'Title', 'leat-crm' ),
				'default'     => null,
				'value'       => $post->post_title,
				'type'        => 'text',
				'description' => __( 'This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat-crm' ),
			],
			'leatPromotionUuid' => [
				'id'          => 'leat_promotion_uuid',
				'label'       => __( 'Leat Promotion UUID', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_uuid', null ),
				'type'        => 'text',
				'description' => __( 'The UUID of the corresponding Leat promotion.', 'leat-crm' ),
			],
			'image'             => [
				'id'          => 'image',
				'label'       => __( 'Image', 'leat-crm' ),
				'default'     => null,
				'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_image', null ),
				'type'        => 'text',
				'description' => __( 'The image that is displayed for the promotion rule.', 'leat-crm' ),
			],
		];

		$promotion_rule['selectedProducts'] = [
			'id'          => 'selected_products',
			'label'       => __( 'Selected products', 'leat-crm' ),
			'optional'    => true,
			'default'     => [],
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_selected_products', [] ),
			'type'        => 'products_select',
			'description' => __( 'The products that are selected for the promotion rule.', 'leat-crm' ),
		];

		$promotion_rule['discountValue'] = [
			'id'          => 'discount_value',
			'label'       => __( 'Discount value', 'leat-crm' ),
			'default'     => 10,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_discount_value', null ),
			'type'        => 'number',
			'description' => __( 'The value of the discount.', 'leat-crm' ),
		];

		$promotion_rule['discountType'] = [
			'id'          => 'discount_type',
			'label'       => __( 'Discount type', 'leat-crm' ),
			'default'     => 'percentage',
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_discount_type', 'percentage' ),
			'type'        => 'select',
			'options'     => [
				'percentage' => [ 'label' => __( 'Percentage', 'leat-crm' ) ],
				'fixed'      => [ 'label' => __( 'Fixed amount', 'leat-crm' ) ],
			],
			'description' => __( 'The type of discount.', 'leat-crm' ),
		];

		$promotion_rule['minimumPurchaseAmount'] = [
			'id'          => 'minimum_purchase_amount',
			'label'       => __( 'Minimum purchase amount', 'leat-crm' ),
			'default'     => 0,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_minimum_purchase_amount', 0 ),
			'type'        => 'number',
			'description' => __( 'The minimum purchase amount required to redeem the promotion.', 'leat-crm' ),
		];

		$promotion_rule['voucherLimit'] = [
			'id'          => 'voucher_limit',
			'label'       => __( 'Voucher limit', 'leat-crm' ),
			'default'     => 0,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_voucher_limit', 0 ),
			'type'        => 'number',
			'description' => __( 'The maximum number of vouchers that can be issued for this promotion. 0 means unlimited.', 'leat-crm' ),
		];

		$promotion_rule['limitPerContact'] = [
			'id'          => 'limit_per_contact',
			'label'       => __( 'Limit Per Contact', 'leat-crm' ),
			'default'     => 0,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_limit_per_contact', 1 ),
			'type'        => 'number',
			'description' => __( 'The maximum number of times a single contact can use this promotion. 0 means unlimited.', 'leat-crm' ),
		];

		$promotion_rule['expirationDuration'] = [
			'id'          => 'expiration_duration',
			'label'       => __( 'Expiration Duration', 'leat-crm' ),
			'default'     => 0,
			'value'       => $this->get_post_meta_data( $post->ID, '_leat_promotion_rule_expiration_duration', 0 ),
			'type'        => 'number',
			'description' => __( 'The number of days after which the promotion expires. 0 means no expiration.', 'leat-crm' ),
		];

		return $promotion_rule;
	}

	public function delete_promotion_rule_by_leat_uuid( $uuid ) {
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => '_leat_promotion_uuid',
					'value'   => $uuid,
					'compare' => '=',
				),
			),
		);

		$posts = get_posts( $args );

		if ( ! empty( $posts ) ) {
			wp_delete_post( $posts[0]->ID );
		}
	}

	private function get_label_description() {
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		return sprintf(
			/* translators: %s: List of available placeholders that can be used in the promotion label text */
			__( "The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm' ),
			$placeholders
		);
	}

	private function get_default_label() {
		return [
			'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}',
		];
	}

	public function create_or_update_promotion_rule_from_promotion( $promotion, $existing_post_id = null ) {
		$post_data = array(
			'post_type'  => 'leat_promotion_rule',
			'post_title' => $promotion['title'],
			'meta_input' => array(
				'_leat_promotion_uuid'                     => $promotion['uuid'],
				'_leat_promotion_rule_voucher_limit'       => $promotion['voucherLimit'],
				'_leat_promotion_rule_limit_per_contact'   => $promotion['limitPerContact'],
				'_leat_promotion_rule_expiration_duration' => $promotion['expirationDuration'],
			),
		);

		if ( isset( $promotion['image'] ) ) {
			$post_data['meta_input']['_leat_promotion_rule_image'] = $promotion['image'];
		}

		if ( $existing_post_id ) {
			$post_data['ID'] = $existing_post_id;
			wp_update_post( $post_data );
		} else {
			// New rules are always draft by default.
			$post_data['post_status'] = 'draft';

			wp_insert_post( $post_data );
		}
	}

	public function get_promotion_rule_by_leat_uuid( $uuid ) {
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => '_leat_promotion_uuid',
					'value'   => $uuid,
					'compare' => '=',
				),
			),
		);

		$posts = get_posts( $args );

		if ( ! empty( $posts ) ) {
			return $this->get_formatted_post( $posts[0] );
		}

		return null;
	}

	public function delete_promotion_rules_by_uuids( $uuids_to_delete ) {
		foreach ( $uuids_to_delete as $post_id => $uuid ) {
			wp_delete_post( $post_id, true );
		}
	}

	public function handle_duplicated_promotion_rules( $uuids ) {
		$this->logger->info( 'Handling duplicated promotion rules for UUIDs: ' . implode( ', ', $uuids ) );

		foreach ( $uuids as $uuid ) {
			$args = array(
				'post_type'      => 'leat_promotion_rule',
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_leat_promotion_uuid',
						'value'   => $uuid,
						'compare' => '=',
					),
				),
			);

			$post_ids = get_posts( $args );

			if ( count( $post_ids ) > 1 ) {
				$keep_id = array_shift( $post_ids );
				$this->logger->info( "Keeping promotion rule with post ID: $keep_id for UUID: $uuid" );

				foreach ( $post_ids as $post_id ) {
					$this->logger->info( "Deleting duplicate promotion rule with post ID: $post_id for UUID: $uuid" );
					wp_delete_post( $post_id, true );
				}
			}
		}

		$this->logger->info( 'Finished handling duplicated promotion rules' );
	}

	public function delete_promotion_rules_with_empty_uuid() {
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft' ),
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_leat_promotion_uuid',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => '_leat_promotion_uuid',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		return count( $posts );
	}

	public function get_promotion_rule_by_id( $id ) {
		$post = get_post( $id );

		if ( ! $post ) {
			return null;
		}

		return $this->get_formatted_post( $post );
	}

	/**
	 * Get all active promotion rules.
	 *
	 * @return array
	 */
	public function get_active_promotion_rules() {
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		$formatted_posts = [];
		$posts           = get_posts( $args );

		foreach ( $posts as $post ) {
			$uuid = get_post_meta( $post->ID, '_leat_promotion_uuid', true );

			if ( $uuid ) {
				$formatted_posts[] = $this->get_formatted_post( $post );
			}
		}

		return $formatted_posts;
	}

	private function get_discount_type( $value ) {
		if ( 'percentage' === $value ) {
			return 'percent';
		} elseif ( 'fixed' === $value ) {
			return 'fixed_product';
		}

		return null;
	}

	public function upsert_coupon_for_promotion_rule( $formatted_rule, Voucher $voucher, ) {
		$voucher_data = $this->connection->format_voucher( $voucher );

		try {
			// Try to load existing coupon.
			$coupon = new \WC_Coupon( $voucher_data['code'] );
		} catch ( \Exception $e ) {
			// Coupon doesn't exist, create new one.
			$coupon = new \WC_Coupon();

			$coupon->set_code( strtoupper( $voucher_data['code'] ) );
		}

		// WE set a descripotion for internal use.
		$descripotion = sprintf(
			/* translators: %s: The promotion rule name */
			__( 'Leat Promotion Voucher: %s', 'leat-crm' ),
			$voucher_data['name']
		);
		$coupon->set_description( $descripotion );

		$contact_uuid = $voucher_data['contact_uuid'];
		$wp_user      = $this->connection->get_user_from_leat_uuid( $contact_uuid );
		$is_redeemed  = $voucher_data['is_redeemed'];

		// If we have a wp user and the voucher is redeemed, set the coupon status to trash.
		if ( $wp_user && $is_redeemed ) {
			$coupon->set_used_by( [ $wp_user->user_email ] );
		}

		// Each voucher can only be used once.
		$coupon->set_individual_use( true );
		$coupon->set_usage_limit( 1 );

		$discount_type = $this->get_discount_type( $formatted_rule['discountType']['value'] );
		if ( $discount_type ) {
			$coupon->set_discount_type( $discount_type );
		}

		$discount_value = $formatted_rule['discountValue']['value'];
		if ( $discount_value ) {
			$coupon->set_amount( $discount_value );
		}

		if ( $voucher_data['expiration_date'] ) {
			$coupon->set_date_expires( strtotime( $voucher_data['expiration_date'] ) );
		}

		$coupon->update_meta_data( '_leat_voucher_uuid', $voucher_data['uuid'] );
		$coupon->update_meta_data( '_leat_promotion_uuid', $voucher_data['promotion']['uuid'] );

		// Check for minimum purchase amount.
		if ( isset( $formatted_rule['minimumPurchaseAmount'] ) &&
			is_numeric( $formatted_rule['minimumPurchaseAmount']['value'] ) ) {
			$min_amount = floatval( $formatted_rule['minimumPurchaseAmount']['value'] );
			if ( $min_amount > 0 ) {
				$coupon->set_minimum_amount( $min_amount );
			}
		}

		if ( isset( $voucher_data['custom_attributes'] ) ) {
			foreach ( $voucher_data['custom_attributes'] as $key => $value ) {
				$coupon->update_meta_data( "_leat_custom_attribute_{$key}", $value );
			}
		}

		// Handle contact-specific restrictions.
		if ( isset( $voucher_data['contact_uuid'] ) ) {
			$coupon->update_meta_data( '_leat_contact_uuid', $contact_uuid );

			if ( $wp_user ) {
				$coupon->set_email_restrictions( [ $wp_user->user_email ] );
			}
		}

		$coupon->save();
	}

}
