<?php

namespace Piggy\Api\Exceptions;

use Exception;
use stdClass;
use Throwable;

class ExceptionMapper
{
    /**
     * @throws PiggyRequestException
     * @throws MaintenanceModeException
     */
    public function map(Exception $exception): Exception
    {
        if (method_exists($exception, 'hasResponse') && method_exists($exception, 'getResponse')) {

            if ($exception->getResponse()->getStatusCode() == 503) {
                throw new MaintenanceModeException('Piggy system is in maintenance mode.', 503);
            }

            $body = $exception->getResponse()->getBody();
            $body = @json_decode($body);

            if ($this->isPiggyException($body)) {
                throw $this->mapPiggyException($body, $exception);
            }
        }

        return $exception;
    }

    private function isPiggyException(stdClass $body): bool
    {
        $statusCode = property_exists($body, 'status_code');
        $code = property_exists($body, 'code');
        $message = property_exists($body, 'message');

        return $statusCode && $code && $message;
    }

    private function mapPiggyException(stdClass $body, Throwable $previous): PiggyRequestException
    {
        $statusCode = $body->status_code;
        $code = $body->code;
        $message = $body->message;

        if (property_exists($body, 'errors')) {
            $mappedErrors = [];

            foreach ($body->errors as $key => $errors) {
                $mappedErrors[] = new Error($key, $errors);
            }

            $errorBag = new ErrorBag($mappedErrors);
        } else {
            $errorBag = null;
        }

        return new PiggyRequestException(
            $message,
            $code,
            $statusCode,
            $errorBag,
            $previous
        );
    }
}
