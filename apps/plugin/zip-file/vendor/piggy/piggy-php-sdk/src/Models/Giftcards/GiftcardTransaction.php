<?php

namespace Piggy\Api\Models\Giftcards;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\StaticMappers\Giftcards\GiftcardTransactionMapper;
use Piggy\Api\StaticMappers\Giftcards\GiftcardTransactionsMapper;
use stdClass;

class GiftcardTransaction
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var int
     */
    protected $amount_in_cents;

    /**
     * @var DateTime
     */
    protected $created_at;

    /**
     * @var ?int
     */
    protected $card_id;

    /**
     * @var ?int
     */
    protected $shop_id;

    /**
     * @var ?bool
     */
    protected $settled;

    /**
     * @var ?string
     */
    protected $type;

    /**
     * @var GiftcardTransactionSettlement[]
     */
    protected $settlements = [];

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var Shop|null
     */
    protected $shop;

    /**
     * @var stdClass|null
     */
    protected $card;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/giftcard-transactions';

    /**
     * @param  GiftcardTransactionSettlement[]  $settlements
     */
    public function __construct(
        string $uuid,
        int $amountInCents,
        DateTime $createdAt,
        ?string $type = null,
        ?bool $settled = null,
        ?int $cardId = null,
        ?int $shopId = null,
        array $settlements = [],
        ?int $id = null,
        ?Shop $shop = null,
        ?stdClass $card = null
    ) {
        $this->uuid = $uuid;
        $this->amount_in_cents = $amountInCents;
        $this->created_at = $createdAt;
        $this->type = $type;
        $this->settled = $settled;
        $this->card_id = $cardId;
        $this->shop_id = $shopId;
        $this->settlements = $settlements;
        $this->id = $id;
        $this->shop = $shop;
        $this->card = $card;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getAmountInCents(): int
    {
        return $this->amount_in_cents;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    public function getGiftcardId(): ?int
    {
        return $this->card_id;
    }

    public function getShopId(): ?int
    {
        return $this->shop_id;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isSettled(): ?bool
    {
        return $this->settled;
    }

    /**
     * @return GiftcardTransactionSettlement[]
     */
    public function getSettlements(): array
    {
        return $this->settlements;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCard(): ?stdClass
    {
        return $this->card;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(string $giftcardTransactionUuid, array $params = []): GiftcardTransaction
    {
        $response = ApiClient::get(self::resourceUri."/$giftcardTransactionUuid", $params);

        return GiftcardTransactionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): GiftcardTransaction
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return GiftcardTransactionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function correct(string $giftcardTransactionUuid, array $body = []): GiftcardTransaction
    {
        $response = ApiClient::post(self::resourceUri."/$giftcardTransactionUuid/correct", $body);

        return GiftcardTransactionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     * @return GiftcardTransaction[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return GiftcardTransactionsMapper::map((array) $response->getData());
    }

    /**
     * @param string $giftcardTransactionUuid
     * @return GiftcardTransaction
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function reverse(string $giftcardTransactionUuid): GiftcardTransaction
    {
        $response = ApiClient::post(self::resourceUri . "/$giftcardTransactionUuid/reverse", []);

        return GiftcardTransactionMapper::map($response->getData());
    }
}