<?php

namespace modules\seo\helpers;

use modules\dealerinventory\models\backend\DealerinventoryPhotos;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @package modules\users\helpers
 */
class Meta
{


    /**
     * @var array
     */
    private static $img_params = [
        'thumbnail' => [
            'width' => 458,
            'height' => 330,
        ],
        'placeholder' => [
            'width' => 458,
            'height' => 330
        ],
        'watermark' => []
    ];

    /**
     * Generate meta title, keywords, description and return title string.
     *
     * @param string $type Type of page for which keywords are made
     * @param object $model
     * @return string $title Title of page
     */
    public static function all($type, $model = null)
    {
        $title = 'Carvoy | A new generation of leasing a car'; // Default title.
        Yii::$app->view->registerMetaTag(
            [
                'charset' => Yii::$app->charset
            ]
        );
        $owner = Yii::$app->domain->owner;

        switch ($type) {
            case 'dealerinventory':
                $photos = $model->getPhotos()->orderBy(['main' => SORT_DESC])->all();

                $title = $model->make . ' - ' . $model->model . ' - ' . $model->year;
                Yii::$app->view->registerMetaTag(
                    ['name' => 'keywords', 'content' => $model->year . ',' . $model->make . ',' . $model->model]
                );
                Yii::$app->view->registerMetaTag(
                    ['name' => 'description', 'content' => $model->year . ' ' . $model->make . ' ' . $model->model]
                );

                $photo_url = !empty($photos[0]->url) ? $photos[0]->url : '';
                DealerinventoryPhotos::findPhoto($photo_url, true);
                Yii::$app->view->registerMetaTag(['property' => 'og:image', 'content' => $photo_url]);
                Yii::$app->view->registerMetaTag(['property' => 'og:url', 'content' => Url::to('', true)]);
                Yii::$app->view->registerMetaTag(['property' => 'og:site_name', 'content' => $owner->getOwnersName()]);

                Yii::$app->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'photo']);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:title', 'content' => Html::encode($title)]);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:image', 'content' => $photo_url]);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:site', 'content' => Url::home(true)]);
                break;
            case 'home':
                $title = 'Carvoy | A new generation of leasing a car';
                $image = Url::to('/statics/images/carvoy.png', true);

                Yii::$app->view->registerMetaTag(['name' => 'keywords', 'content' => 'lease, car, transfer']);
                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'description',
                        'content' => 'Carvoy - Change the way you lease. Lease your next new car online and we\'ll deliver it to your doorstep.'
                    ]
                );

                Yii::$app->view->registerMetaTag(['property' => 'og:image', 'content' => $image]);
                Yii::$app->view->registerMetaTag(['property' => 'og:url', 'content' => Url::to('', true)]);
                Yii::$app->view->registerMetaTag(['property' => 'og:site_name', 'content' => Yii::$app->name]);

                Yii::$app->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'photo']);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:title', 'content' => Html::encode($title)]);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:image', 'content' => $image]);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:site', 'content' => Url::home(true)]);
                break;
            case 'blog':
                $title = $model->title;
                Yii::$app->view->registerMetaTag(['property' => 'og:url', 'content' => Url::canonical()]);
                Yii::$app->view->registerMetaTag(['property' => 'og:site_name', 'content' => Yii::$app->name]);
                if ($model->preview_url) {
                    try {
                        if (self::isValidURL(Url::to($model->urlAttribute('preview_url'), true))) {
                            Yii::$app->view->registerMetaTag(
                                [
                                    'property' => 'og:image',
                                    'content' => Url::to($model->urlAttribute('preview_url'), true)
                                ]
                            );
                            Yii::$app->view->registerMetaTag(['property' => 'og:image:width', 'content' => '300']);
                            Yii::$app->view->registerMetaTag(['property' => 'og:image:height', 'content' => '150']);
                        } else {
                            Yii::$app->view->registerMetaTag(
                                [
                                    'property' => 'og:image',
                                    'content' => rtrim(Url::home(true), '/') . Yii::$app->thumbnail->url(
                                            'http://dummyimage.com/220x168&text=NoImage',
                                            self::$img_params
                                        )
                                ]
                            );
                        }
                    } catch (Exception $e) {
                    }
                }
                Yii::$app->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'photo']);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:title', 'content' => Html::encode($title)]);
                if ($model->preview_url) {
                    if (self::isValidURL(self::isValidURL(Url::to($model->urlAttribute('preview_url'), true)))) {
                        Yii::$app->view->registerMetaTag(
                            ['name' => 'twitter:image', 'content' => Url::to($model->urlAttribute('preview_url'), true)]
                        );
                    } else {
                        Yii::$app->view->registerMetaTag(
                            [
                                'property' => 'twitter:image',
                                'content' => rtrim(Url::home(true), '/') . Yii::$app->thumbnail->url(
                                        'http://dummyimage.com/220x168&text=NoImage',
                                        self::$img_params
                                    )
                            ]
                        );
                    }
                }
                Yii::$app->view->registerMetaTag(['name' => 'twitter:site', 'content' => Url::home(true)]);
                Yii::$app->view->registerMetaTag(['property' => 'twitter:url', 'content' => Url::canonical()]);
                Yii::$app->view->registerMetaTag(['property' => 'twitter:domain', 'content' => Url::home(true)]);
                break;
            case 'lease':
                $title = $model->make . ' - ' . $model->model . ' - ' . $model->year . ' - ' . $model->exterior_color . ' - ' . $model->engineFuelType . ' for lease in ' . $model->location;
                $h1 = $model->year . ' ' . $model->make . ' ' . $model->model . ' - ' . $model->location . ' - For Lease';

                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'keywords',
                        'content' => Html::encode(
                            $model->year . ', ' . $model->make . ', ' . $model->model . ', ' . $model->exterior_color . ', ' . $model->engineFuelType . ', ' . $model->location . ', for, lease'
                        )
                    ]
                );
                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'description',
                        'content' => Html::encode(
                            $model->year . ' ' . $model->make . ' ' . $model->model . ' ' . $model->exterior_color . ' ' . $model->engineFuelType . ' for lease in ' . $model->location
                        )
                    ]
                );

                Yii::$app->view->registerMetaTag(['property' => 'og:title', 'content' => Html::encode($h1)]);
                if ($model->leaseMainPhoto) {
                    try {
                        if (self::isValidURL($model->leaseMainPhoto->url)) {
                            Yii::$app->view->registerMetaTag(
                                [
                                    'property' => 'og:image',
                                    'content' => rtrim(Url::home(true), '/') . Yii::$app->thumbnail->url(
                                            $model->leaseMainPhoto->url,
                                            self::$img_params
                                        )
                                ]
                            );
                        } else {
                            Yii::$app->view->registerMetaTag(
                                [
                                    'property' => 'og:image',
                                    'content' => rtrim(Url::home(true), '/') . Yii::$app->thumbnail->url(
                                            'http://dummyimage.com/220x168&text=NoImage',
                                            self::$img_params
                                        )
                                ]
                            );
                        }
                    } catch (Exception $e) {
                    }
                }
                Yii::$app->view->registerMetaTag(['property' => 'og:url', 'content' => Url::to('', true)]);
                Yii::$app->view->registerMetaTag(['property' => 'og:site_name', 'content' => Yii::$app->name]);

                Yii::$app->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'photo']);
                Yii::$app->view->registerMetaTag(['name' => 'twitter:title', 'content' => Html::encode($h1)]);
                if ($model->leaseMainPhoto) {
                    if (self::isValidURL($model->leaseMainPhoto->url)) {
                        Yii::$app->view->registerMetaTag(
                            [
                                'name' => 'twitter:image',
                                'content' => rtrim(Url::home(true), '/') . Yii::$app->thumbnail->url(
                                        $model->leaseMainPhoto->url,
                                        self::$img_params
                                    )
                            ]
                        );
                    } else {
                        Yii::$app->view->registerMetaTag(
                            [
                                'property' => 'twitter:image',
                                'content' => rtrim(Url::home(true), '/') . Yii::$app->thumbnail->url(
                                        'http://dummyimage.com/220x168&text=NoImage',
                                        self::$img_params
                                    )
                            ]
                        );
                    }
                }
                Yii::$app->view->registerMetaTag(['name' => 'twitter:site', 'content' => Url::home(true)]);

                Yii::$app->view->registerMetaTag(['property' => 'twitter:url', 'content' => Url::to('', true)]);
                Yii::$app->view->registerMetaTag(['property' => 'twitter:domain', 'content' => Url::home(true)]);
                break;
            case 'seocatalog':
                $title = $model->seodata['title'];
                Yii::$app->view->registerMetaTag(
                    ['name' => 'description', 'content' => $model->seodata['description']]
                );
                break;
            case 'info_page':
                $title = $model->make . ' - ' . $model->model . ' - ' . $model->year;
                $h1 = $model->year . ' ' . $model->make . ' ' . $model->model;

                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'keywords',
                        'content' => Html::encode($model->year . ', ' . $model->make . ', ' . $model->model)
                    ]
                );
                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'description',
                        'content' => Html::encode($model->year . ' ' . $model->make . ' ' . $model->model)
                    ]
                );

