<?php

namespace modules\seo\components;

use modules\chromedata\models\ChromedataVehicle;
use modules\seo\controllers\frontend\DefaultController;
use modules\seo\models\Route;
use modules\seo\models\SeoCatalog;
use modules\users\models\User;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Request;
use yii\web\UrlManager;
use yii\base\BaseObject;
use Yii;
use yii\web\UrlRuleInterface;

/**
 * Class UrlRule
 * @package modules\seo\components
 */
class UrlRule extends BaseObject implements UrlRuleInterface
{
    /**
     * @var string
     */
    public $connectionID = 'db';

    /**
     * @param UrlManager $manager
     * @param string $route
     * @param array $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        /**
         * User module create urls
         */
        if ($route === 'users/user/view') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return $role . '/' . $params['state'] . '/' . $params['node'];
            }
        }

        if ($route === 'users/user/menu') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/menu';
            }
        }


        if ($route === 'users/user/garage') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/garage';
            }
        }

        if ($route === 'users/user/saved-cars') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                $type = isset($params['type']) ? '?type=' . $params['type'] : '';
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/saved-cars' . $type;
            }
        }

        if ($route === 'users/user/messages') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                $status = isset($params['status']) ? '?status=' . $params['status'] : '';
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/messages' . $status;
            }
        }

        if ($route === 'users/user/reviews') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/reviews';
            }
        }

        if ($route === 'users/user/requests') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                $submitted = (isset($params['submitted'])) ? '?submitted=' . $params['submitted'] : '';
                $page = (isset($params['page'])) ? 'page=' . $params['page'] : '';
                if (!empty($page)) {
                    if (empty($submitted)) {
                        $page = '?' . $page;
                    } else {
                        $page = '&' . $page;
                    }
                }
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/requests' . $submitted . $page;
            }
        }

        if ($route === 'users/user/liverequests') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                $submitted = (isset($params['submitted'])) ? '?submitted=' . $params['submitted'] : '';
                $page = (isset($params['page'])) ? 'page=' . $params['page'] : '';
                if (!empty($page)) {
                    if (empty($submitted)) {
                        $page = '?' . $page;
                    } else {
                        $page = '&' . $page;
                    }
                }
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/liverequests' . $submitted . $page;
            }
        }

        if ($route === 'users/user/public-reviews') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return $role . '/' . $params['state'] . '/' . $params['node'] . '/reviews';
            }
        }

        if ($route === 'users/user/settings') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/settings';
            }
        }

        if ($route === 'users/user/vehicles') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/vehicles';
            }
        }

        if ($route === 'users/user/prospects') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                $status = isset($params['status']) ? '?status=' . $params['status'] : '';
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/prospects' . $status;
            }
        }

        if ($route === 'users/user/tools') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/tools';
            }
        }

        if ($route === 'users/user/request') {
            if (isset($params['role'], $params['state'], $params['node'], $params['id'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/request/' . $params['id'];
            }
        }
        if ($route === 'users/user/referral') {
            if (isset($params['role'], $params['state'], $params['node'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/referral';
            }
        }
        if ($route === 'users/user/request-edit') {
            if (isset($params['role'], $params['state'], $params['node'], $params['id'])) {
                $role = ($params['role'] !== 'dealer') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/request-edit/' . $params['id'];
            }
        }
        if ($route === 'users/user/offer') {
            if (isset($params['role'], $params['state'], $params['node'], $params['id'])) {
                $role = ($params['role'] === 'superadmin') ? 'user' : $params['role'];
                $role = mb_substr($role, 0, 1);
                return 'account/' . $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/offer/' . $params['id'];
            }
        }


        /**
         * Lease module create urls
         */

        if ($route === 'lease/lease/view') {
            if (isset($params['state'], $params['node'], $params['role'])) {
                $role = ($params['role'] == 'dealer') ? 'new-lease' : 'lease-transfer';
                return $role . '/' . $params['state'] . '/' . $params['node'];
            }
        }

        if ($route === 'lease/lease/update') {
            if (isset($params['state'], $params['node'], $params['role'])) {
                $role = ($params['role'] == 'dealer') ? 'new-lease' : 'lease-transfer';
                return $role . '/' . $params['state'] . '/' . $params['node'] . '/edit/update';
            }
        }

        /**
         *  Information Pages create urls
         */
        if ($route === 'cars/info/view') {
            if (isset($params['node'])) {
                return 'i/' . $params['node'];
            }
        }


        /**
         *  Request Pages create urls
         */
        if ($route === 'carbuilder/requests/summary') {
            if (isset($params['node'])) {
                return 'request-lease/' . $params['node'];
            }
        }


        return false;
    }
    
    public static function redirectOldUrlOnInfoCatalog($url){
		//1. Get Route
		$route = Route::findRouteByUrl($url);
		if (!$route) {
			return false;
		}
		//2. Get Routes params
		$params = Json::decode($route['params']);
		//3. Put Routes params into edmundsToCrhomedata comparator
		//4. Matched params returned
		$matched_params = ChromedataVehicle::edmundsToChromedataComparator($params);
		//5. Send matched params into redirect

		return ["seo/default/redirect", $matched_params];
	}

    /**
     * @param UrlManager $manager
     * @param Request $request
     * @return array|bool|void
     * @throws InvalidConfigException
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();

        $hostname = $request->getHostName();
        $parts = explode('.', $hostname);


        if (preg_match('%^ignite\/liverequest/(?P<url>[\s_A-Za-z-0-9-]+)?%', $pathInfo, $matches)) {
            $url = ($matches['url']);
            //$parts = explode('-',$url);
            //$id = array_pop($parts);

            $params['node'] = $url;
            $params['id'] = $url;

            //$params['routeorder'] = $route['order'];

            return ['dealerinventory/default/liverequest', $params];
        }


        if (preg_match('%^(?P<role>u|d)\/(?P<state>[A-Za-z]{2})\/(?P<url>[._\sA-Za-z-0-9-]+)?%', $pathInfo, $matches)) {
            $route = Route::findRouteByUrl($matches['url']);

            if (!$route) {
                /** 301 redirect for search listing old URLs */
                if ($matches['role'] == 'd') {
                    $find_url = 'new-lease' . substr($pathInfo, 1);
                } else {
                    $find_url = 'lease-transfer' . substr($pathInfo, 1);
                }
                if ($route = Route::findRouteByUrl($find_url)) {
                    Yii::$app->getResponse()->redirect('/' . $find_url, 301, false)->send();
                    return;
                }
            }
            if ($route->route == 'lease/lease/view') {
                /** 301 redirect for lease page old URLs */
                if ($matches['role'] == 'd') {
                    $find_url = 'new-lease' . substr($pathInfo, 1);
                } else {
                    $find_url = 'lease-transfer' . substr($pathInfo, 1);
                }
                Yii::$app->getResponse()->redirect('/' . $find_url, 301, false)->send();
                return;
            }
            if (!$route) {
                return false;
            }
            $params = [
                'node' => $matches['url'],
                'role' => $matches['role'],
                'state' => $matches['state']
            ];

            if (!empty($route['params'])) {
                $params = array_merge($params, json_decode($route['params'], true));
            }
            return [$route['route'], $params];
        }

        /**
         * Parse request for search URLs with location and year
         */
        if (preg_match(
            '%^(?P<role>lease-transfer|new-lease)\/(?P<state>[A-Za-z]{2})\/(?P<url>[._\sA-Za-z-0-9-]+)\/(?P<year>\d{4})?%',
            $pathInfo,
            $matches
        )) {
            return self::redirectOldUrlOnInfoCatalog($pathInfo);
        }

        /**
         * Parse request for search URLs with location and with year
         */
        if (preg_match(
            '%^(?P<role>lease-transfer|new-lease)\/(?P<url>[._\sA-Za-z-0-9-]+)\/(?P<year>\d{4})%',
            $pathInfo,
            $matches
        )) {
            return self::redirectOldUrlOnInfoCatalog($pathInfo);
        }

        /**
         * 301 Redirect for old search URLs with location and with year
         */
        if (preg_match('%^(?P<role>u|d)\/(?P<url>[._\sA-Za-z-0-9-]+)\/(?P<year>\d{4})%', $pathInfo, $matches)) {
            /** 301 redirect for search listing old URLs */
            if ($matches['role'] == 'd') {
                $find_url = 'new-lease' . substr($pathInfo, 1);
            } else {
                $find_url = 'lease-transfer' . substr($pathInfo, 1);
            }
            if ($route = Route::findRouteByUrl($find_url)) {
                Yii::$app->getResponse()->redirect('/' . $find_url, 301, false)->send();
                return false;
            } else {
                return false;
            }
        }

        /**
         * Parse request for leases URLs and search URLs with location
         */
        // ^(?P<state>[A-Za-z]{2})\/(?P<lease>[\w-0-9]+)$
        if (preg_match(
            '%^(?P<role>lease-transfer|new-lease)\/(?P<state>[A-Za-z]{2})\/(?P<url>[_A-Za-z-0-9-]+)?%',
            $pathInfo,
            $matches
        )) {
            return self::redirectOldUrlOnInfoCatalog($matches['url']);
        }

        /**
         * Parse request for search URLs without location and year
         */
        if (preg_match('%^(?P<role>lease-transfer|new-lease)\/(?P<url>[._\sA-Za-z-0-9-]+)?%', $pathInfo, $matches)) {
            return self::redirectOldUrlOnInfoCatalog($pathInfo);
        }

        /**
         * Redirect for old lease urls
         */
        if (preg_match('%^(?P<state>[A-Za-z]{2})\/(?P<url>[_A-Za-z-0-9-]+)?%', $pathInfo, $matches)) {
            $route = Route::findRouteByUrl($matches['url']);
            if (!$route) {
                return false;
            }
            if ($route->route == 'lease/lease/view') {
                /** 301 redirect for lease page old URLs */
                $find_url = '/lease-transfer/' . $pathInfo;
                Yii::$app->getResponse()->redirect($find_url, 301, false)->send();
                return false;
            } else {
                return false;
            }
        }

        /**
         * Parse request for Information pages URLs
         */
        if (preg_match('%^i\/(?P<url>[_A-Za-z-0-9-]+)?%', $pathInfo, $matches)) {
			return self::redirectOldUrlOnInfoCatalog($matches['url']);
        }

        /**
         * Parse request for Request Summary Pages URLs
         */
        if (preg_match('%^request-lease\/(?P<url>[_A-Za-z-0-9-]+)?%', $pathInfo, $matches)) {
            return self::redirectOldUrlOnInfoCatalog($matches['url']);
        }
//var_dump(preg_match('%^ignite\/(?P<url>[+_A-Za-z-0-9-]+)?%', $pathInfo, $matches));


        return false;
    }
}
