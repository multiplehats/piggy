<?php

namespace Piggy\Api\StaticMappers\Automations;

use Piggy\Api\Http\Responses\Response;

class AutomationsMapper
{
    public static function map(Response $response): array
    {
        $automations = [];

        foreach ($response->getData() as $item) {
            $automations[] = AutomationMapper::map($item);
        }

        return $automations;
    }
}
