<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

class WCCategoriesSearchSchema extends AbstractSchema {
	protected $title = 'wc-categories';
	const IDENTIFIER = 'wc-categories';

	public function get_properties() {
		return [
			'id'    => [
				'description' => __( 'Unique identifier for the category', 'leat-crm' ),
				'type'        => 'integer',
			],
			'title' => [
				'description' => __( 'Title of the category', 'leat-crm' ),
				'type'        => 'string',
			],
		];
	}

	/**
	 * Get the item response
	 *
	 * @param \WP_Term $term
	 * @return array
	 */
	public function get_item_response( $term ) {
		if ( ! $term || is_wp_error( $term ) ) {
			return [];
		}

		return [
			'id'    => $term->term_id,
			'title' => $term->name,
		];
	}
}
