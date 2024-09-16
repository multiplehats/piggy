<?php

namespace Piggy\Api\Resources\Shared\Vouchers;

use DateTime;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Vouchers\VoucherLockMapper;
use Piggy\Api\Mappers\Vouchers\VoucherMapper;
use Piggy\Api\Mappers\Vouchers\VouchersMapper;
use Piggy\Api\Models\Vouchers\Voucher;
use Piggy\Api\Models\Vouchers\VoucherLock;
use Piggy\Api\Resources\BaseResource;

abstract class BaseVouchersResource extends BaseResource
{
    /**
     * @throws PiggyRequestException
     */
    public function create(string $promotionUuid, ?string $code = null, ?string $contactUuid = null, ?DateTime $activationDate = null, ?DateTime $expirationDate = null): Voucher
    {
        $response = $this->client->post($this->resourceUri, [
            'promotion_uuid' => $promotionUuid,
            'code' => $code,
            'contact_uuid' => $contactUuid,
            'activation_date' => $activationDate,
            'expiration_date' => $expirationDate,
        ]);

        $mapper = new VoucherMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @return Voucher[]
     *
     * @throws PiggyRequestException
     */
    public function list(int $page = 1, int $limit = 30, ?string $promotionUuid = null, ?string $contactUuid = null, ?string $status = null): array
    {
        $response = $this->client->get($this->resourceUri, [
            'page' => $page,
            'limit' => $limit,
            'promotion_uuid' => $promotionUuid,
            'contact_uuid' => $contactUuid,
            'status' => $status,
        ]);

        $mapper = new VouchersMapper();

        return $mapper->map((array) $response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function findByCode(string $code): Voucher
    {
        $response = $this->client->get("$this->resourceUri/find", [
            'code' => $code,
        ]);

        $mapper = new VoucherMapper();

        return $mapper->map($response->getData());
    }
}
