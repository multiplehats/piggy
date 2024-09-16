<?php

namespace Piggy\Api\Models\Giftcards;

class GiftcardTransactionSettlement
{
    /**
     * @var int | null
     */
    protected $id;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
