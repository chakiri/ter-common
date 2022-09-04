<?php

declare(strict_types=1);

namespace Ter\Common\Service;

class FeatureApiService extends ApiService
{
    public function getFeature($api_url, $resource = [], $secure = true, $token_api = "")
    {
        return $this->verbGet($api_url, $resource, $secure, $token_api);
    }

    public function postFeature($api_url, $resource = [], $secure = true, $token_api = "")
    {
        return $this->verbPost($api_url, $resource, $secure, $token_api);
    }

    public function patchFeature($api_url, $resource = [], $secure = true, $token_api = "")
    {
        return $this->verbPatch($api_url, $resource, $secure, $token_api);
    }
}