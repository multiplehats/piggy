<?php

namespace Piggy\Api\Models\Loyalty\Transactions;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Loyalty\LoyaltyTransactionAttributes\LoyaltyTransactionAttributeMapper;
use Piggy\Api\StaticMappers\Loyalty\LoyaltyTransactionAttributes\LoyaltyTransactionAttributesMapper;

class LoyaltyTransactionAttribute
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string|null
     */
    protected $placeholder;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $field_type;

    /**
     * @var mixed[]
     */
    protected $options;

    /**
     * @var bool
     */
    protected $is_piggy_defined;

    /**
     * @var bool
     */
    protected $is_soft_read_only;

    /**
     * @var bool
     */
    protected $is_hard_read_only;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/loyalty-transaction-attributes';

    /**
     * @param  mixed[]  $options
     */
    public function __construct(
        string $name,
        string $label,
        string $type,
        string $field_type,
        ?string $placeholder = null,
        array $options = [],
        bool $is_piggy_defined = false,
        bool $is_soft_read_only = false,
        bool $is_hard_read_only = false
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->field_type = $field_type;
        $this->placeholder = $placeholder;
        $this->options = $options;
        $this->is_piggy_defined = $is_piggy_defined;
        $this->is_soft_read_only = $is_soft_read_only;
        $this->is_hard_read_only = $is_hard_read_only;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFieldType(): string
    {
        return $this->field_type;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isPiggyDefined(): bool
    {
        return $this->is_piggy_defined;
    }

    public function isSoftReadOnly(): bool
    {
        return $this->is_soft_read_only;
    }

    public function isHardReadOnly(): bool
    {
        return $this->is_hard_read_only;
    }

    /**
     * @param  mixed[]  $params
     * @return LoyaltyTransactionAttribute[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return LoyaltyTransactionAttributesMapper::map((array) $response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): LoyaltyTransactionAttribute
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return LoyaltyTransactionAttributeMapper::map($response->getData());
    }
}
