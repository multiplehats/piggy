<?php

namespace Piggy\Api\Models\Loyalty;

class Media
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $value;

    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
