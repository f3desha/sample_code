<?php

namespace modules\seo\controllers\frontend;

use common\components\F3deshaHelpers;
use modules\seo\models\SeoCatalog;
use modules\seo\models\Sitemap;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use modules\dealerinventory\models\backend\Dealerinventory;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class DefaultController
 * @package modules\seo\controllers\frontend
 */
class DefaultController extends Controller
{

    /**
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $sitemap = new Sitemap();
        if (empty($sitemap_owner = Yii::$app->domain->owner->getSubdomainName())) {
            $sitemap_owner = 'carvoy';
        }

        $sitemap_postfix = !empty(Yii::$app->urlManager->parseRequest(Yii::$app->request)[1]['sitemap']) ?
            Yii::$app->urlManager->parseRequest(Yii::$app->request)[1]['sitemap'] : null;

        $sitemap_root_folder = Yii::getAlias('@sitemap');
        $sitemap_owner_folder = $sitemap_root_folder . '/' . $sitemap_owner;


        $sitemap_parsed_route = $sitemap->parseRouteByPostfix($sitemap_postfix);
        $sitemap_url = $sitemap_owner_folder . $sitemap_parsed_route;

        if (file_exists($sitemap_url)) {
            $xml_sitemap = file_get_contents($sitemap_url);
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'text/xml');
            return $this->renderPartial(
                '@modules/seo/views/frontend/_index',
                array(
                    'sitemap' => $xml_sitemap
                )
            );
        } else {
            throw new NotFoundHttpException();
        }
    }
    
    public function actionRedirect(
    	$state = SeoCatalog::INFO_MODE_STATE_PLACEHOLDER, 
		$city = SeoCatalog::INFO_MODE_CITY_PLACEHOLDER,
		$make = '',
		$model = '',
		$year = '',
		$trim = ''
	){
		$prelink = Url::base(
			) . '/find/' . $state . '/' . F3deshaHelpers::encodeForUrl(
				$city
			);
		
		$params = [$make, $model, $year, $trim];
		
		foreach ($params as $param){
			if(!empty($param)){
				$prelink = F3deshaHelpers::prepareForUrl(
					$prelink . '/' . F3deshaHelpers::encodeForUrl($param)
				);
			}
		}		
		
		return $this->redirect([$prelink]);
	}

    /**
     * @param string $state
     * @return Response
     */
    public function actionTolocation($state = SeoCatalog::STATE_PLACEHOLDER)
    {
        return $this->redirect(['/find/' . $state]);
    }

    /**
     * @return Response
     */
    public function actionToyear()
    {
        return $this->redirect([Yii::$app->request->url . '/' . date("Y")]);
    }

    /**
     * @param null $state
     * @param null $city
     * @param null $make
     * @param null $model
     * @param null $year
     * @param null $trim
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionFind($state = null, $city = null, $make = null, $model = null, $year = null, $trim = null)
    {
        //Start seocatalog service. All the logic is hidden in this service
        $seocatalog = new SeoCatalog(
            [
                'state' => $state,
                'city' => $city,
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'trim' => $trim
            ]
        );

        $this->layout = $seocatalog->view_layout;

        return $seocatalog->build($this);
    }
}
