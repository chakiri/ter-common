<?php

namespace Ter\Common\Service;

use Ter\Common\Model\ApiResponse;
use Doctrine\ORM\Repository\Exception\InvalidMagicMethodCall;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * ApiService
 *
 * @method ApiResponse get(string $url, array $filters = [], bool $secure = true, string $token_api = "")
 * @method ApiResponse post(string $url, array $resource = [], bool $secure = true, string $token_api = "")
 * @method ApiResponse put(string $url, array $resource = [], bool $secure = true, string $token_api = "")
 * @method ApiResponse patch(string $url, array $resource = [], bool $secure = true, string $token_api = "")
 * @method ApiResponse delete(string $url, array $resource = [], bool $secure = true, string $token_api = "")
 * @method ApiResponse upload(string $url, array $resource = [], bool $secure = true, string $token_api = "")
 */
abstract class ApiService
{
    public const HYDRA_MEMBER = 'hydra:member';
    public const HYDRA_TOTAL_ITEMS = 'hydra:totalItems';

    /**
     * Allowed methods for __call
     */
    const FUNCTION_VERB = ['get', 'post', 'patch', 'put', 'upload', 'delete'];

    /**
     * HTTP Client
     *
     * @var Client
     */
    public Client $client;

    /**
     * API URL
     *
     * @var string
     */
    private string $url;

    /**
     * API Token
     *
     * @var string
     */
    private string $token_api = "";

    /**
     * HTTP Client headers
     *
     * @var array|string[]
     */
    private array $headers = [
        'accept' => 'application/ld+json',
        'Content-Type' => 'application/ld+json'
    ];

    /**
     * Proxy setting: To bypass proxy setting for GuzzleHttp client
     */
    private array $proxySetting = ['proxy' => ''];


    public function __construct(
        private ContainerBagInterface $containerBagInterface,
        private Security              $security,
        private bool                  $useProxy,
        private ApiError              $apiError,
    )
    {
        $this->client = new Client();
        $this->url = $containerBagInterface->get('url_api') . "/api";
    }

    /**
     * Create and return an instance of http client
     */
    public static function createHttpClient(array $params): Client
    {
        return new Client($params);
    }

    /***
     * For others functions
     */
    public function __call(string $name, array $args)
    {
        if (!isset($args[0])) {
            throw new InvalidArgumentException('Vous devez fournir un IRI de resource valide');
        }

        $uri = $args[0];//$url
        $resource = $args[1] ?? [];//$resource
        $secure = $args[2] ?? true;//secure
        $tokenApi = $args[3] ?? '';//tokenApi

        $methodName = in_array($name, self::FUNCTION_VERB) ? 'verb' . ucfirst($name) : $name;
        if (!method_exists(ApiService::class, $methodName)) {
            //TODO: create custom exception
            throw new InvalidMagicMethodCall("La méthode " . $name . " n'est pas définie dans ApiService");
        }

        try {
            $response = $this->{$methodName}($uri, $resource, $secure, $tokenApi);
        } catch (GuzzleException $ex) {
            $errors = json_decode($ex->getResponse()->getBody()->getContents(), true);
            $response = [
                'error' => true,
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'violations' => $this->apiError->violations($errors)
            ];
        }

        return new ApiResponse($response);
    }

