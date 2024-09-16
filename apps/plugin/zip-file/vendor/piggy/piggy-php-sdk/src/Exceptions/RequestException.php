<?php

namespace Piggy\Api\Exceptions;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Throwable;

class RequestException extends Exception
{
    /**
     * @var string
     */
    protected $message;

    /**
     * HTTP-status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Piggy Internal Code. Useful for extra debugging.
     *
     * @var ?int
     */
    protected $code;

    /**
     * If given, an array of errors.
     *
     * @var mixed[]|null
     */
    protected $errors;

    /**
     * The original, json decoded response.
     *
     * @var Response|null
     */
    protected $response;

    /**
     * @param  mixed[]|null  $errors
     */
    public function __construct(
        string $message,
        int $statusCode,
        ?int $code,
        ?array $errors = [],
        ?Response $response = null
    ) {
        parent::__construct($message);
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->code = $code;
        $this->errors = $errors;
        $this->response = $response;
    }

    /**
     * @return RequestException
     *
     * @throws RequestException
     */
    public static function createFromGuzzleException(GuzzleException $guzzleException)
    {
        // Not all Guzzle Exceptions implement hasResponse() / getResponse()
        if (method_exists($guzzleException, 'hasResponse') && method_exists($guzzleException, 'getResponse')) {
            if ($guzzleException->hasResponse()) {
                return static::createFromResponse($guzzleException->getResponse());
            }
        }

        return new self($guzzleException->getMessage(), $guzzleException->getCode(), null, null);
    }

    /**
     * @throws RequestException
     */
    public static function createFromResponse(Response $response, ?Throwable $previous = null): RequestException
    {
        $body = $response->getBody();

        $object = json_decode($body);

        return new self(
            $object->message,
            $response->getStatusCode(),
            $object->code,
            (array) $previous
        );
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}
