<?php

namespace modules\seo;

use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * Blogs module bootstrap class.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        // Add module URL rules.
        $app->getUrlManager()->addRules(
            [
                'find' => 'seo/default/tolocation',
                'find/<state\w{1,4}>' => 'seo/default/find',
                'find/<state\w{1,4}>/<city:[^/]+>' => 'seo/default/find',
                'find/<state\w{1,4}>/<city:[^/]+>/<make:[^/]+>' => 'seo/default/find',
                'find/<state\w{1,4}>/<city:[^/]+>/<make:[^/]+>/<model:[^/]+>' => 'seo/default/find',
                'find/<state\w{1,4}>/<city:[^/]+>/<make:[^/]+>/<model:[^/]+>/<year:\d{4}>' => 'seo/default/find',
                'find/<state\w{1,4}>/<city:[^/]+>/<make:[^/]+>/<model:[^/]+>/<year:\d{4}>/<trim:[^/]+>' => 'seo/default/find',
            ]
        );
    }
}
