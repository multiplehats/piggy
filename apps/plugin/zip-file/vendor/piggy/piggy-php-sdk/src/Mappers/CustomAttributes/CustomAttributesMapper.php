<?php

namespace Piggy\Api\Mappers\CustomAttributes;

use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use stdClass;

class CustomAttributesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return CustomAttribute[]
     */
    public function map(array $data): array
    {
        $mapper = new CustomAttributeMapper();

        $customAttributes = [];
        foreach ($data as $item) {
            $customAttributes[] = $mapper->map($item);
        }

        return $customAttributes;
    }
}
