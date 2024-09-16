<?php

namespace Piggy\Api\Models\Vouchers;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\StaticMappers\Vouchers\VoucherLockMapper;
use Piggy\Api\StaticMappers\Vouchers\VoucherMapper;
use Piggy\Api\StaticMappers\Vouchers\VouchersMapper;
use stdClass;

class Voucher
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var ?string
     */
    protected $code;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var ?string
     */
    protected $name;

    /**
     * @var ?string
     */
    protected $description;

    /**
     * @var DateTime|null
     */
    protected $expiration_date;

    /**
     * @var DateTime|null
     */
    protected $activation_date;

    /**
     * @var DateTime|null
     */
    protected $redeemed_at;

    /**
     * @var ?bool
     */
    protected $is_redeemed;

    /**
     * @var ?Promotion
     */
    protected $promotion;

    /**
     * @var Contact|null
     */
    protected $contact;

    /**
     * @var mixed[]
     */
    protected $attributes;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/vouchers';

    /**
     * @param  mixed[]  $attributes
     */
    public function __construct(
        string $uuid,
        string $status,
        ?string $code,
        ?string $name,
        ?string $description,
        ?Promotion $promotion,
        ?Contact $contact,
        ?DateTime $redeemedAt,
        ?bool $isRedeemed,
        ?DateTime $activationDate,
        ?DateTime $expirationDate,
        array $attributes = []
    ) {
        $this->uuid = $uuid;
        $this->code = $code;
        $this->name = $name;
        $this->status = $status;
        $this->description = $description;
        $this->promotion = $promotion;
        $this->contact = $contact;
        $this->redeemed_at = $redeemedAt;
        $this->is_redeemed = $isRedeemed;
        $this->activation_date = $activationDate;
        $this->expiration_date = $expirationDate;
        $this->attributes = $attributes;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function getExpirationDate(): ?DateTime
    {
        return $this->expiration_date;
    }

    public function getActivationDate(): ?DateTime
    {
        return $this->activation_date;
    }

    public function getRedeemedAt(): ?DateTime
    {
        return $this->redeemed_at;
    }

    public function isRedeemed(): ?bool
    {
        return $this->is_redeemed;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    /**
     * @return mixed[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function create(array $body): Voucher
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return VoucherMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function batch(array $body): stdClass
    {
        $response = ApiClient::post(self::resourceUri.'/batch', $body);

        return $response->getData();
    }

    /**
     * @param  mixed[]  $params
     * @return Voucher[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return VouchersMapper::map((array) $response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function find(array $params): Voucher
    {
        $response = ApiClient::get(self::resourceUri.'/find', $params);

        return VoucherMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function redeem(array $body): Voucher
    {
        $response = ApiClient::post(self::resourceUri.'/redeem', $body);

        return VoucherMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function lock(string $voucherUuid, array $body = []): VoucherLock
    {
        $response = ApiClient::post(self::resourceUri."/$voucherUuid/lock/", $body);

        return VoucherLockMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function release(string $voucherUuid, array $body): VoucherLock
    {
        $response = ApiClient::post(self::resourceUri."/$voucherUuid/release/", $body);

        return VoucherLockMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function update(string $voucherUuid, array $body): Voucher
    {
        $response = ApiClient::put(self::resourceUri."/$voucherUuid", $body);

        return VoucherMapper::map($response->getData());
    }
}
