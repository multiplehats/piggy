<?php

namespace Piggy\Api\Mappers\Automations;

use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Models\Automations\Automation;
use stdClass;

class AutomationMapper extends BaseMapper
{
    public function map(stdClass $data): Automation
    {
        return new Automation(
            $data->name,
            $data->status,
            $data->event,
            $this->parseDate($data->created_at),
            $this->parseDate($data->updated_at)
        );
    }
}
