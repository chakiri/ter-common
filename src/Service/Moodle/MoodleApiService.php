<?php

declare(strict_types=1);

namespace Ter\Common\Service\Moodle;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GzzRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Ter\Common\Model\MoodleApiResponse;

final class MoodleApiService
{
    private const WEBSERVICE_URI = '/webservice/rest/server.php';
    private const MOODLE_API_FORMAT = 'json';

    //Moodle functions names
    public const FUNCTION_USER_KEY = 'auth_userkey_request_login_url';
    public const FUNCTION_ENROL = 'enrol_manual_enrol_users';
    public const FUNCTION_USER_COURSES = 'core_enrol_get_users_courses';
    public const FUNCTION_USER_BY_FIELD = 'core_user_get_users_by_field';

    public const ROLE_STUDENT = 5;

    private const PLATFORM_NAME = 'platform';
    private const PLATFORM_VALUE = 'e-learning';

    private const MODULE_TYPE_VALUE = 'scorm';


    private array $proxySetting = ['proxy' => ''];

    private Client $client;

    /**
     * @var string[]
     */
    private array $headers = [];

    public function __construct(
        private string $useProxy,
        private string $urlMoodle,
        private string $urlApiMoodle,
        private string $userKeyApiMoodle,
        private string $tokenApiMoodle,
    )
    {
        $this->client = new Client();
    }

    /**
     * @param UserInterface $user
     * @param $redirectTo
     * @return string|null
     */
    public function getLoginUrl(UserInterface $user, $redirectTo = null): ?string
    {
        $profile = $user->getProfile();

        $formData = [
            'user[firstname]' => $profile['firstname'],
            'user[lastname]' => $profile['lastname'],
            'user[username]' => $user->getEmail(),
            'user[email]' => $user->getEmail(),
        ];

        $response = $this->post(MoodleApiService::FUNCTION_USER_KEY, $formData);
        $loginUrl = null;
        if ($response && !empty($response->getLoginurl())) {
            $loginInfos = json_decode($response->getLoginurl(), true);
            $loginUrl = $loginInfos['loginurl'] ?? null;
        }
        if ($redirectTo) {
            $path = '&wantsurl=' . urlencode($redirectTo);
            $loginUrl = str_replace($this->urlApiMoodle(), $this->getUrlMoodle(), $loginUrl);

            $loginUrl = $loginUrl . '?' . $path;
        }

        return $loginUrl;
    }

    public function post(string $functionName, array $formData = [], string $token = null): MoodleApiResponse
    {
        $options = [
            'form_params' => $formData
        ];
        $request = new GzzRequest(
            Request::METHOD_POST,
            $this->getApiUrl($functionName, [], $token),
            array_merge($this->headers, ['Content-Type' => 'application/x-www-form-urlencoded'])
        );
        $response = $this->client->sendAsync($request, $this->optionsWrapper($options))->wait();

        return new MoodleApiResponse(json_decode($response->getBody()->getContents(), true) ?? []);
    }

    /**
     * @param string $functionName
     * @param array $queries
     * @param string|null $token
     * @return string
     */
    private function getApiUrl(string $functionName, array $queries = [], string $token = null): string
    {
        if (!$token) {
            if (self::FUNCTION_USER_KEY === $functionName) {
                $token = $this->userKeyApiMoodle;
            } else {
                $token = $this->tokenApiMoodle;
            }
        }

        $baseQueries = [
            'wstoken' => $token,
            'moodlewsrestformat' => self::MOODLE_API_FORMAT,
            'wsfunction' => $functionName,
        ];
        $queries = array_merge($baseQueries, $queries);

        $query = '?' . http_build_query($queries);

        return $this->urlApiMoodle . self::WEBSERVICE_URI . $query;
    }

    /**
     * Info: this function wrap user query string with bases required queries
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

    public function urlApiMoodle(): string
    {
        return $this->urlApiMoodle;
    }

    public function getUrlMoodle(array $queries = []): string
    {
        $query = count($queries) ? '?' . http_build_query($queries) : '';

        return $this->urlMoodle . $query;
    }
}
