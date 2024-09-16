<?php

namespace Piggy\Api\StaticMappers\Automations;

use Piggy\Api\Models\Automations\Automation;
use Piggy\Api\StaticMappers\BaseMapper;
use stdClass;

class AutomationMapper extends BaseMapper
{
    public static function map(stdClass $data): Automation
    {
        return new Automation(
            $data->name,
            $data->status,
            $data->event,
            self::parseDate($data->created_at),
            self::parseDate($data->updated_at)
        );
    }
}
