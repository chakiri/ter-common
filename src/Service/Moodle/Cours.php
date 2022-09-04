<?php

declare(strict_types=1);

namespace Ter\Common\Service\Moodle;

use GuzzleHttp\Client;

final class Cours
{

    private const MOODLE_API_FUNCTION = [
        'get_courses' => 'core_course_get_courses',
        'get_courses_full' => 'core_course_get_courses_by_field',
        'get_categories' => 'core_course_get_categories',
        'create_course' => 'core_course_create_courses',
        'delete_course' => 'core_course_delete_courses',
        'get_contents' => 'core_course_get_contents',
    ];

    private const PLATFORM_NAME = 'platform';
    private const PLATFORM_VALUE = 'e-learning';
    public const MODULE_TYPE_VALUE = 'scorm';

    private const MOODLE_API_FORMAT = 'json';

    private Client $client;

    public function __construct(private string $urlApiMoodle, private string $keyApiMoodle)
    {
        $this->client = new Client();
    }

    public function getCourses(string $action, array $extraParams = []): ?array
    {
        $resourceBase = $this->getResourcesBase($action);
        $resources = array_merge($resourceBase, $extraParams);

        $response = $this->client->get($this->urlApiMoodle, [
            'query' => $resources,
            'proxy' => ''
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getResourcesBase($action = ''): array
    {
        return [
            'wstoken' => $this->keyApiMoodle,
            'wsfunction' => self::MOODLE_API_FUNCTION[$action],
            'moodlewsrestformat' => self::MOODLE_API_FORMAT,
        ];
    }

    public function createCourse(array $resources): ?array
    {
        $resourceBase = $this->getResourcesBase('create_course');
        $resources['courses'][0]['format'] = 'singleactivity';
        $resources['courses'][0]['customfields'] = [
            [
                'shortname' => self::PLATFORM_NAME,
                'value' => self::PLATFORM_VALUE,
            ]
        ];
        $resources['courses'][0]['courseformatoptions'] = [
            [
                'name' => 'activitytype',
                'value' => self::MODULE_TYPE_VALUE,
            ]
        ];

        $resources = array_merge($resourceBase, $resources);
        $response = $this->client->get($this->urlApiMoodle, [
            'query' => $resources,
            'proxy' => ''
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteCourse(int $id): ?array
    {
        $resourceBase = $this->getResourcesBase('delete_course');
        $resources = array_merge($resourceBase, ['courseids' => [$id]]);
        $response = $this->client->get($this->urlApiMoodle, [
            'query' => $resources,
            'proxy' => ''
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }
}
