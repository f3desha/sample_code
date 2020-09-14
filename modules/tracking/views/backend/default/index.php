<?php

	use common\components\F3deshaHelpers;use modules\dealerinventory\models\backend\Pricing;
	use modules\themes\admin\widgets\Box;
use modules\themes\admin\widgets\GridView;
use modules\users\models\backend\User;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use modules\dealerinventory\Module;


$user_id = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
$user = User::find()->where(["id" => $user_id])->limit(1)->one();


if ($user->isRole('administrateDealers') ) {
    $show_user = true;
} else {
    $show_user = false;
}

$this->title = Module::t('dealerinventory', 'BACKEND_LEAD_TITLE');
$this->params['subtitle'] = Module::t('dealerinventory', 'BACKEND_LEAD_SUBTITLE');

$gridId = 'bankfee-grid';
$gridConfig = [
    'id' => $gridId,
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
            'class' => CheckboxColumn::classname()
        ],
        'name',
        'surname',
        'email',
        'phone',
        'zip',
        [
            'label' => 'Dealership',
            'attribute' => 'user_id',
            'value' => 'user.dealership.name',
            'filter' => Html::activeDropDownList(
				$searchModel,
				'user_id',
				F3deshaHelpers::getDealers(),
				['class' => 'form-control', 'prompt' => 'Dealer']
			),
            'visible' => $show_user
        ],
        [
            'label' => 'Created At',
            'attribute' => 'max_created',
            'value' => 'max_created',
            'format' => ['date', 'php:m/d/Y']
        ],
    ],

];

$boxButtons = $actions = [];
$showActions = false;

if (Yii::$app->user->can('viewTracking')) {
	$actions[] = '{newtrack}';
    $showActions = $showActions || true;
}

if (Yii::$app->user->can('deleteTracking')) {
    $actions[] = '{delete}';
    $showActions = $showActions || true;
}

if (Yii::$app->user->can('deleteTracking')) {

    $boxButtons[] = '{batch-delete}';
    $showActions = $showActions || true;
}



if ($showActions === true) {
    $gridConfig['columns'][] = [
        'class' => ActionColumn::className(),
		'buttons' => [
			'newtrack'=>function ($url, $model) {
				
					return Html::tag('a','<span class="fa fa-search"></span>',['title' => 'Tracking', 'href'=>\yii\helpers\Url::toRoute(['default/ignite-items', 'id' => $model->id])]);
			},
		],
        'template' => implode(' ', $actions)
    ];
}
$boxButtons = !empty($boxButtons) ? implode(' ', $boxButtons) : null; ?>

<div class="row">
    <div class="col-xs-12">
        <?php Box::begin(
            [
                'title' => $this->params['subtitle'],
                'bodyOptions' => [
                    'class' => ''
                ],
                'buttonsTemplate' => $boxButtons,
                'grid' => $gridId
            ]
        ); ?>
<!--        <a class='btn' href="/backend/web/tracking/default/importlead?to=excel">Import data to EXCEL</a>-->
<!--        <br><br>-->
        <?php echo GridView::widget($gridConfig);

        Box::end(); ?>
    </div>
</div>
