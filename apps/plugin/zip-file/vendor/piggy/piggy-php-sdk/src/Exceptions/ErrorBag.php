<?php

namespace Piggy\Api\Exceptions;

class ErrorBag
{
    /**
     * @var Error[]
     */
    protected $errors;

    /**
     * ErrorBag constructor.
     *
     * @param  Error[]  $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get the first error
     */
    public function first(): ?Error
    {
        if (count($this->errors)) {
            return $this->errors[0];
        }

        return null;
    }

    /**
     * Get all errors
     *
     * @return Error[]
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Get error by key
     */
    public function get(string $key): ?Error
    {
        return $this->errors[$key] ?? null;
    }
}
