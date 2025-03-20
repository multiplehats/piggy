<?php

namespace Leat\Infrastructure\Formatters;

use Piggy\Api\Models\Tiers\Tier;

class TierFormatter
{
    public function format(Tier $tier)
    {
        $media = $tier->getMedia();

        return [
            'id' => $tier->getUuid(),
            'name' => $tier->getName(),
            'position' => $tier->getPosition(),
            'media' => isset($media) ? array(
                'type' => $media['type'],
                'value' => $media['value'],
            ) : null,
            'description' => $tier->getDescription() ?? null,
        ];
    }
}
