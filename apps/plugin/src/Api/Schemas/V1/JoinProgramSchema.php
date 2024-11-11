<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * JoinProgram schema class.
 *
 * @internal
 */
class JoinProgramSchema extends AbstractSchema {
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'join-program';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'join-program';

    /**
     * API key schema properties.
     *
     * @return array
     */
    public function get_properties() {
        return [
            'user_id' => [
                'description' => __('The ID of the user joining the program', 'leat-crm'),
                'type'        => 'integer',
                'required'    => true,
            ],
        ];
    }

    /**
     * Convert a join program result into an object suitable for the response.
     *
     * @param array $result Join program result.
     * @return array
     */
    public function get_item_response($result) {
        return [
            'success' => $result['success'],
            'message' => $result['message'],
        ];
    }
}