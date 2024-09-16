<?php

namespace Piggy\Api\Resources\OAuth\Vouchers;

use DateTime;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Vouchers\VoucherLockMapper;
use Piggy\Api\Mappers\Vouchers\VoucherMapper;
use Piggy\Api\Models\Vouchers\Voucher;
use Piggy\Api\Models\Vouchers\VoucherLock;
use Piggy\Api\Resources\Shared\Vouchers\BaseVouchersResource;

class VouchersResource extends BaseVouchersResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/vouchers';

    /**
     * @throws PiggyRequestException
     */
    public function batch(string $promotionUuid, string $quantity, ?string $contactUuid = null, ?DateTime $activationDate = null, ?DateTime $expirationDate = null): string
    {
        $this->client->post($this->resourceUri, [
            'promotion_uuid' => $promotionUuid,
            'quantity' => $quantity,
            'contact_uuid' => $contactUuid,
            'activation_date' => $activationDate,
            'expiration_date' => $expirationDate,
        ]);

        return 'Voucher generation successfully started in background.';
    }

    /**
     * @throws PiggyRequestException
     */
    public function redeem(?string $code = null, ?string $contactUuid = null, ?string $releaseKey = null): Voucher
    {
        $response = $this->client->post("$this->resourceUri/redeem", [
            'code' => $code,
            'contact_uuid' => $contactUuid,
            'release_key' => $releaseKey,
        ]);

        $mapper = new VoucherMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function lock(string $voucherUuid): VoucherLock
    {
        $response = $this->client->post("$this->resourceUri/$voucherUuid/lock/", []);

        $mapper = new VoucherLockMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function release(string $voucherUuid, string $releaseKey): VoucherLock
    {
        $response = $this->client->post("$this->resourceUri/$voucherUuid/release/", [
            'release_key' => $releaseKey,
        ]);

        $mapper = new VoucherLockMapper();

        return $mapper->map($response->getData());
    }
}
