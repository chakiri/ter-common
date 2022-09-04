# Ter Common

>Le but de cette librairie est de mutualiser les services en common entre les différentes plateforms TER

## Prérequis
    Symfony 5 
    PHP 7.* ou plus

## Structure
* [ter-common](../ter-common)
    * [.docker](./.docker): configuration docker
    * [src](./src)
        * [Model](./src/Model): Model non persistés en base de données
        * [Service](./src/Service): Emplacement des services
            * [Moodle](./src/Service/Moodle): Moodle service files dir
        * [Twig](./src/Twig): Extensions Twig
    * [README.md](./README.md): Documentation

## Installation
### Ajouter ter/common à composer.json
>Les lignes suivantes doivent être ajouté à votre fichier composer.json
``` json
{
    "repositories": [
      {
        "type": "vcs",
        "url": "https://gitlabdev.s2h.corp/sogbonin/ter-common.git"
      }
    ],
    "require": {
        "ter/common": "dev-master",    
    }
}
```
### Ajouter et configurer les services
``` yaml
services:
    Ter\Common\:
        resource: '../vendor/ter/common/src/'
        exclude:
            - '../vendor/ter/common/src/Entity/'
            - '../vendor/ter/common/src/Model/'
    Ter\Common\Service\Moodle\MoodleApiService:
        arguments:
            $urlMoodle: '%env(string:URL_MOODLE)%'
            $urlApiMoodle: '%env(resolve:URL_API_MOODLE)%'
            $userKeyApiMoodle: '%env(resolve:MOODLE_AUTH_USERKEY_REQUEST_LOGIN_TOKEN)%'
            $tokenApiMoodle: '%env(resolve:MOODLE_API_TOKEN)%'

```

### Mettre à jour ter/common
``` shell
composer update ter/common
```

## Développement
### Ajouter/Modifier un service, une classe
> Dans un premier temps ajouter le service dans le projet de départ, développer et tester.<br/>
> Une fois terminé, l'ajout du service peut se faire dans ce projet dans une branche.<br/>
> Par exemple:
>* Branche dans ter-common: nouveau_dev
>* composer.json: changer "ter/common": "dev-master" à  "ter/common": "dev-nouveau_dev"
>* Mettre à jour ter/common: ``` composer update ter/common```
   > Une fois les tests terminé, faire une demande de merge dans sur la branche develpment sur ter-common

## Fonctionnalités
### Les services
* [ApiService](./src/Service/ApiService.php): effectuer des requetes vers [ter-api](https://itineraireretraite.api.dev.s2h.corp/api)
* [MoodleApiService](./src/Service/Moodle/MoodleApiService.php): effectuer des requetes vers My E-Learning (Moodle)
