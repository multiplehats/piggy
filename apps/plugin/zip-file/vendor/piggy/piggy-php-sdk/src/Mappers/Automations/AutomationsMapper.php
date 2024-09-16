<?php

namespace Piggy\Api\Mappers\Automations;

use Piggy\Api\Models\Automations\Automation;
use stdClass;

class AutomationsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Automation[]
     */
    public function map(array $data): array
    {
        $mapper = new AutomationMapper();

        $automations = [];
        foreach ($data as $item) {
            $automations[] = $mapper->map($item);
        }

        return $automations;
    }
}
