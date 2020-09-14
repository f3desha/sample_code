<?php

namespace modules\tracking;

use modules\dealerinventory\models\backend\Dealerinventory;
use yii\base\BootstrapInterface;

/**
 * Class Bootstrap
 * @package modules\tracking
 * Blogs module bootstrap class.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        // Add module URL rules.
        $app->getUrlManager()->addRules(
            [
               // 'tracking' => 'tracking/default/index',
            ]
        );


		// Add module I18N category.
		if (!isset($app->i18n->translations['modules/tracking']) && !isset($app->i18n->translations['modules/*'])) {
			$app->i18n->translations['modules/tracking'] = [
				'class' => 'yii\i18n\PhpMessageSource',
				'basePath' => '@modules/tracking/messages',
				'forceTranslation' => true,
				'fileMap' => [
					'modules/tracking' => 'tracking.php',
				]
			];
		}

    }
}