//                Yii::$app->view->registerMetaTag(['property' => 'og:title','content' => Html::encode($h1)]);
//                if (isset($model->leaseMainPhoto))
//                    Yii::$app->view->registerMetaTag(['property' => 'og:image','content' => rtrim(Url::home(true), '/') . \Yii::$app->thumbnail->url($model->leaseMainPhoto->url, self::$img_params)]);
//                Yii::$app->view->registerMetaTag(['property' => 'og:url','content' => Url::to('', true)]);
//                Yii::$app->view->registerMetaTag(['property' => 'og:site_name','content' => \Yii::$app->name]);
//
//                Yii::$app->view->registerMetaTag(['name' => 'twitter:card','content' => 'photo']);
//                Yii::$app->view->registerMetaTag(['name' => 'twitter:title','content' => Html::encode($h1)]);
//                if (isset($model->leaseMainPhoto))
//                    Yii::$app->view->registerMetaTag(['name' => 'twitter:image','content' => rtrim(Url::home(true), '/') . \Yii::$app->thumbnail->url($model->leaseMainPhoto->url, self::$img_params)]);
//                Yii::$app->view->registerMetaTag(['name' => 'twitter:site','content' => Url::home(true)]);
//
//                Yii::$app->view->registerMetaTag(['property' => 'twitter:url','content' => Url::to('', true)]);
//                Yii::$app->view->registerMetaTag(['property' => 'twitter:domain','content' => Url::home(true)]);
                break;
            case 'user':
                $name = ($model->role === 'dealer') ? $model->profile->company_name : $model->profile->name;

                $title = $name . ' - ' . $model->location;

                Yii::$app->view->registerMetaTag(
                    ['name' => 'keywords', 'content' => Html::encode($name . ', ' . $model->location)]
                );
                Yii::$app->view->registerMetaTag(
                    ['name' => 'description', 'content' => Html::encode($name . ' ' . $model->location)]
                );
                break;
            case 'search':
                if ($model['role'] == 'd') {
                    $role = 'Dealer Lease';
                } elseif ($model['role'] == 'u') {
                    $role = 'Lease Transfers';
                } else {
                    $role = 'All Leases';
                }

                if (isset($model['make']) && isset($model['model'])) {
                    $_make = (is_array($model['make'])) ? ((isset($model['make']) && (count(
                                $model['make']
                            ) == 1)) ? $model['make'][0] : false) : $model['make'];
                    $_model = (is_array($model['model'])) ? ((isset($model['model']) && (count(
                                $model['model']
                            ) == 1)) ? $model['model'][0] : false) : $model['model'];
                    $_year = false;
                    $_location = false;

                    if (isset($model['year'])) {
                        $_year = (is_array($model['year'])) ? ((isset($model['year']) && (count(
                                    $model['year']
                                ) == 1)) ? $model['year'][0] : false) : $model['year'];
                    }
                    if (isset($model['location'])) {
                        $_location = (is_array($model['location'])) ? ((isset($model['location']) && (count(
                                    $model['location']
                                ) == 1)) ? $model['location'][0] : false) : $model['location'];
                    }

                    if (($_make || $_model) && !(isset($model['make']) && (count($model['make']) > 1))) {
                        $title = $_make . (($_model) ? ' ' . $_model : '') . (($_year) ? ' ' . $_year : '') . ' for Lease' . (($_location) ? ' in ' . $_location . '. ' : '. ') . $role . '.';
                    } else {
                        $title = 'Vehicle for Lease' . (($_location) ? ' in ' . $_location . '. ' : '. ') . $role . '.';
                    }

                    Yii::$app->view->registerMetaTag(
                        [
                            'name' => 'keywords',
                            'content' => Html::encode(
                                ltrim(
                                    $_make . (($_model) ? ', ' . $_model : '') . (($_year) ? ', ' . $_year : '') . ', for, Lease' . (($_location) ? ', in, ' . $_location : '') . ', ' . implode(
                                        ', ',
                                        (explode(' ', $role))
                                    ),
                                    ', '
                                )
                            )
                        ]
                    );
                    Yii::$app->view->registerMetaTag(
                        [
                            'name' => 'description',
                            'content' => Html::encode(
                                'List of ' . ((!$_model && !$_make) ? 'Vehicles' : '') . $_make . (($_model) ? ' ' . $_model : '') . (($_year) ? ' ' . $_year : '') . (($_location) ? ' in ' . $_location : '') . ' available for lease. ' . $role . '.'
                            )
                        ]
                    );
                } else {
                    $title = 'Search results';
                }
                break;

            case 'carbuilder':
                $title = 'Car Builder';
                Yii::$app->view->registerMetaTag(['name' => 'keywords', 'content' => 'Car, Builder']);
                Yii::$app->view->registerMetaTag(['name' => 'description', 'content' => 'Car, builder']);
                break;

            case 'summary':
                $title = $model->vehicle->make . ' ' . $model->vehicle->model . ' ' . $model->vehicle->trim . ' ' . $model->vehicle->year . ' ' . $model->exterior_color . ' ' . $model->id;
                $optionsStr = '';
                if ($model->packages || $model->options) {
                    foreach ($model->packages as $package) {
                        $optionsStr .= ', ' . $package->name;
                    }
                    foreach ($model->options as $option) {
                        $optionsStr .= ', ' . $option->name;
                    }
                }
                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'keywords',
                        'content' => $model->vehicle->make . ', ' . $model->vehicle->model . ', ' . $model->vehicle->trim . ', ' . $model->vehicle->year . ', ' . $model->interior_color . ', ' . $model->exterior_color . $optionsStr
                    ]
                );
                Yii::$app->view->registerMetaTag(
                    [
                        'name' => 'description',
                        'content' => $model->vehicle->make . ' ' . $model->vehicle->model . ' ' . $model->vehicle->trim . ' ' . $model->vehicle->year . ', ' . $model->interior_color . ', ' . $model->exterior_color . $optionsStr
                    ]
                );
                break;
        }

        return $title;
    }

    /**
     * @param $url
     * @return bool
     */
    public static function isValidURL($url)
    {
        $file_headers = @get_headers($url);
        if (strpos($file_headers[0], "200 OK") > 0) {
            return true;
        } else {
            return false;
        }
    }

}
