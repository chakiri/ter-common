<?php

namespace Ter\Common\Service;

class ApiError
{

    public function violations($response): array
    {
        if ($response && array_key_exists('violations', $response)){
            foreach ($response['violations'] as $violation){
                $violations[$violation['propertyPath']] = $violation['message'];
            }
        }

        return $violations ?? [];
    }

    public function error($response): string
    {
        $error = "Une erreur est survenue. Veuillez réessayer";

        if ($response && array_key_exists('violations', $response))
            $error = "Le formulaire contient des champs invalides. Veuillez les corriger.";
        elseif ($response && array_key_exists('hydra:description', $response))
            $error = $response['hydra:description'];

        return $error;
    }

}