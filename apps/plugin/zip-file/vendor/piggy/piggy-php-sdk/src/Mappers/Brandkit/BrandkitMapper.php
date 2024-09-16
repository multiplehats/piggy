<?php

namespace Piggy\Api\Mappers\Brandkit;

use Piggy\Api\Models\Brandkit\Brandkit;
use stdClass;

class BrandkitMapper
{
    public function map(stdClass $data): Brandkit
    {
        return new Brandkit(
            $data->small_logo_url ?? null,
            $data->large_logo_url ?? null,
            $data->cover_image_url ?? null,
            $data->primary_color ?? null,
            $data->secondary_color ?? null,
            $data->tertiary_color ?? null,
            $data->quaternary_color ?? null,
            $data->font_color ?? null,
            $data->description ?? null,
            $data->corner_theme ?? null,
            $data->font_family ?? null
        );
    }
}
