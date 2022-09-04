<?php

declare(strict_types=1);

namespace Ter\Common\Service\Moodle;

use Exception;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Player actions services
 */
final class MoodleCoursePlayerService
{
    public function __construct(
        private Security         $security,
        private MoodleApiService $moodleApiService,
    )
    {
    }

    //TODO: fix session regenerate error on first authentication
    public function getPlayerUrl(int $scoid, int $cm, int $courseId, ?UserInterface $user = null, $mode = 'review', $display = 'popup'): bool|string
    {
        $user = $user ?? $this->security->getUser();
        $profile = $user->getProfile();

        try {
            $formData = [
                'user[firstname]' => $profile['firstname'],
                'user[lastname]' => $profile['lastname'],
                'user[username]' => $user->getEmail(),
                'user[email]' => $user->getEmail(),
            ];
            //login url and register if user does not exist
            $response = $this->moodleApiService->post(MoodleApiService::FUNCTION_USER_KEY, $formData);
            $loginUrl = $response->getLoginurl() ?? null;
        } catch (Exception $ex) {
            return false;
        }

        if (!isset($loginUrl)) {
            return false;
        }

        $playerQuery = http_build_query(['scoid' => $scoid, 'cm' => $cm, 'id' => $courseId, 'mode' => $mode, 'display' => $display]);
        $wantsUrl = $this->moodleApiService->getUrlMoodle() . '/mod/scorm/player.php?' . $playerQuery;
        $path = '&wantsurl=' . urlencode($wantsUrl);

        $loginUrl = str_replace($this->moodleApiService->urlApiMoodle(), $this->moodleApiService->getUrlMoodle(), $loginUrl);

        return $loginUrl . '?' . $path;
    }

}
