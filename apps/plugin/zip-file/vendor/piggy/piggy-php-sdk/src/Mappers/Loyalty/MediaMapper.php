<?php

namespace Piggy\Api\Mappers\Loyalty;

use Piggy\Api\Models\Loyalty\Media;
use stdClass;

class MediaMapper
{
    public function map(stdClass $data): Media
    {
        return new Media(
            $data->type,
            $data->value
        );
    }
}
