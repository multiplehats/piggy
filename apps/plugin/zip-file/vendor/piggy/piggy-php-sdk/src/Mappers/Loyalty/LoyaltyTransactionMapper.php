<?php

namespace Piggy\Api\Mappers\Loyalty;

use Exception;
use Piggy\Api\Enum\LoyaltyTransactionType;
use Piggy\Api\Mappers\Loyalty\Receptions\CreditReceptionMapper;
use Piggy\Api\Mappers\Loyalty\Receptions\RewardReceptionMapper;
use Piggy\Api\Models\Loyalty\Receptions\BaseReception as BaseReceptionModel;
use stdClass;

class LoyaltyTransactionMapper
{
    /**
     * @param  stdClass[]  $data
     * @return array<int, BaseReceptionModel|null>
     *
     * @throws Exception
     */
    public function map(array $data): array
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
