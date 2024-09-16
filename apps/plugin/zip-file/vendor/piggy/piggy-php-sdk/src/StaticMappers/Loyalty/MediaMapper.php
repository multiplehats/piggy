<?php

namespace Piggy\Api\StaticMappers\Loyalty;

use Piggy\Api\Models\Loyalty\Media;
use stdClass;

class MediaMapper
{
    public static function map(stdClass $data): Media
    {
        return new Media(
            $data->type,
            $data->value
        );
    }
}
