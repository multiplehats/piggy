<?php

namespace Piggy\Api\StaticMappers\Loyalty;

use Exception;
use Piggy\Api\Enum\LoyaltyTransactionType;
use Piggy\Api\Models\Loyalty\Receptions\BaseReception as BaseReceptionModel;
use Piggy\Api\StaticMappers\Loyalty\Receptions\CreditReceptionMapper;
use Piggy\Api\StaticMappers\Loyalty\Receptions\RewardReceptionMapper;

class LoyaltyTransactionMapper
{
    /**
     * @return array<int, BaseReceptionModel>
     *
     * @throws Exception
     */
    public static function map($data): array
    {
        $creditReceptionMapper = new CreditReceptionMapper();
        $rewardReceptionMapper = new RewardReceptionMapper();

        $transactions = [];

        foreach ($data as $transactionData) {
            switch ($transactionData->type) {

                case LoyaltyTransactionType::CREDIT_RECEPTION:
                    $mapper = $creditReceptionMapper;
                    break;

                case LoyaltyTransactionType::PHYSICAL_REWARD_RECEPTION:
                case LoyaltyTransactionType::DIGITAL_REWARD_RECEPTION:
                    $mapper = $rewardReceptionMapper;
                    break;

                default:
                    continue 2;
            }

            $transactions[] = $mapper->map($transactionData);
        }

        return $transactions;
    }
}
