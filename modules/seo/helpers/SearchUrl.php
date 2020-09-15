<?php

namespace modules\seo\helpers;

use modules\seo\models\Route;
use modules\zipdata\models\Zip;
use Yii;

/**
 * @package modules\users\helpers
 */
class SearchUrl
{

    /**
     * Generate Search Url by params.
     *
     * @param array $params Params
     * @param bool $make_link If true - make a tag with link
     * @return string $url Url of search page
     */
    public static function createUrl($params, $make_link = false)
    {
        if (!empty($params['url'])) {
            $params['url'] = str_replace(' ', '_', $params['url']);
            if ($search_url = Route::findRouteByUrl($params['url'])) {
                return '/' . $params['url'];
            } else {
                $route = new Route();
                $route->url = str_replace(' ', '_', substr($params['url'], 1));
                $route->route = 'lease/search/index';
                $route->params = json_encode(
                    ['make' => $params['make'], 'model' => $params['model'], 'location' => $params['location']]
                );
                $route->save();
                return '/' . $params['url'];
            }
        }

        if (isset($params['type']) && in_array($params['type'], ['user', 'dealer'])) {
            $type = ($params['type'] == 'dealer') ? 'new-lease' : 'lease-transfer';
        } else {
            return false;
        }

        if ((isset($params['zip']) && !empty($params['zip'])) || (isset($params['location']) && isset($params['state']))) {
            // make model price zip type
            if (isset($params['zip']) && !empty($params['zip'])) {
                $zipdata = Zip::findOneByZip($params['zip']);
            } else {
                $zipdata = Zip::findOneByLocation($params['location'], $params['state']);
            }
            // city state_code
            if (!empty($zipdata)) {
                $url = $type . '/' . $zipdata['state_code'] . '/' . $params['make'] . '-' . $params['model'] . '-' . $zipdata['city'];
                if (!empty($params['year'])) {
                    $url .= '/' . $params['year'];
                }
                $url = str_replace(' ', '_', $url);
                if ($search_url = Route::findRouteByUrl($url)) {
                    return '/' . $url;
                } else {
                    $route = new Route();
                    $route->url = str_replace(' ', '_', $url);
                    $route->route = 'lease/search/index';
                    $pars = [
                        'make' => $params['make'],
                        'model' => $params['model'],
                        'location' => $zipdata['city'],
                        'state' => $zipdata['state_code']
                    ]; //, 'zip'=>$params['zip'] ];
                    if (!empty($params['year'])) {
                        $pars['year'] = $params['year'];
                    }
                    $route->params = json_encode($pars);
                    $route->save();
                    return $route->url;
                }
            }
        }
        if (isset($params['make'], $params['model'])) {
            $url = $type . '/' . $params['make'] . '-' . $params['model'];
            if (!empty($params['year'])) {
                $url .= '/' . $params['year'];
            }
            $url = str_replace(' ', '_', $url);

            if ($search_url = Route::findRouteByUrl($url)) {
                return '/' . $url;
            } else {
                $route = new Route();
                $route->url = str_replace(' ', '_', $url);
                $route->route = 'lease/search/index';
                $pars = ['make' => $params['make'], 'model' => $params['model']];
                if (!empty($params['year'])) {
                    $pars['year'] = $params['year'];
                }
                $route->params = json_encode($pars);
                $route->save();
                return $route->url;
            }
        }
        //return false;
    }

}
