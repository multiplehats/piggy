<?php

namespace Piggy\Api\StaticMappers\Forms;

class FormsMapper
{
    public static function map($data): array
    {
        $forms = [];
        foreach ($data as $item) {
            $forms[] = FormMapper::map($item);
        }

        return $forms;
    }
}
