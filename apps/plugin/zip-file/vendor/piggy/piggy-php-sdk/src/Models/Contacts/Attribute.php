<?php

namespace Piggy\Api\Models\Contacts;

class Attribute
{
    /** @var string */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $fieldType;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var bool|null
     */
    protected $isSoftReadOnly;

    /**
     * @var bool|null
     */
    protected $isHardReadOnly;

    /**
     * @var bool|null
     */
    protected $isPiggyDefined;

    /**
     * @var mixed[]|null
     */
    protected $options;

    /**
     * @param  mixed[]|null  $options
     */
    public function __construct(string $name, string $label, string $type, ?string $fieldType, ?string $description = null, ?bool $isSoftReadOnly = null, ?bool $isHardReadOnly = null, ?bool $isPiggyDefined = null, ?array $options = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->fieldType = $fieldType;
        $this->description = $description;
        $this->isSoftReadOnly = $isSoftReadOnly;
        $this->isHardReadOnly = $isHardReadOnly;
        $this->isPiggyDefined = $isPiggyDefined;
        $this->options = $options;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getFieldType(): ?string
    {
        return $this->type;
    }

    public function setFieldType(string $type): void
    {
        $this->type = $type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIsSoftReadOnly(): ?bool
    {
        return $this->isSoftReadOnly;
    }

    public function setIsSoftReadOnly(bool $isSoftReadOnly): void
    {
        $this->isSoftReadOnly = $isSoftReadOnly;
    }

    public function getIsHardReadOnly(): ?bool
    {
        return $this->isHardReadOnly;
    }

    public function setIsHardReadOnly(bool $isHardReadOnly): void
    {
        $this->isHardReadOnly = $isHardReadOnly;
    }

    public function getIsPiggyDefined(): ?bool
    {
        return $this->isPiggyDefined;
    }

    public function setIsPiggyDefined(bool $isPiggyDefined): void
    {
        $this->isPiggyDefined = $isPiggyDefined;
    }

    /**
     * @return mixed[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param  mixed[]|null  $options
     */
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }
}
