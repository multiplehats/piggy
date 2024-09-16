<?php

namespace Piggy\Api;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\Exceptions\ExceptionMapper;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\MalformedResponseException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Http\Responses\Response;
use Piggy\Api\Http\Traits\SetsOAuthResources as OAuthResources;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ApiClient
{
    use OAuthResources;

    /**
     * @var GuzzleClient
     */
    private static $httpClient;

    /**
     * @var string
     */
    private static $baseUrl = 'https://api.piggy.nl';

    /**
     * @var array<string, string>
     */
    private static $headers = [
        'Accept' => 'application/json',
    ];

    public function __construct(string $apiKey, string $baseUrl)
    {
        self::setApiKey($apiKey);
        self::setBaseUrl($baseUrl);
        self::$httpClient = new GuzzleClient();
    }

    public static function configure(string $apiKey, string $baseUrl): void
    {
        new self($apiKey, $baseUrl);
    }

    public static function setApiKey(string $apiKey): void
    {
        self::addHeader('Authorization', "Bearer $apiKey");
    }

    /**
     * @param  mixed[]  $queryOptions
     *
     * @throws Exceptions\MaintenanceModeException
     * @throws Exceptions\PiggyRequestException
     * @throws GuzzleException
     */
    public static function request(string $method, string $endpoint, array $queryOptions = []): Response
    {
        if (! array_key_exists('Authorization', self::$headers)) {
            throw new Exception('Authorization not set yet.');
        }

        $url = self::$baseUrl.$endpoint;

        try {
            $rawResponse = self::getResponse($method, $url, [
                'headers' => self::$headers,
                'form_params' => $queryOptions,
            ]);

            return self::parseResponse($rawResponse);
        } catch (Exception $e) {
            $exceptionMapper = new ExceptionMapper();

            throw $exceptionMapper->map($e);
        }
    }

    /**
     * @throws MalformedResponseException
     */
    private static function parseResponse(ResponseInterface $response): Response
    {
        try {
            $content = json_decode($response->getBody()->getContents());
        } catch (Throwable $exception) {
            throw new MalformedResponseException('Could not decode response');
        }

        if (! property_exists($content, 'data')) {
            throw new MalformedResponseException('Invalid response given. Data property was missing from response.');
        }

        return new Response($content->data, $content->meta ?? []);
    }

    public static function getBaseUrl(): string
    {

        return self::$baseUrl;
    }

    public static function setBaseUrl(string $baseUrl): void
    {
        self::$baseUrl = $baseUrl;
    }

    public static function addHeader(string $key, string $value): void
    {
        self::$headers[$key] = $value;
    }

    /**
     * @return string[]
     */
    public static function getHeaders(): array
    {
        return self::$headers;
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException
     * @throws GuzzleException
     * @throws PiggyRequestException
     */
    public static function post(string $url, array $body): Response
    {
        return self::request('POST', $url, $body);
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException
     * @throws GuzzleException
     * @throws PiggyRequestException
     */
    public static function put(string $url, array $body): Response
    {
        return self::request('PUT', $url, $body);
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException
     * @throws GuzzleException
     * @throws PiggyRequestException
     */
    public static function get(string $url, array $params = []): Response
    {
        $query = http_build_query($params);

        if ($query) {
            $url = "{$url}?{$query}";
        }

        return self::request('GET', $url);
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException
     * @throws GuzzleException
     * @throws PiggyRequestException
     */
    public static function delete(string $url, array $body = []): Response
    {
        $query = http_build_query($body);

        if ($query) {
            $url = "{$url}?{$query}";
        }

        return self::request('DELETE', $url);
    }

    /**
     * @param  mixed[]  $options
     *
     * @throws GuzzleException
     */
    private static function getResponse(string $method, string $url, array $options = []): ResponseInterface
    {
        return self::$httpClient->request($method, $url, $options);
    }
}
