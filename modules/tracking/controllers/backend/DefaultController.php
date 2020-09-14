<?php

namespace modules\tracking\controllers\backend;


use modules\admin\components\Controller;
use modules\dealerinventory\models\backend\IgniteLead;
use modules\dealerinventory\models\backend\IgniteLeadSearch;
use modules\tracking\models\backend\Tracking;
use modules\tracking\models\backend\TrackingSearch;
use modules\tracking\models\backend\TrackingSession;
use modules\tracking\models\backend\TrackingSessionSearch;
use modules\users\models\backend\DealershipGroup;
use modules\users\models\backend\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\data\Pagination;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\HttpException;

/**
 * Class DefaultController
 * @package modules\tracking\controllers\backend
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['rules'] = [
            [
                'actions' => ['login', 'error'],
                'allow' => true,
            ],
        ];

        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => ['index'],
            'roles' => ['viewTracking'],
        ];

        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => ['update', 'delete', 'batch-delete'],
            'roles' => ['updateTracking'],
        ];

        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => ['index', 'delete', 'batch-delete', 'timeline'],
            'roles' => ['dealer', 'viewTracking'],
        ];

        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => ['ignite-items', 'view'],
            'roles' => ['viewOwnTracking'],
            'matchCallback' => function ($rule, $action) {

                $model = $this->findModel(Yii::$app->getRequest()->get('id'));
                $user = User::findOne(Yii::$app->user->id);

                if ($user->hasRole('dealer-moderator')) {

                    $hasSubdomainDealers = User::find()->where(['role' => 'dealer'])->orWhere(['role' => 'dealer-manager'])->andWhere(['<>', 'username', ''])->all();
                    $ids = [];
                    foreach ($hasSubdomainDealers as $dealer) {
                        $ids[] = $dealer->id;
                    }

                    return in_array($model->user_id, $ids);

                } elseif ($user->hasRole('dealer-group-manager')) {
                        $group = DealershipGroup::findOne($user->dealer_id)->group;
                        $ids = [];
                        foreach ($group as $dealership) {
                            $ids[] = $dealership->id;
                        }
                        $hasDealers = User::find()->where(['role' => 'dealer'])->orWhere(['role' => 'dealer-manager'])
                            ->andWhere(['<>', 'username', ''])
                            ->andWhere(['IN', 'dealer_id', $ids])->all();
                        $ids2 = [];
                        foreach ($hasDealers as $dealer) {
                            $ids2[] = $dealer->id;
                        }

                        return in_array($model->user_id, $ids2);
                    } elseif ($user->hasRole('dealer') || $user->hasRole('dealer-manager')) {

                            $hasDealers = User::find()->where(['role' => 'dealer'])->orWhere(['role' => 'dealer-manager'])
                                ->andWhere(['<>', 'username', ''])
                                ->andWhere(['dealer_id' => $user->dealer_id])->all();

                            $ids = [];

                            foreach ($hasDealers as $dealer) {
                                $ids[] = $dealer->id;
                            }

                            return in_array($model->user_id, $ids);
                        } elseif ($user->hasRole('marketplace-manager')) {
                                return empty($model->user_id);
                            } elseif ($user->hasRole('marketplace-client-manager')) {
                                    return empty($model->user_id);
                                }





                return true;
            },
        ];


        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => ['importlead'],
            'roles' => ['updateUsers'],
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'index' => ['get'],
                'update' => ['get', 'put', 'post'],
                'delete' => ['post', 'delete'],
            ],
        ];

        return $behaviors;
    }

    /**
     * @return string
     */
    public function actionIndex()
    {

        $searchModel = new IgniteLeadSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        $dataProvider->setPagination(['pageSize' => 20]);

        return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]
        );

    }

    /**
     * @param $id
     *
     * @return string
     */
    public function actionIgniteItems($id)
    {

        $view_data = TrackingSession::getTrackingView($id);

        return $this->render('igniteitems', [
            'lead' => $view_data['lead'],
            'viewed_items_models' => $view_data['viewed_items_models'],
            'viewed_items_pages' => $view_data['viewed_items_pages'],
            'unlocked_items_models' => $view_data['unlocked_items_models'],
            'unlocked_items_pages' => $view_data['unlocked_items_pages'],
        ]);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function actionTimeline($id)
    {
        $searchModel = new TrackingSessionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        $query = $dataProvider;
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count(), 'defaultPageSize' => 10]);
        $models = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        return $this->render('timeline', [
            'models' => $models,
            'pages' => $pages,
        ]);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function actionView($id)
    {

        $lead_car = Tracking::find()->where(["lead_id" => $id])->limit(1)->one();

        $lead = IgniteLead::findOne($id);

        $leads = IgniteLead::find()->where(["email" => $lead->email])->orderBy(["created_at" => SORT_DESC])->innerJoin('{{%dealerinventory}}', 'dealerinventory_id={{%dealerinventory}}.id')->all();

        $searchModel = new TrackingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());
        $dataProvider->setSort(['defaultOrder' => ['created_at' => SORT_DESC]]);
        $dataProvider->setPagination(['pageSize' => 20]);

        return $this->render('view', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'lead_car' => $lead_car,
                'lead' => $lead,
                'leads' => $leads,
            ]
        );

    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws HttpException
     */
    public function actionBatchDelete()
    {

        if (($ids = Yii::$app->request->post('ids')) !== null) {
            $models = $this->findModel($ids);
            foreach ($models as $model) {
                $model->delete();
            }

            return $this->redirect(['index']);
        } else {
            throw new HttpException(400);
        }
    }


    /**
     * @param $id
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws HttpException
     */
    public function actionDelete($id)
    {
        $lead = $this->findModel($id);
        if ($lead->delete()) {
            $this->redirect('index');
        }
    }

    /**
     * @param $id
     *
     * @return IgniteLead[]|IgniteLead
     * @throws HttpException
     */
    protected function findModel($id)
    {

        if (is_array($id)) {
            $model = IgniteLead::findIdentities($id);

        } else {
            $model = IgniteLead::findIdentity($id);
        }
        if ($model !== null) {
            return $model;
        } else {
            throw new HttpException(404);
        }
    }

    /**
     * @param $to
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public function actionImportlead($to)
    {
        exit();
        ini_set('max_execution_time', 0);
        $dir_path = Yii::getAlias('@user_import_excel');
        FileHelper::createDirectory($dir_path);
        if ($to == 'excel') {
            $connection = Yii::$app->getDb();
            $command = $connection->createCommand("SELECT max(" . IgniteLead::tableName() . ".id) AS id FROM " . IgniteLead::tableName() . " where email NOT IN (SELECT email FROM " . User::tableName() . " WHERE role='user') GROUP BY email ORDER BY id DESC");
            $leads = $command->queryAll();
            $i = 1;
            $file_name = '/leads_' . date('m-d-y-' . time()) . '.xlsx';
            $file_path = $dir_path . $file_name;
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A' . $i, 'Username of dealer');
            $sheet->setCellValue('D' . $i, 'First Name');
            $sheet->setCellValue('C' . $i, 'Last Name');
            $sheet->setCellValue('D' . $i, 'Email');
            $sheet->setCellValue('E' . $i, 'Phone');
            $sheet->setCellValue('F' . $i, 'Tracking Link for lead');
            $sheet->setCellValue('G' . $i, 'Unlock Payment Date');
            $sheet->setCellValue('H' . $i, 'Unlock Payment');
            foreach ($leads as $lead) {
                $user = IgniteLead::find()->select("min(" . IgniteLead::tableName() . ".created_at) AS created, " . IgniteLead::tableName() . ".email, " . IgniteLead::tableName() . ".name, " . IgniteLead::tableName() . ".surname, " . IgniteLead::tableName() . ".phone, " . User::tableName() . ".username")->leftJoin(User::tableName(), User::tableName() . '.id=' . IgniteLead::tableName() . '.user_id')->where(['=', IgniteLead::tableName() . '.id', $lead['id']])->limit(1)->asArray(true)->one();
                $user['traking_url'] = Url::toRoute(['/tracking/default/view', 'id' => $lead['id']], true);
                $user['created'] = date('F, jS Y h:i:s A', $user['created']);
                $sheet->setCellValue('A' . ($i + 1), $user['username']);
                $sheet->setCellValue('B' . ($i + 1), $user['name']);
                $sheet->setCellValue('C' . ($i + 1), $user['surname']);
                $sheet->setCellValue('D' . ($i + 1), $user['email']);
                $sheet->setCellValue('E' . ($i + 1), $user['phone']);
                $sheet->setCellValue('F' . ($i + 1), $user['traking_url']);
                $sheet->setCellValue('G' . ($i + 1), $user['created']);
                $sheet->setCellValue('H' . ($i + 1), "ON");
                $i++;
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save($file_path);
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file_path));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit();
        }
    }
}
