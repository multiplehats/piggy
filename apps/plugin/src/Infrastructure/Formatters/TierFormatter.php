<?php

namespace Leat\Infrastructure\Formatters;

use Piggy\Api\Models\Tiers\Tier;

class TierFormatter
{
    public function format(Tier $tier)
    {
        // TODO: Missing getMedia() in SDK.
        // $media_obj = $promotion->getMedia();
        // $media     = $media_obj ? [
        // 	'type'  => $media_obj->getType(),
        // 	'value' => $media_obj->getValue(),
        // ] : null;

        return [
            'id' => $tier->getUuid(),
            'name' => $tier->getName(),
            'position' => $tier->getPosition(),
        ];
    }
}
