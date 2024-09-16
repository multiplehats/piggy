<?php

namespace Piggy\Api\StaticMappers;

use DateTime;
use DateTimeInterface;

abstract class BaseMapper
{
    /**
     * @return DateTime|false
     */
    public static function parseDate(string $date)
    {
        return DateTime::createFromFormat(DateTimeInterface::ATOM, $date);
    }
}
