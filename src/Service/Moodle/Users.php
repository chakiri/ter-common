<?php

declare(strict_types=1);

namespace Ter\Common\Service\Moodle;

use GuzzleHttp\Client;

final class Users
{

  private const MOODLE_API_FUNCTION = [
    'get_users' => 'core_user_get_users',
  ];
  private const MOODLE_API_FORMAT = 'json';

  private Client $client;

  public function __construct(private string $urlApiMoodle, private string $keyApiMoodle)
  {
    $this->client = new Client();
  }

  public function getUsers(string $action, array $extraParams = []): ?array
  {
    $resourceBase = [
      'wstoken' => $this->keyApiMoodle,
      'wsfunction' =>  self::MOODLE_API_FUNCTION[$action],
      'moodlewsrestformat' => self::MOODLE_API_FORMAT,
    ];
    $resources = array_merge($resourceBase, $extraParams);

    $response = $this->client->get($this->urlApiMoodle, [
      'query' => $resources,
        'proxy' => ''
    ]);

    return json_decode($response->getBody()->getContents(), true);
  }

  public function formatArrayUsersById(array $users): ?array
  {
    $usersById = [];
    foreach ($users as $user) {
      $usersById[$user['id']] = $user;
    }
    return $usersById;
  }
  
}
