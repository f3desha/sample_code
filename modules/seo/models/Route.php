<?php

namespace modules\seo\models;

use himiklab\sitemap\behaviors\SitemapBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;
use Yii;
use yii\helpers\Url;

/**
 * Class Route
 * @package modules\seo\models
 * Saved urls
 * @property int $id ID
 * @property string $url Url
 * @property string $route Route
 * @property int $created_at Created at
 * @property int $updated_at Updated at
 * @property string $params
 * @property bool $cached [tinyint(1)]
 * @property int $results [int(11)]
 * @property int $order [int(11)]
 */
class Route extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%route}}';
    }

    /**
     * Get route by match url
     *
     * @param $url
     * @return array|Route|ActiveRecord|null
     */
    public static function findRouteByUrl($url)
    {
        $query = self::find()
            ->where(['url' => $url])
            ->limit(1)
            ->one();

        return $query;
    }

    /**
     * @param $b
     * @param bool $u
     * @return array|ActiveQuery|ActiveRecord[]
     */
    public static function getPopularSearches($b, $u = true)
    {
        $routes = Route::find()->where(['route' => 'lease/search/index', 'cached' => '1'])->andWhere(
            ['>=', 'results', '2']
        );

        if ($u) {
            $routes = $routes->andWhere(['like', 'url', 'lease-transfer/%', false]);
        } else {
            $routes = $routes->andWhere(['like', 'url', 'new-lease/%', false]);
        }

        if ($b) {
            $routes = $routes->orderBy('rand()')->limit(6);
        } else {
            $routes = $routes->orderBy('id asc');
        }

        $routes = $routes->all();
        foreach ($routes as &$r) {
            $r['params'] = json_decode($r['params'], true);
        }
        return $routes;
    }

    /**
     * Create user url
     *
     * @param string $url Url
     * @param string $route Route
     * @return bool
     */
    public static function saveUrl($url, $route)
    {
        $_route = new Route();
        $_route->url = $url;
        $_route->route = $route;

        if (empty($_route->params)) {
            $_route->params = '';
        }
        if (empty($_route->cached)) {
            $_route->cached = 0;
        }
        if (empty($_route->results)) {
            $_route->results = 0;
        }
        if (empty($_route->order)) {
            $_route->order = 0;
        }

        if ($_route->save(false)) {
            return true;
        }
        return false;
    }

    /**
     * Update count of results for Search and Info pages url
     *
     * @param string $route Route
     * @param array $params Params
     * @return bool
     */
    public static function updateResults($route, $params)
    {
        if ($route) {
            $count = Lease::find()->joinWith('user')->where($params)->andWhere(Lease::$AVAILABLE_CONDITION)->count();
            $route->results = $count ? $count : 0;
            if ($route->validate() && $route->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'timestampBehavior' => [
                'class' => TimestampBehavior::className(),
            ],

            'sitemap' => [
                'class' => SitemapBehavior::className(),
                'scope' => function ($model) {
                    /** @var ActiveQuery $model */
                    $model->select(['url', 'updated_at', 'route', 'results', 'cached']);
                    $model->where(['and', ['route' => 'lease/search/index'], ['>', 'cached', 0]]);
                    $model->orWhere(['route' => 'cars/info/view']);
                    $model->orWhere(['route' => 'carbuilder/requests/summary']);
                },
                'dataClosure' => function ($model) {
                    /** @var self $model */
                    switch ($model->route) {
                        case 'lease/search/index' :
                            $priority = ($model->results > 1) ? 0.9 : 0.4;
                            $changefreq = ($model->results > 1) ? SitemapBehavior::CHANGEFREQ_HOURLY : SitemapBehavior::CHANGEFREQ_DAILY;
                            $url = Url::to($model->url, true);
                            break;
                        case 'cars/info/view' :
                            $priority = 0.9;
                            $changefreq = SitemapBehavior::CHANGEFREQ_MONTHLY;
                            $url = Url::to('i/' . $model->url, true);
                            break;
                        default :
                            $priority = 0.8;
                            $changefreq = SitemapBehavior::CHANGEFREQ_MONTHLY;
                            $url = Url::to('request-lease/' . $model->url, true);
                    }
                    return [
                        'loc' => $url,
                        'lastmod' => $model->updated_at,
                        'changefreq' => $changefreq,
                        'priority' => $priority
                    ];
                }
            ],
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['url', 'string', 'max' => 100],
            ['route', 'string', 'max' => 50],
            ['params', 'string', 'max' => 500],
            [['url', 'route'], 'required'],
            [['url'], 'unique']
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'created_at' => 'Created at',
            'updated_at' => 'Updated at'
        ];
    }
}
