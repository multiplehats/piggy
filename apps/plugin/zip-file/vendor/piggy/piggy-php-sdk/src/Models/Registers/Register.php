<?php

namespace Piggy\Api\Models\Registers;

use Piggy\Api\Models\Shops\Shop;

class Register
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var ?string
     */
    protected $name;

    /**
     * @var Shop
     */
    protected $shop;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function setShop(Shop $shop): void
    {
        $this->shop = $shop;
    }
}
