<?php

namespace Ter\Common\Model;


use InvalidArgumentException;

abstract class AbstractApiResponse
{
    public function __construct(protected ?array $response)
    {
    }

    public function __call(string $name, array $args)
    {
        if (str_starts_with($name, 'get') && count($args)) {
            throw new InvalidArgumentException('Cette méthode ne prend pas de paramètre');
        }
        if (str_starts_with($name, 'set')) {
            throw new InvalidArgumentException("Cette méthode n'est pas supporté");
        }

        $property = lcfirst(substr($name, 3, strlen($name) - 2));

        return $this->response[$property] ?? null;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

}