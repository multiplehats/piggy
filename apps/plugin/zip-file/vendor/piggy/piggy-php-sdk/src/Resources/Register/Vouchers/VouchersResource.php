<?php

namespace Piggy\Api\Resources\Register\Vouchers;

use DateTime;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Vouchers\VoucherMapper;
use Piggy\Api\Mappers\Vouchers\VouchersMapper;
use Piggy\Api\Models\Vouchers\Voucher;
use Piggy\Api\Resources\Shared\Vouchers\BaseVouchersResource;

class VouchersResource extends BaseVouchersResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/vouchers';

    /**
     * @throws PiggyRequestException
     */
    public function redeem(?string $code = null, ?string $contactUuid = null): Voucher
    {
        $response = $this->client->post("$this->resourceUri/redeem", [
            'code' => $code,
            'contact_uuid' => $contactUuid,
        ]);

        $mapper = new VoucherMapper();

        return $mapper->map($response->getData());
    }
}
