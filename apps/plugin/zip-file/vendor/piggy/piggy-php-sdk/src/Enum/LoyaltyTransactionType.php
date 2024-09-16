<?php

namespace Piggy\Api\Enum;

use MabeEnum\Enum;

class LoyaltyTransactionType extends Enum
{
    const CREDIT_RECEPTION = 'credit_reception';

    const PHYSICAL_REWARD_RECEPTION = 'reward_reception';

    const DIGITAL_REWARD_RECEPTION = 'digital_reward_reception';
}