    public function verbPost(string $url, array $resource = [], bool $secure = true, string $token_api = "")
    {
        $api_url = $this->url . $url;
        $this->setAuthorizationHeader($secure, $token_api);
        $response = $this->client->post($api_url, $this->optionsWrapper([
            'headers' => $this->headers,
            'body' => json_encode($resource)
        ]));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Add authorization header
     */
    public function setAuthorizationHeader(bool $secure, string $token_api): void
    {
        if ($secure) {
            if ($this->security->getUser()) {
                $token_api = $this->security->getUser()->getTokenApi();
            }
            $this->headers['Authorization'] = 'Bearer ' . $token_api;
        }
    }

    /**
     * Add additional options for API client
     *
     * @param array $options
     * @return array|string[]
     */
    private function optionsWrapper(array $options): array
    {
        if ($this->useProxy) {
            $options = array_merge($options, $this->proxySetting);
        }
        return $options;
    }

    public function verbPatch(string $url, array $resource = [], bool $secure = true, string $token_api = "")
    {
        $api_url = $this->url . $url;
        $this->setAuthorizationHeader($secure, $token_api);
        $this->headers['Content-Type'] = 'application/merge-patch+json';
        $response = $this->client->patch($api_url, $this->optionsWrapper([
            'headers' => $this->headers,
            'body' => json_encode($resource)
        ]));

        return json_decode($response->getBody()->getContents(), true);
    }

    public function verbPut(string $url, array $resource = [], bool $secure = true, string $token_api = "")
    {
        $api_url = $this->url . $url;
        $this->setAuthorizationHeader($secure, $token_api);
        $response = $this->client->put($api_url, $this->optionsWrapper([
            'headers' => $this->headers,
            'body' => json_encode($resource)
        ]));
        return json_decode($response->getBody()->getContents(), true);
    }

    public function verbDelete(string $url, array $resource = [], bool $secure = true, string $token_api = ""): mixed
    {
        $api_url = $this->url . $url;
        $this->setAuthorizationHeader($secure, $token_api);
        $response = $this->client->delete($api_url, $this->optionsWrapper([
            'headers' => $this->headers,
            'body' => json_encode($resource),
        ]));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $url
     * @param $file
     * @param bool $secure
     * @param string $token_api
     * @return mixed
     * @throws GuzzleException
     */
    public function verbUpload(string $url, $file, bool $secure = true, string $token_api = ""): mixed
    {
        $url = $this->url . $url;
        $this->setAuthorizationHeader($secure, $token_api);
        $resource = [
            [
                'Content-type' => 'multipart/form-data',
                'name' => 'file',
                'filename' => $file->getClientOriginalName(),
                'contents' => fopen($file->getPath() . '/' . $file->getFilename(), 'r')
            ]
        ];
        $response = $this->client->post($url, $this->optionsWrapper([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->security->getUser()->getTokenApi()
            ],
            'multipart' => $resource
        ]));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Return the first element of get list result
     *
     * @param string $url
     * @param array $filters
     * @param bool $secure
     * @param string $token_api
     * @return array|null
     * @throws GuzzleException
     *
     * @example $service->getOne('/profiles', ['userFunction.code'=>[4,5],'page'=>3])
     */
    public function getOne(string $url, array $filters = [], bool $secure = true, string $token_api = ""): ?array
    {
        $filters = array_merge($filters, ['page' => 1, 'itemsPerPage' => 1]);
        $result = $this->verbGet($url, $filters, $secure, $token_api);

        if (isset($result) && isset($result[self::HYDRA_TOTAL_ITEMS]) && $result[self::HYDRA_TOTAL_ITEMS] > 0) {
            return array_shift($result[self::HYDRA_MEMBER]);
        }

        return null;
    }

    /**
     * Make API get request
     */
    public function verbGet(string $url, array $resource = [], bool $secure = true, string $token_api = ""): mixed
    {
        $api_url = $this->url . $this->getUrlWithFilters($url, $resource);
        $this->setAuthorizationHeader($secure, $token_api);
        $response = $this->client->get($api_url, $this->optionsWrapper([
            'headers' => $this->headers
        ]));

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Build http query string from filter array and return request iri with filters query string
     * Note: not support two level array key => value. See examples
     *
     * @param string $url
     * @param array $filters
     * @return string
     * @example
     *      usage: $this->buildHttpQuery(['userFunction.code'=>[4,5],'page'=>3])
     *      Result ==> page=1&userFunction.code[]=4&userFunction.code[]=5
     *
     */
    private function getUrlWithFilters(string $url, array $filters): string
    {
        if (!count($filters)) {
            return $url;
        }

        $urlInfo = parse_url($url);
        $path = $urlInfo['path'];
        $query = $urlInfo['query'] ?? '';
        $queryArray = $query ? explode('&', $query) : [];
        $queries = [];
        foreach ($queryArray as $queryValue) {
            $queryValue = explode('=', $queryValue);
            if (isset($queryValue[0])) {
                $key = $queryValue[0];
                if (str_ends_with($key, '[]')) {
                    $queries[$key][] = $queryValue[1] ?? null;
                } else {
                    $queries[$key] = $queryValue[1] ?? null;
                }
            }
        }
        $queries = array_merge($queries, $filters);
        $query = preg_replace('/%5B%5D%5B[0-9]+%5D/', '[]', http_build_query($queries));

        return $path . '?' . $query;
    }

    /**
     * Return all elements presents in hydra::member
     * @param string $url
     * @param array $filters
     * @param bool $secure
     * @param string $token_api
     * @return array|null
     * @throws GuzzleException
     *
     * TODO: add pagination option to ensure pagination is disabled for the request
     */
    public function getAll(string $url, array $filters = [], bool $secure = true, string $token_api = ""): ?array
    {
        $result = $this->verbGet($url, $filters, $secure, $token_api);

        if (isset($result) && isset($result[self::HYDRA_TOTAL_ITEMS]) && $result[self::HYDRA_TOTAL_ITEMS] > 0) {
            return $result[self::HYDRA_MEMBER];
        }

        return null;
    }

    /**
     * Return total of elements presents in hydra::member
     *
     * @param string $url
     * @param array $filters
     * @param boolean $secure
     * @param string $token_api
     * @return int|null
     */
    public function getTotal(string $url, array $filters = [], bool $secure = true, string $token_api = ""): ?int
    {
        $result = $this->verbGet($url, $filters, $secure, $token_api);

        if (isset($result) && isset($result[self::HYDRA_TOTAL_ITEMS])) {
            return $result[self::HYDRA_TOTAL_ITEMS];
        }

        return null;
    }

}
