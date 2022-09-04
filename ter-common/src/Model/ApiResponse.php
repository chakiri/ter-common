<?php

declare(strict_types=1);

namespace Ter\Common\Model;

class ApiResponse extends AbstractApiResponse
{
    public const HYDRA_MEMBER = 'hydra:member';
    public const HYDRA_TOTAL_ITEMS = 'hydra:totalItems';
    public const HYDRA_VIEW = 'hydra:view';
    public const HYDRA_FIRST = 'hydra:first';
    public const HYDRA_PREVIOUS = 'hydra:previous';
    public const HYDRA_NEXT = 'hydra:next';
    public const HYDRA_LAST = 'hydra:last';
    public const PAGE_KEY = 'page';
    public const ERROR_KEY = 'error';

    public function getTotalItems(): int
    {
        return $this->response[ApiResponse::HYDRA_TOTAL_ITEMS] ?? 0;
    }

    public function getItems(): iterable
    {
        return $this->response[ApiResponse::HYDRA_MEMBER] ?? [];
    }

    public function getView(): iterable
    {
        $view = $this->response[ApiResponse::HYDRA_VIEW] ?? null;

        if ($view) {
            $first = $this->getViewPage($view[self::HYDRA_FIRST] ?? null);
            $previous = $this->getViewPage($view[self::HYDRA_PREVIOUS] ?? null);
            $next = $this->getViewPage($view[self::HYDRA_NEXT] ?? null);
            $last = $this->getViewPage($view[self::HYDRA_LAST] ?? null);

            $page = $next ? ($next - 1) : $last;

            return array_merge($view, [
                'first' => $first,
                'page' => $page,
                'previous' => $previous,
                'next' => $next,
                'last' => $last,
            ]);
        }
        return null;
    }

    private function getViewPage(?string $uri): ?int
    {
        if(!$uri){
            return null;
        }
        $array = parse_url($uri);
        parse_str($array['query'] ?? '', $array);
        return (int)($array[self::PAGE_KEY] ?? 0);
    }

    public function hasView(): bool
    {
        return array_key_exists(self::HYDRA_VIEW, $this->response);
    }

    public function hasError(): bool
    {
        return array_key_exists(self::ERROR_KEY, $this->response);
    }

    public function getFirst(): mixed
    {
        if(isset($this->response[self::HYDRA_MEMBER])){
            return array_shift($this->response[self::HYDRA_MEMBER]);
        }

        return null;
    }

    public function get($key)
    {
        $resource = $this->getResponse();
        return $resource[$key] ?? null;
    }

    /**
     * Return resource iri
     *
     * @return mixed|null
     */
    public function getIri()
    {
        $resource = $this->getResponse();
        return $resource['@id'] ?? null;
    }

    /**
     * @return array
     */
    public function getViolations(): array
    {
        $violations= $this->get('violations');
        if(!$violations){
            $response = $this->getResponse();

            if ($response && array_key_exists('violations', $response)){
                foreach ($response['violations'] as $violation){
                    $violations[$violation['propertyPath']] = $violation['message'];
                }
            }
        }

        return $violations ?? [];
    }

}