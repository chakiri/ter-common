<?php

declare(strict_types=1);

namespace Ter\Common\Service;

class LoginService extends ApiService
{
    public function getLogin($api_url, $resource = [], $secure = true, $token_api = "")
    {
        return $this->verbGet($api_url, $resource, $secure, $token_api);
    }

    public function postLogin($api_url, $resource = [], $secure = true, $token_api = "")
    {
        return $this->verbPost($api_url, $resource, $secure, $token_api);
    }
}