<?php

declare(strict_types=1);

namespace Ter\Common\Twig;

use Twig\Extension\RuntimeExtensionInterface;

abstract class AppRuntime implements RuntimeExtensionInterface
{
    public const RESOURCE_COURSE = 'courses';
    private const ASSETS_PATHS = [
        'default' => '',
        AppRuntime::RESOURCE_COURSE => '/uploads/images/courses'
    ];

    public function __construct(
        private string $apiUrl,
    )
    {
    }

    /**
     * Return absolute path for the app uploaded assets
     *
     * @param $fileName
     * @param string $pathKey
     * @return string|null
     */
    public function appAsset(?string $fileName, string $pathKey = 'default'): ?string
    {
        if (!$fileName) {
            return null;
        }
        $path = AppRuntime::ASSETS_PATHS[$pathKey] ?? null;

        return $path ? $this->apiUrl . $path . '/' . $fileName : null;
    }

}
