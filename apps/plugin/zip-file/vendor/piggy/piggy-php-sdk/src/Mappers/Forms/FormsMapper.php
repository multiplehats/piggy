<?php

namespace Piggy\Api\Mappers\Forms;

use Piggy\Api\Models\Forms\Form;
use stdClass;

class FormsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Form[]
     */
    public function map(array $data): array
    {
        $mapper = new FormMapper();

        $forms = [];
        foreach ($data as $item) {
            $forms[] = $mapper->map($item);
        }

        return $forms;
    }
}
