<?php

namespace Leat\Domain\Services;

use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\PiggyRequestException;
use Leat\Utils\Logger;

class ApiService
{

    /**
     * Register Client instance.
     *
     * @var RegisterClient
     */
    protected $client;


    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $api_key = $this->get_api_key();

        if ($api_key) {
            $this->client = new RegisterClient($api_key);
        } else {
            $this->client = null;
        }

        $this->logger = new Logger();
    }

    /**
     * Log API errors.
     *
     * @param \Exception $e The exception to log.
     * @param string     $context Additional context for the error.
     */
    public function log_exception(\Exception $e, string $context = '')
    {
        if ($e instanceof PiggyRequestException) {
            $error_bag = $e->getErrorBag();
            $this->logger->error(
                'API Error Details: ' .
                    wp_json_encode(
                        [
                            'message'     => $e->getMessage(),
                            'code'        => $e->getCode(),
                            'error_bag'   => $error_bag ? wp_json_encode($error_bag->all()) : null,
                            'first_error' => $error_bag ? wp_json_encode($error_bag->first()) : null,
                            'context'     => $context,
                        ],
                        JSON_PRETTY_PRINT,
                    ),
            );
        } else {
            $this->logger->error(
                $context . ': ' .
                    wp_json_encode(
                        [
                            'error' => $e->getMessage(),
                            'trace' => defined('WP_DEBUG') && WP_DEBUG ? $e->getTraceAsString() : null,
                        ]
                    ),
            );
        }
    }

    /**
     * Get the Leat API key.
     *
     * @return string|null The Leat API key.
     */
    public function get_api_key()
    {
        $api_key = get_option('leat_api_key', null);

        if (! $api_key) {
            $api_key = get_option('piggy_api_key', null);
        }

        return $api_key;
    }

    public function has_api_key()
    {
        $api_key = $this->get_api_key();

        return null !== $api_key && '' !== $api_key;
    }

    /**
     * Get the Leat shop UUID.
     *
     * @return string|null The Leat shop UUID.
     */
    public function get_shop_uuid()
    {
        return get_option('leat_shop_uuid', null);
    }

    /**
     * Get the  Register Client instance.
     *
     * @return null|true
     */
    public function init_client()
    {
        $api_key = $this->get_api_key();

        if ($api_key && strlen($api_key) === 53) {
            ApiClient::configure($api_key, 'https://api.piggy.eu');
            ApiClient::setPartnerId('P01-267-loyal_minds');

            $this->client = true;
            return $this->client;
        } else {
            $this->client = null;
            return $this->client;
        }
    }
}
