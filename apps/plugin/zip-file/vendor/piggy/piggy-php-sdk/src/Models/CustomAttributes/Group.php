<?php

namespace Piggy\Api\Models\CustomAttributes;

class Group
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ?string
     */
    protected $label;

    /**
     * @var int
     */
    protected $position;

    public function __construct(string $name, int $position, ?string $label = null)
    {
        $this->name = $name;
        $this->position = $position;
        $this->label = $label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
