<?php

namespace Piggy\Api\Exceptions;

use Exception;
use Throwable;

class PiggyRequestException extends Exception
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var ErrorBag|null
     */
    protected $errorBag;

    /**
     * PiggyRequestException constructor.
     */
    public function __construct(string $message, int $code, int $statusCode, ?ErrorBag $errorBag = null, ?Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->errorBag = $errorBag;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorBag(): ?ErrorBag
    {
        return $this->errorBag;
    }
}
