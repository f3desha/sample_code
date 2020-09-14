<?php

use modules\dealerinventory\models\backend\Dealerinventory;
use modules\themes\admin\widgets\Box;
use modules\themes\admin\widgets\GridView;
use modules\tracking\models\backend\Tracking;
use modules\users\models\backend\User;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use modules\dealerinventory\Module;
use modules\tracking\TrackingAsset;
use yii\helpers\Url;

TrackingAsset::register($this);

$user_id = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
$user = User::find()->where(["id" => $user_id])->limit(1)->one();

if ($user->isRole('administrateDealers') || $user->isRole('moderator')) {
    $show_user = true;
} else {
    $show_user = false;
}

$this->title = Module::t('tracking', 'BACKEND_TRACKING_TITLE');
//$this->params['subtitle'] = Module::t('dealerinventory', 'BACKEND_TRACKING_SUBTITLE');



?>


<?php /*  lead */
if (!is_null($lead)):
   $date_lead = date('m/d/Y', $lead->created_at);

    ?>
    <table class="table table-bordered table-hover track_table">

        <tr>
            <td> First Name</td>
            <td> Last Name</td>
            <td> Email</td>
            <td> ZIP</td>
            <td> Phone</td>
            <td> Date</td>

        </tr>
        <tr>
            <td> <?= $lead->name ?> </td>
            <td> <?= $lead->surname ?> </td>
            <td> <?= $lead->email ?> </td>
            <td> <?= $lead->zip ?> </td>
            <td> <?= $lead->phone ?> </td>
            <td> <?=  $date_lead ?> </td>

        </tr>
    </table>
    <?php
endif;
/*  lead */
?>



    <table class="table table-bordered table-hover track_table">
        <caption> Unlocked Lease Price</caption>
        <tr>
            <td> Id</td>
            <td> Year</td>
            <td> Make</td>
            <td> Model</td>
            <td> Trim</td>
            <td> Link</td>
            <td> IP Adress</td>
            <td> Date</td>
            <td> Duration</td>
        </tr>
	    
	    <?php
		    if (!empty($leads)){
			    foreach($leads as $lead){
				
				    $dealerinventory = Dealerinventory::findOne($lead->dealerinventory_id);
				    $lead_car = Tracking::find()->where(["lead_id"=>$lead->id])->limit(1)->one();
				    $date_di = date('m/d/Y', $lead->created_at);
				    
				    if($user->role == 'dealer' && empty(strpos($lead_car->car_link, $user->username)) ){
				    	continue;
				    }
				    
				    if(!empty($lead_car->car_link)){
					    echo "<tr>";
					    echo "<td>$lead_car->id</td>";
					    echo "<td>$dealerinventory->year</td>";
					    echo "<td>$dealerinventory->make</td>";
					    echo "<td>$dealerinventory->model</td>";
					    echo "<td>$dealerinventory->trim</td>";
					    echo "<td> <a target='_blank' href='$lead_car->car_link'>$lead_car->car_link</a> </td>";
					    echo "<td>$lead_car->ip_address</td>";
					    echo "<td>$date_di</td>";
					    echo "<td>$lead_car->duration</td>";
					    echo "</tr>";
				    }
			    	
			    }
		    }
	    ?>
	    </table>

<?php
$gridId = 'tracking-grid';
$gridConfig = [
    'id' => $gridId,
    'class'=>"track_grid",
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
          'id',
        //  'cookie_user_id:ntext',
       // 'user_id',

       /* [
            'attribute'=>'dealerinventory_id',
            'value' => 'dealerinventory_id',
            'headerOptions' => ['style' => 'width:2%'],
        ],*/
        // 'page_id',
        // 'lead_id',

        [
            'attribute'=>'year',
            'value' => 'dealerinventory.year',
            'format' => 'html',
            'contentOptions'=>['style'=>'max-width: 20px;'] // <-- right here

        ],
        [
            'attribute'=>'make',
            'value' => 'dealerinventory.make',

        ],
        [
            'attribute'=>'model',
            'value' => 'dealerinventory.model',

        ],
        [
            'attribute'=>'trim',
            'value' => 'dealerinventory.trim',

        ],

[
    'attribute'=>'link_from',
    'format' => 'raw',
    'value' => function ($model) {
        $link="<a  class='kkk' target='_blank' href=".$model->car_link."> $model->car_link </a>";
       return $link;
    }
],
        'ip_address',
        [
            'attribute' => 'created_at',
            'format' => ['date', 'php:m/d/Y']
        ],
        'duration',
    ],

];

$boxButtons = $actions = [];
$showActions = false;


if ($showActions === true) {
    $gridConfig['columns'][] = [
        'class' => ActionColumn::className(),
        'template' => implode(' ', $actions)
    ];
}
$boxButtons = !empty($boxButtons) ? implode(' ', $boxButtons) : null;

Box::begin(
    [
        'title' => !empty($this->params['subtitle']) ? $this->params['subtitle'] : '',
        'bodyOptions' => [
            'class' => ''
        ],
        'buttonsTemplate' => $boxButtons,
        'grid' => $gridId
    ]
);


echo GridView::widget($gridConfig);

Box::end();
