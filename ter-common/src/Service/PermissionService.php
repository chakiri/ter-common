<?php

declare(strict_types=1);

namespace Ter\Common\Service;

use App\Service\Api\FeatureApiService;
use App\Service\Api\LoginService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

/**
 * User Permission service
 */
class PermissionService
{
    public const SESSION_SERVICES_PERMISSION_KEY = 'services';
    public const FEATURE_E_LEARNING = 'e-learning';
    public const FEATURE_SIMULATION = 'simulation-retraite';
    public const FEATURE_MEETING = 'rendez-vous-expert';
//    public const FEATURE_ = 'comment-bien-se-preparer-a-la-retraite';
//    public const FEATURE_ = 'bilan-retraite';
    public const FEATURE_CATEGORY_MEETING_CODE = 1;

    public function __construct(
        private SessionInterface $session,
        private LoginService     $userService,
        private FeatureApiService $featureApiService,
        private Security $security
    )
    {
    }

    /**
     * Get user services permissions
     * Refresh from api if not in session
     *
     * @return mixed
     */
    public function getServicesPermissions(): mixed
    {
        if (!$this->session->has(self::SESSION_SERVICES_PERMISSION_KEY)) {
            $permissions = $this->refreshPermissions();
        } else {
            $permissions = $this->session->get(self::SESSION_SERVICES_PERMISSION_KEY);
        }

        return is_string($permissions) ? json_decode($permissions, true) : $permissions;
    }


    /**
     * Refresh session with new permissions
     * @return array|mixed|null
     */
    public function refreshPermissions(): mixed
    {
        try {
            $permissions = $this->userService->get('/permissions')->getFirst();

            if (isset($permissions[self::SESSION_SERVICES_PERMISSION_KEY])) {
                $this->session->set(self::SESSION_SERVICES_PERMISSION_KEY,
                    json_encode($permissions[self::SESSION_SERVICES_PERMISSION_KEY]));
            }

            return $permissions;
        } catch (ClientException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
        }

        return null;
    }


    /**
     * @param $featureId
     * array|mixed|null
     */
    public function incrementNbUsingFeature($featureId)
    {
        try{
            //call api
            $packageFeatureProfile = $this->featureApiService->get('/increment_nb_using/' . $this->security->getUser()->getProfile()['id'] . '/' . $featureId);

            //Refresh session
            $this->refreshPermissions();

        }catch (ClientException $e){
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }catch (GuzzleException $e){
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return $packageFeatureProfile;
    }

    /**
     * @param string $featureSlug
     * @return bool
     */
    private function canUseFeature(string $featureSlug): bool
    {
        $permissions = $this->getServicesPermissions();

        if (!$permissions || !isset($permissions['packageFeatures'])) {
            return false;
        }

        foreach ($permissions['packageFeatures'] as $packageFeature) {
            if ($featureSlug === $packageFeature['featureSlug'] &&  $packageFeature['canUseFeature']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $featureCategoryCode
     * @return bool
     */
    private function canUseCategory(int $featureCategoryCode): bool
    {
        $permissions = $this->getServicesPermissions();

        if ($permissions && isset($permissions['packageFeatures'])){
            foreach ($permissions['packageFeatures'] as $packageFeature){
                if ($featureCategoryCode === $packageFeature['featureCategoryCode'] && $packageFeature['canUseFeature']){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canUseELearning(): bool
    {
        return $this->canUseFeature(self::FEATURE_E_LEARNING);
    }

    /**
     * @return bool
     */
    public function canUseMeeting(): bool
    {
        return $this->canUseCategory(self::FEATURE_CATEGORY_MEETING_CODE);
    }

    /**
     * @return bool
     */
    public function canUseSimulation(): bool
    {
        return $this->canUseFeature(self::FEATURE_SIMULATION);
    }
}
