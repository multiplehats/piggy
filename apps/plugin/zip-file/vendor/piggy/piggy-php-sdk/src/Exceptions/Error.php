<?php

namespace Piggy\Api\Exceptions;

class Error
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed[]
     */
    protected $errors;

    /**
     * @param  mixed[]  $errors
     */
    public function __construct(string $key, array $errors)
    {
        $this->key = $key;
        $this->errors = $errors;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
