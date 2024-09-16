<?php

namespace Piggy\Api\Mappers\Loyalty\RewardAttributes;

use Piggy\Api\Models\Loyalty\RewardAttributes\RewardAttribute;
use stdClass;

class RewardAttributeMapper
{
    public function map(stdClass $rewardAttribute): RewardAttribute
    {
        $fieldType = $rewardAttribute->type;

        $isSoftReadOnly = property_exists($rewardAttribute, 'is_soft_read_only') && $rewardAttribute->is_soft_read_only;
        $isHardReadOnly = property_exists($rewardAttribute, 'is_hard_read_only') && $rewardAttribute->is_hard_read_only;
        $isPiggyDefined = property_exists($rewardAttribute, 'is_piggy_defined') && $rewardAttribute->is_piggy_defined;

        $options = [];

        if (property_exists($rewardAttribute, 'options') && $rewardAttribute->options != null) {
            foreach ($rewardAttribute->options as $item) {
                $options[] = get_object_vars($item);
            }
        }

        return new RewardAttribute(
            $rewardAttribute->name,
            $rewardAttribute->label,
            $rewardAttribute->description,
            $rewardAttribute->type,
            $fieldType,
            $isSoftReadOnly,
            $isHardReadOnly,
            $isPiggyDefined,
            $options,
            $rewardAttribute->placeholder ?? null
        );
    }
}
