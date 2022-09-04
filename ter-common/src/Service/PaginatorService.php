<?php

declare(strict_types=1);

namespace Ter\Common\Service;

use Ter\Common\Model\ApiResponse;

class PaginatorService
{
    public const SEPARATOR = '...';
    public const FIRST_PAGE = 1;

    public function getPaginationData(ApiResponse $apiResponse): array
    {
        $visiblePages = [];
        $page = self::FIRST_PAGE;
        $adjacentPages = 4;

        if ($apiResponse->hasView()) {
            $view = $apiResponse->getView();
            $page = $view['page'];
            $last = $view['last'];

            $visiblePages = range(self::FIRST_PAGE, $last);

            if (($adjacentPages = (int)floor($adjacentPages / 2) * 2 + 1) >= 1) {
                $visiblePages = array_slice(
                    $visiblePages,
                    intval(max(0, min(
                        count($visiblePages) - $adjacentPages,
                        intval($page) - ceil($adjacentPages / 2)
                    ))),
                    $adjacentPages
                );
            }
            if (max($visiblePages) < $last - 1) {
                $visiblePages[] = self::SEPARATOR;
            }
            if (max($visiblePages) <= ($last - 1)) {
                $visiblePages[] = $last;
            }
            if (
                min(array_filter($visiblePages, function ($item) {
                    return $item !== self::SEPARATOR;
                }))
                > (self::FIRST_PAGE + 1)
            ) {
                array_unshift($visiblePages, self::SEPARATOR);
            }
            if (
                min(array_filter($visiblePages, function ($item) {
                    return $item !== self::SEPARATOR;
                }))
                >= (self::FIRST_PAGE + 1)
            ) {
                array_unshift($visiblePages, self::FIRST_PAGE);
            }
        }

        $visiblePages = count($visiblePages)  ? $visiblePages : [PaginatorService::FIRST_PAGE];
        $visiblePages = array_filter($visiblePages, function($item){return $item>0;});

        return [
            'page' => $page,
            'totalItems' => $apiResponse->getTotalItems(),
            'visiblePages' => $visiblePages,
        ];
    }
}
