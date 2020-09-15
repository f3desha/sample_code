<?php

namespace modules\seo\models;

use common\components\F3deshaHelpers;
use common\jobs\CountSeocatalogCarsAmountJob;
use modules\carimages\models\ImportImages;
use modules\chromedata\models\CategorySpecification;
use modules\chromedata\models\ChromedataVehicle;
use modules\chromedata\models\SelectedMake;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\DealerinventoryHasGenericEquipment;
use modules\site\models\ContactForm;
use modules\tracking\models\backend\TempStorage;
use modules\users\models\backend\Dealership;
use modules\zipdata\models\Zip;
use Throwable;
use Yii;
use yii\base\Model;
use yii\data\Pagination;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\widgets\ActiveForm;

/**
 * Class SeoCatalog
 * @package modules\seo\models
 */
class SeoCatalog extends Model
{
    /**
     *
     */
    const FULL_VERSION = '1';
    const QUICK_VERSION = '2';

    /**
     *
     */
    const STATE_STAGE = 1;
    const CITY_STAGE = 2;
    const MAKE_STAGE = 3;
    const MODEL_STAGE = 4;
    const TRIM_STAGE = 5;
    const TRIM_HIGHLIGHTS_STAGE = 6;

	/**
	 * WARNING: file controllers.js contains method isInSeocatalogInfoMode() which reads url on hardcoded pattern '/find/i/a'
	 * If you will change this constant, please also change that pattern to prevent bug
	 */
	const INFO_MODE_STATE_PLACEHOLDER = 'i';

	/**
	 * WARNING: file controllers.js contains method isInSeocatalogInfoMode() which reads url on hardcoded pattern '/find/i/a'
	 * If you will change this constant, please also change that pattern to prevent bug
	 */
	const INFO_MODE_CITY_PLACEHOLDER = 'a';

    /**
     *
     */
    const SESSION_ZIP_LOCATOR_KEY = 'seocatalogzip';

    /**
     *
     */
    const TEMP_STORAGE_SEOCATALOG_CLASS = 'SEO_CATALOG';

    /**
     *
     */
    const SEOLINK_FILE_NAME = 'seocatalog_links';

    /**
     *
     */
    const SEO_UAV_AMOUNT = 40;

    /**
     *
     */
    const SEO_STATES_CACHE_KEY = '43jh3j5gj353';
    /**
     *
     */
    const SEO_STATES_COLLECTION_PREFIX = 'states_covered_collection_';
    /**
     *
     */
    const SEO_STATES_UNIQUE_PREFIX = 'states_covered_';
    /**
     *
     */
    const SEO_STATES_UNIQUE_CARS_AMOUNT_PREFIX = 'states_cars_amount_';

    /**
     *
     */
    const SEO_CITIES_UNIQUE_CARS_AMOUNT_PREFIX = 'cities_cars_amount_';

    /**
     *
     */
    const STATE_PLACEHOLDER = 'a';
    /**
     *
     */
    const CITY_PLACEHOLDER = 'a';

    public $isInfoMode = false;
    /**
     * @var mixed|null
     */
    public $stage = null;
    /**
     * @var mixed|null
     */
    public $stage_view = null;
    /**
     * @var bool|mixed
     */
    public $can_be_built = false;
    /**
     * @var array
     */
    public $config = [];
    /**
     * @var string
     */
    public $view_layout = '//home';
    /**
     * @var array
     */
    public $seodata = [
        'title' => 'Carvoy | A new generation of leasing car',
        'description' => 'The easiest way to find next car. It’s simple - browse all makes and models, choose the car you want, fill out a short form, and we’ll do the rest',
    ];

    /**
     * @var null
     */
    public $controller = null;
    /**
     * @var bool
     */
    public $reload = false;
    /**
     * @var string
     */
    public $redirect = '';

    /**
     * @var null
     */
    public $city_pages = null;

    /**
     * @var null
     */
    public $stage_based_ignite_link = null;

    /**
     * @var array
     */
    public $stage_data_provider = [];

    /**
     * SeoCatalog constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct($config = [])
    {
        $config = F3deshaHelpers::decodeUrl($config);

        $this->config = $config;

        if (!array_key_exists('version', $this->config)) {
            $this->config['version'] = self::FULL_VERSION;
        }

        $data_provider = $this->buildDataProvider();
        $this->stage = $data_provider['stage_id'];
        $this->stage_view = $data_provider['stage_view'];
        $this->can_be_built = $data_provider['can_be_built'];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function buildDataProvider()
    {
        $config = [];

        //Location data
        $this->stage_data_provider['location_data'] = [];
        $this->stage_data_provider['location_data']['state'] = !empty($this->config['state']) ? $this->config['state'] : self::STATE_PLACEHOLDER;
        $this->stage_data_provider['location_data']['city'] = !empty($this->config['city']) ? $this->config['city'] : self::CITY_PLACEHOLDER;

		if($this->searchInfoMode()){
			$this->isInfoMode = true;
		}

        $this->stage_based_ignite_link = Url::base(true) . '/' . Dealerinventory::MODULE_NAME;

        if (!empty($this->config['state']) && !empty($this->config['city']) && !empty($this->config['make']) && !empty($this->config['model']) && !empty($this->config['year']) && !empty($this->config['trim'])) {
            $config['stage_id'] = self::TRIM_HIGHLIGHTS_STAGE;
            $config['stage_view'] = 'trimhighlights';

			if($this->isInfoMode){
				$this->stage_data_provider['managers_mask'] = $this->buildManagersMaskByLocation();
				$this->stage_data_provider['available_years'] = $this->getAvailableYears();
				$config['can_be_built'] = (bool)$this->getAvailableTrims();
				$this->stage_data_provider['trim_details'] = $this->getTrimHighlightsDetails();
			} else {
				$this->changeZipBasedOnRoute();
				$this->stage_data_provider['managers_mask'] = $this->buildManagersMaskByLocation();

				$this->stage_based_ignite_link = F3deshaHelpers::prepareForUrl(
					Url::base(true) . '/' . Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::encodeForUrl(
						$this->config['make']
					) . '&model=' . F3deshaHelpers::encodeForUrl(
						$this->config['model']
					) . '&year=' . F3deshaHelpers::encodeForUrl(
						$this->config['year']
					) . '&trim=' . F3deshaHelpers::encodeForUrlIgnite($this->config['trim'])
				);
				$this->stage_data_provider['available_years'] = $this->getAvailableYears();

				$this->getAvailableModels();
				$config['can_be_built'] = (bool)$this->getAvailableTrims();

				$this->stage_data_provider['trim_details'] = $this->getTrimHighlightsDetails();
				$this->stage_data_provider['random_ignite_items'] = $this->getRandomIgniteItemsInCityByMake();
				$this->seodata = [
					'title' => "{$this->config['year']} {$this->config['make']} {$this->config['model']} {$this->config['trim']} in {$this->config['city']} - For Sale ({$this->config['state']} state)",
					'description' => "Available For Sale " . $this->getModelsCountByZip(
						) . " items {$this->config['year']} {$this->config['make']} {$this->config['model']} {$this->config['trim']} in {$this->config['city']}, {$this->config['state']} state - Carvoy"
				];
			}
			
            return $config;
        } elseif (!empty($this->config['state']) && !empty($this->config['city']) && !empty($this->config['make']) && !empty($this->config['model'])) {
            $config['stage_id'] = self::TRIM_STAGE;
            $config['stage_view'] = 'trim';


			if($this->isInfoMode){
				$this->stage_data_provider['available_years'] = $this->getAvailableYears();
				if(empty($this->stage_data_provider['available_years'])){
					$config['can_be_built'] = true;
					$this->redirect = self::routeToCatalog($this->config['state'], $this->config['city'], $this->config['make']);
				} else {
					if ($this->inFullVersion() || !empty($this->config['year'])) {

						if ($this->redirectToAvailableYear()) {
							$config['can_be_built'] = true;
						} else {
							$this->stage_data_provider['managers_mask'] = $this->buildManagersMaskByLocation();
							$config['can_be_built'] = (bool)$this->getAvailableTrims();
						}
					} elseif ($this->inQuickVersion()) {
						$config['can_be_built'] = false;
					}
				}
			} else {
				$this->changeZipBasedOnRoute();
				$this->stage_data_provider['managers_mask'] = $this->buildManagersMaskByLocation();

				$this->stage_based_ignite_link = F3deshaHelpers::prepareForUrl(
					Url::base(true) . '/' . Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::encodeForUrl(
						$this->config['make']
					) . '&model=' . F3deshaHelpers::encodeForUrl(
						$this->config['model']
					) . '&year=' . F3deshaHelpers::encodeForUrl($this->config['year'])
				);
				$this->stage_data_provider['available_years'] = $this->getAvailableYears();
				if (empty($this->stage_data_provider['available_years'])) {
					$config['can_be_built'] = true;
					$this->redirect = self::routeToCatalog($this->config['state'], $this->config['city'], $this->config['make']);
				} else {
					if ($this->inFullVersion() || !empty($this->config['year'])) {
						if ($this->redirectToAvailableYear()) {
							$config['can_be_built'] = true;
						} else {
							if ($this->inFullVersion()) {
								$this->getAvailableModels();
							}
							$config['can_be_built'] = (bool)$this->getAvailableTrims();
							$this->seodata = [
								'title' => "{$this->config['year']} {$this->config['make']} {$this->config['model']} in {$this->config['city']} - For Sale {$this->config['model']} in {$this->config['city']}, {$this->config['state']} state",
								'description' => "Available For Sale {$this->config['year']} {$this->config['make']} {$this->config['model']} Trim: " . implode(
										', ',
										ArrayHelper::getColumn($this->stage_data_provider['available_trims'], 'trim')
									) . " in {$this->config['city']}, {$this->config['state']} state"
							];
							if ($this->inFullVersion()) {
								$this->stage_data_provider['random_ignite_items'] = $this->getRandomIgniteItemsInCityByMake();
							}
						}
					} elseif ($this->inQuickVersion()) {
						$config['can_be_built'] = false;
					}

				}
			}

            return $config;
        } elseif (!empty($this->config['state']) && !empty($this->config['city']) && !empty($this->config['make'])) {
            $config['stage_id'] = self::MODEL_STAGE;
            $config['stage_view'] = 'model';

			if($this->isInfoMode){
				$config['can_be_built'] = (bool)$this->getAvailableModels();
			} else {
				$this->changeZipBasedOnRoute();
				$this->stage_data_provider['managers_mask'] = $this->buildManagersMaskByLocation();

				if ($this->inFullVersion()) {
					$this->stage_data_provider['random_ignite_items'] = $this->getRandomIgniteItemsInCityByMake();
				}
				$config['can_be_built'] = (bool)$this->getAvailableModels();
				$this->seodata = [
					'title' => "{$this->config['make']} in {$this->config['city']} - For Sale {$this->config['make']} in {$this->config['city']}, {$this->config['state']} state",
					'description' => "Available For Sale {$this->config['make']} Models: " . implode(
							', ',
							ArrayHelper::getColumn(
								$this->stage_data_provider['available_models'],
								'model'
							)
						) . " in {$this->config['city']}, {$this->config['state']} state."
				];
			}

            return $config;
        } elseif (!empty($this->config['state']) && !empty($this->config['city'])) {
            $config['stage_id'] = self::MAKE_STAGE;
            $config['stage_view'] = 'make';

			if($this->isInfoMode){
				$config['can_be_built'] = (bool)$this->getAvailableMakes();
			} else {
				$this->changeZipBasedOnRoute();

				$this->stage_data_provider['managers_mask'] = $this->buildManagersMaskByLocation();

				$config['can_be_built'] = (bool)$this->getAvailableMakes();
				$this->seodata = [
					'title' => "Find car in {$this->config['city']}, {$this->config['state']} state - Carvoy",
					'description' => "Available For Sale in {$this->config['city']}, {$this->config['state']} state Brands: " . implode(
							', ',
							ArrayHelper::getColumn($this->stage_data_provider['available_makes'], 'title')
						) . " in {$this->config['city']}, {$this->config['state']} state."
				];
			}

            return $config;
        } elseif (!empty($this->config['state']) && $this->searchInState()) {
            $config['stage_id'] = self::CITY_STAGE;
            $config['stage_view'] = 'city';
            $this->stage_data_provider['available_cities'] = $this->getAvailableCities();
            $config['can_be_built'] = (bool)$this->stage_data_provider['available_cities'];
            $this->seodata = [
                'title' => "Find car in {$this->config['state']} state - Carvoy",
                'description' => "Carvoy - Choose state to find a car for lease."
            ];
            return $config;
        } elseif (!empty($this->config['state']) && $this->searchAnywhere()) {
            $config['stage_id'] = self::STATE_STAGE;
            $config['stage_view'] = 'state';

            if ($this->searchAnywhere()) {
				$this->stage_data_provider['available_states'] = [];
				if(!$this->inFullVersion()){
					$this->stage_data_provider['available_states']['i'] = [
						'state' => 'i',
						'link' => 'http://carvoy.com/find/i'
					];
				}
				$states = $this->getAvailableStates();
				$this->stage_data_provider['available_states'] = array_merge($this->stage_data_provider['available_states'], $states);
                
            }
            $config['can_be_built'] = (bool)$this->stage_data_provider['available_states'];
            $this->seodata = [
                'title' => "Carvoy - Choose state to find a car",
                'description' => "Carvoy - Choose state to find a car for lease."
            ];
            return $config;
        }
    }

    /**
     *
     */
    public function changeZipBasedOnRoute()
    {
        $zipdata_for_current_url = $this->getZipdataByStateAndCity($this->config['state'], $this->config['city']);
        $this->stage_data_provider['location_data']['zip'] = $zipdata_for_current_url['zip'];
        if ($this->inFullVersion()) {
            $newLocatorObject = $this->formLocatorObjectByZipdata($zipdata_for_current_url);
            $currentLocatorObject = $this->getLocatorObjectOverride($newLocatorObject);
            $this->assignLocationZipByLocatorObject($currentLocatorObject);
        }
    }

    /**
     * @param string $state
     * @param string $city
     * @return array|Zip|ActiveRecord|null
     */
    public function getZipdataByStateAndCity(string $state, string $city)
    {
        return Zip::find()->where(
            [
                'state_code' => $state,
                'city' => $city
            ]
        )->asArray()->one();
    }

    /**
     * @return bool
     */
    public function inFullVersion()
    {
        return $this->config['version'] === self::FULL_VERSION;
    }

    /**
     * @param $zipdata
     * @return bool
     */
    public function formLocatorObjectByZipdata($zipdata)
    {
		return F3deshaHelpers::formLocatorObjectByZipdata($zipdata);
    }
    
    public function findMakesByString(string $string){
		$brands = ChromedataVehicle::find()->select('division')->distinct()->where(['like', 'division', $string.'%', false])->asArray()->all();
		if(!empty($brands)){
			$brands = \common\components\ArrayHelper::index($brands, 'division');
			$brands = array_keys($brands);
			$allowedMakes = SelectedMake::getMakesList();
			$brands = array_intersect($allowedMakes, $brands);

			$makes_map = $this->getMakesMap();
			foreach ($brands as $i => $brand){
				if(array_key_exists($brand, $makes_map)){
					$brands[$brand]['division'] = $brand;
					$brands[$brand]['image'] = $makes_map[$brand]['image_url'];
					unset($brands[$i]);
				}
			}
			$brands = array_values($brands);
			return $brands;

		}
	}

	public function findAllTrimsOfModel($model, $make, $years){
		$trims = ChromedataVehicle::find()->select(['division','model','short_trim'])->distinct()
			->where(['like', 'model', $model.'%', false])->andWhere(['year'=>$years])->andWhere(['division' => $make])->all();
		$new_trims = [];
		if(!empty($trims)){
			$new_trim = [];
			foreach ($trims as $trim){
				$result = ChromedataVehicle::find()
					->where(['division'=>$trim->division, 'model'=>$trim->model, 'short_trim'=>$trim->short_trim])
					->andWhere(['year'=>$years])
					->orderBy('year DESC')
					->limit(1)->all();
				if(!empty($result)){
					$result = $result[0];
				}
				$exterior_img[0] = '';
				if(!empty($exterior_img = Yii::$app->carImages->getExterior($result->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
					$image_url = $exterior_img[0];
				} else {
					$image_url = '/statics/web/images/itemCarOptimized.jpg';
				}
				$new_trim['division'] = "{$result->division}";
				$new_trim['model_single'] = "{$trim->model}";
				$new_trim['year'] = "{$result->year}";
				$new_trim['trim_single'] = "{$trim->short_trim}";
				$new_trim['trim'] = "{$trim->division} {$trim->model} {$trim->short_trim}";
				$new_trim['image'] = $image_url;
				$new_trims[] = $new_trim;
			}
			return $new_trims;
		}
	}
	
	public function findAllModelsOfMake($string, $years){
		$models = ChromedataVehicle::find()->select(['division','model'])->distinct()->where(
			['like', 'division', $string.'%', false]
		)->andWhere(['year'=>$years])->all();
		$new_models = [];
		if(!empty($models)){
			$new_model = [];
			foreach ($models as $model){
				$result = ChromedataVehicle::find()
					->where(['division'=>$model->division, 'model'=>$model->model])
					->andWhere(['year'=>$years])
					->orderBy('year DESC')
					->limit(1)->all();
				if(!empty($result)){
					$result = $result[0];
				}
				$exterior_img[0] = '';
				if(!empty($exterior_img = Yii::$app->carImages->getExterior($result->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
					$image_url = $exterior_img[0];
				} else {
					$image_url = '/statics/web/images/itemCarOptimized.jpg';
				}
				$new_model['division'] = "{$model->division}";
				$new_model['model_single'] = "{$model->model}";
				$new_model['year'] = "{$result->year}";
				$new_model['model'] = "{$model->division} {$model->model}";
				$new_model['image'] = $image_url;
				$new_models[] = $new_model;
			}
			return $new_models;
		}
	}
	
	public function findModelsByString(string $string, $years){
		$models = ChromedataVehicle::find()->select(['division','model'])->distinct()->where(
			['like', 'model', $string.'%', false]
		)->andWhere(['year'=>$years])->all();
		$new_models = [];
		if(!empty($models)){
			$new_model = [];
			foreach ($models as $model){
				$result = ChromedataVehicle::find()
					->where(['division'=>$model->division, 'model'=>$model->model])
					->andWhere(['year'=>$years])
					->orderBy('year DESC')
					->limit(1)->all();
				if(!empty($result)){
					$result = $result[0];
				}
				$exterior_img[0] = '';
				if(!empty($exterior_img = Yii::$app->carImages->getExterior($result->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
					$image_url = $exterior_img[0];
				} else {
					$image_url = '/statics/web/images/itemCarOptimized.jpg';
				}
				$new_model['division'] = "{$model->division}";
				$new_model['model_single'] = "{$model->model}";
				$new_model['year'] = "{$result->year}";
				$new_model['model'] = "{$model->division} {$model->model}";
				$new_model['image'] = $image_url;
				$new_models[] = $new_model;
			}
			return $new_models;
		}
	}
	
	public function findTrimsByString(string $string, $years){
		$trims = ChromedataVehicle::find()->select(['division','model','short_trim'])->distinct()
			->where(['like', 'short_trim', $string.'%', false])->andWhere(['year'=>$years])->all();
		$new_trims = [];
		if(!empty($trims)){
			$new_trim = [];
			foreach ($trims as $trim){
				$result = ChromedataVehicle::find()
					->where(['division'=>$trim->division, 'model'=>$trim->model, 'short_trim'=>$trim->short_trim])
					->andWhere(['year'=>$years])
					->orderBy('year DESC')
					->limit(1)->all();
				if(!empty($result)){
					$result = $result[0];
				}
				$exterior_img[0] = '';
				if(!empty($exterior_img = Yii::$app->carImages->getExterior($result->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
					$image_url = $exterior_img[0];
				} else {
					$image_url = '/statics/web/images/itemCarOptimized.jpg';
				}
				$new_trim['division'] = "{$result->division}";
				$new_trim['model_single'] = "{$trim->model}";
				$new_trim['year'] = "{$result->year}";
				$new_trim['trim_single'] = "{$trim->short_trim}";
				$new_trim['trim'] = "{$trim->division} {$trim->model} {$trim->short_trim}";
				$new_trim['image'] = $image_url;
				$new_trims[] = $new_trim;
			}
			return $new_trims;
		}
	}

    /**
     * @param $locatorObject
     * @return bool|mixed
     */
    public function getLocatorObjectOverride($locatorObject)
    {
        $seocatalog_session_zip = Yii::$app->track->tracking_session->tracking_storage->getSessionProvider(
            self::SESSION_ZIP_LOCATOR_KEY
        );
        if (empty($seocatalog_session_zip)) {
            return $this->storeLocatorObject($locatorObject);
        }
        if ($locatorObject && $seocatalog_session_zip) {
            $zipdata = Zip::find()->select('zip')->where(
                [
                    'city' => $this->getCityFromLocatorObject($locatorObject),
                    'state_code' => $this->getStateFromLocatorObject($locatorObject)
                ]
            )->asArray()->all();
            $zips = ArrayHelper::getColumn($zipdata, 'zip');
            if (in_array($seocatalog_session_zip, $zips)) {
                $zipdata = $this->getZipdataByZip($seocatalog_session_zip);
                $sessionLocatorObject = $this->formLocatorObjectByZipdata($zipdata);
                return $this->storeLocatorObject($sessionLocatorObject);
            }
        }
        return $this->storeLocatorObject($locatorObject);
    }

    /**
     * @param $locatorObject
     * @return bool|mixed
     */
    public function storeLocatorObject($locatorObject)
    {
        if (!empty($locatorObject)) {
			F3deshaHelpers::storeLocatorObject($locatorObject);
            return $this->getLocatorObject();
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getLocatorObject()
    {
		return F3deshaHelpers::getLocatorObject();
    }

    /**
     * @param $locatorObject
     * @return bool
     */
    public function getCityFromLocatorObject($locatorObject)
    {
        if (!empty($locatorObject['processed']['location_details']['city'])) {
            return $locatorObject['processed']['location_details']['city'];
        }
        return false;
    }

    /**
     * @param $locatorObject
     * @return bool
     */
    public function getStateFromLocatorObject($locatorObject)
    {
        if (!empty($locatorObject['processed']['location_details']['state'])) {
            return $locatorObject['processed']['location_details']['state'];
        }
        return false;
    }

    /**
     * @param string $zip
     * @return array|Zip|ActiveRecord|null
     */
    public function getZipdataByZip(string $zip)
    {
        return Zip::find()->where(
            [
                'zip' => $zip
            ]
        )->asArray()->one();
    }

    /**
     * @param $locator_object
     * @return bool
     */
    public function assignLocationZipByLocatorObject($locator_object)
    {
        if (!empty($locator_object)) {
            if (!empty($locator_object['processed']['location_details']['zip'])) {
                $this->stage_data_provider['location_data']['zip'] = $locator_object['processed']['location_details']['zip'];
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|bool
     */
    public function buildManagersMaskByLocation()
    {
        if ($this->zipExistsForLocationRoute()) {
        	//For regular seocatalog we take zip by decoding state and city from seocatalogs url
            $zip = $this->stage_data_provider['location_data']['zip'];
            $coverageDealersSet = Dealerinventory::getCoverageDealerSet((int)$zip);
            $managersMask = Dealerinventory::extractManagersMask($coverageDealersSet);
            return $managersMask;
        } elseif($this->inFullVersion()){
        	if($session_locator = $this->getLocatorObject()){
				//But if we in info mode seocatalog, we just take the zip from geolocator widget and use it
				$zip = $session_locator['processed']['location_details']['zip'];
				$coverageDealersSet = Dealerinventory::getCoverageDealerSet((int)$zip);
				$managersMask = Dealerinventory::extractManagersMask($coverageDealersSet);
				return $managersMask;
			}
		}
		
        return false;
    }

    /**
     * @return array|Dealerinventory[]|DealerinventoryHasGenericEquipment[]|Dealership[]|Zip[]|ActiveRecord[]
     */
    public function zipExistsForLocationRoute()
    {
        return Zip::find()->where(
            [
                'state_code' => $this->stage_data_provider['location_data']['state'],
                'city' => $this->stage_data_provider['location_data']['city']
            ]
        )->limit(1)->all();
    }

    /**
     * @return array
     */
    public function getAvailableYears()
    {
        $available_years = [];
		$years_range = $this->getWorkingYearRange();

		if($this->isInfoMode){
			$filters_secondary = [
				'division' => $this->config['make'],
				'model' => $this->config['model']
			];
			if (!empty($this->config['trim'])) {
				$filters_secondary['short_trim'] = $this->config['trim'];
			}
			$items = ChromedataVehicle::find()->select(['year'])->where($filters_secondary)->distinct(
			)->asArray()->all();
			$years = array_column($items, 'year');
			rsort($years);
			$years = array_intersect($years, $years_range);
			if ($this->inFullVersion()) {
				$current_url = $_SERVER['REQUEST_URI'];
				$parsed_url = explode('/', $current_url);
				unset($parsed_url[0]); // remove item at index 0
				$parsed_url = array_values($parsed_url); // 'reindex' array

				$years_available_count = count($years);
				$fake_year_collection = [];

				if (!empty($years) && $years_available_count < 3) {
					$current_year = $years_range[1];
					$next_year = $current_year + 1;
					$fake_years_to_feel = 3 - $years_available_count;
					while ($fake_years_to_feel > 0) {
						$year = false;
						if (!in_array($next_year, $years)) {
							$year = $next_year;
						}
						if (!in_array($current_year, $years)) {
							$year = $current_year;
						}
						if (!$year) {
							$year = $current_year - 1;
						}
						if ($year) {
							$fake_year_collection[] = $year;
							$years[] = $year;
						}
						$fake_years_to_feel--;
					}
				}
				rsort($years);

				foreach ($years as $year) {
					$parsed_url[5] = $year;
					$new_url = implode('/', $parsed_url);
					if (!in_array($year, $fake_year_collection)) {
						$url = F3deshaHelpers::prepareForUrl('/' . Url::base() . $new_url);
						$isFake = false;
					} else {
						$url = '';
						$isFake = true;
					}
					$available_years[] = ['year' => $year, 'link' => $url, 'fake' => $isFake];
				}
			} elseif ($this->inQuickVersion()){
				$available_years = $years;
			}
			
		} elseif ($this->zipExistsForLocationRoute()) {
				$managersMask = $this->stage_data_provider['managers_mask'];
				$filters = Dealerinventory::activeItemsCommonConditions($managersMask);
				$filters_secondary = [
					'make' => $this->config['make'],
					'model' => $this->config['model']
				];
				if (!empty($this->config['trim'])) {
					$filters_secondary['short_trim'] = $this->config['trim'];
				}
				$items = Dealerinventory::find()->select(['year'])->where($filters)->andWhere($filters_secondary)->distinct(
				)->asArray()->all();
				$years = array_column($items, 'year');
				rsort($years);
				$years = array_intersect($years, $years_range);

				if ($this->inFullVersion()) {
					$current_url = $_SERVER['REQUEST_URI'];
					$parsed_url = explode('/', $current_url);
					unset($parsed_url[0]); // remove item at index 0
					$parsed_url = array_values($parsed_url); // 'reindex' array

					$years_available_count = count($years);
					$fake_year_collection = [];

					if (!empty($years) && $years_available_count < 3) {
						$current_year = $years_range[1];
						$next_year = $current_year + 1;
						$fake_years_to_feel = 3 - $years_available_count;
						while ($fake_years_to_feel > 0) {
							$year = false;
							if (!in_array($next_year, $years)) {
								$year = $next_year;
							}
							if (!in_array($current_year, $years)) {
								$year = $current_year;
							}
							if (!$year) {
								$year = $current_year - 1;
							}
							if ($year) {
								$fake_year_collection[] = $year;
								$years[] = $year;
							}
							$fake_years_to_feel--;
						}
					}
					rsort($years);

					foreach ($years as $year) {
						$parsed_url[5] = $year;
						$new_url = implode('/', $parsed_url);
						if (!in_array($year, $fake_year_collection)) {
							$url = F3deshaHelpers::prepareForUrl('/' . Url::base() . $new_url);
							$isFake = false;
						} else {
							$url = '';
							$isFake = true;
						}
						$available_years[] = ['year' => $year, 'link' => $url, 'fake' => $isFake];
					}
				} elseif ($this->inQuickVersion()) {
					$available_years = $years;
				}
			}


        return $available_years;
    }

    /**
     * @return bool
     */
    public function inQuickVersion()
    {
        return $this->config['version'] === self::QUICK_VERSION;
    }

    /**
     * @return array
     */
    public function getAvailableModels()
    {
        $available_models = [];
		if($this->isInfoMode){
			$models = ChromedataVehicle::find()->select('model')->where([
				'year'=>$this->getWorkingYearRange(),
				'division' => $this->config['make']
			])->distinct()->asArray()->all();

			foreach ($models as $item) {
				$item_with_images = ChromedataVehicle::find()->where([
					'year'=>$this->getWorkingYearRange(),
					'division' => $this->config['make'],
				])->andWhere(
					['model' => $item['model']]
				)->limit(1)->all();
				$item_with_images = $item_with_images[0];

				$exterior_img[0] = '';
				if(!empty($exterior_img = Yii::$app->carImages->getExterior($item_with_images->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
					$image_url = $exterior_img[0];
				} else {
					$image_url = '/statics/web/images/itemCarOptimized.jpg';
				}

				$model_link = self::routeToCatalog(
					$this->stage_data_provider['location_data']['state'],
					$this->stage_data_provider['location_data']['city'],
					$this->config['make'],
					$item['model']
				);

				$available_models[]  = [
						'make' => $this->config['make'],
						'model' => $item['model'],
						'link' => $model_link,
						'image' => $image_url
					];
			}


		} else if ($this->zipExistsForLocationRoute()) {
				$managersMask = $this->stage_data_provider['managers_mask'];
				$filters = Dealerinventory::activeItemsCommonConditions($managersMask);
				$items = Dealerinventory::find()->select(['model'])->where($filters)->andWhere(
					[
						'make' => $this->config['make']
					]
				)->distinct()->all();
				foreach ($items as $item) {
					$model = [];
					$item_with_images = Dealerinventory::find()->where($filters)->andWhere(
						['model' => $item->model, 'make' => $this->config['make']]
					)->with(['photos', 'chromedataVehicle.engines'])->limit(1)->all();
					$item_with_images = $item_with_images[0];
					$model['make'] = $item_with_images->make;
					$model['state'] = $this->stage_data_provider['location_data']['state'];
					$model['city'] = $this->stage_data_provider['location_data']['city'];
					$model['model'] = $item_with_images->model;
					$prelink = Url::base(
						) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
							$this->stage_data_provider['location_data']['city']
						) . '/' . $this->config['make'] . '/';
					$model['link'] = F3deshaHelpers::prepareForUrl(
						$prelink . F3deshaHelpers::encodeForUrl($model['model'])
					);

					if (!empty($item_with_images->photos[0])) {
						$image = $item_with_images->photos[0]->url;
					} else {
						$image = 'statics/web/images/itemCarOptimized.jpg';
					}
					$model['image'] = $image;

					$available_models[] = $model;
				}

			}


		sort($available_models);
		$this->stage_data_provider['available_models'] = $available_models;

        return $available_models;
    }

    /**
     * @return array
     */
    public function getAvailableTrims()
    {
        $available_trims = [];
        $grouped_photos = [];

		if($this->isInfoMode){
		$managersMask = $this->stage_data_provider['managers_mask'];
		$filters = Dealerinventory::activeItemsCommonConditions($managersMask);
			
			$ymt = [
				'division' => $this->config['make'],
				'model' => $this->config['model']
			];
			if (!empty($this->config['year'])) {
				$ymt['year'] = $this->config['year'];
			}
			if (!empty($this->config['trim'])) {
				$ymt['short_trim'] = $this->config['trim'];
			}
			$items = ChromedataVehicle::find()->select(['short_trim'])->where($ymt)->distinct()->all(
			);
			foreach ($items as $item) {
				$trim = [];
				$ymt = [
					'division' => $this->config['make'],
					'model' => $this->config['model'],
					'short_trim' => $item->short_trim
				];
				if (!empty($this->config['year'])) {
					$ymt['year'] = $this->config['year'];
				}

				$subquery = ChromedataVehicle::find()->where($ymt)->min('base_msrp');
				$item_with_images = ChromedataVehicle::find()->where($ymt)->andWhere(
					[
						'base_msrp' => $subquery
					]
				)->one();
				$trim['trim'] = $item_with_images->short_trim;
				$trim['model'] = $item_with_images->model;
				$trim['year'] = $item_with_images->year;
				$trim['make'] = $item_with_images->division;
				$trim['link'] = self::routeToCatalog(
					$this->stage_data_provider['location_data']['state'],
					$this->stage_data_provider['location_data']['city'],
					$this->config['make'],
					$trim['model'],
					$trim['year'],
					$trim['trim']
				);
				
				if($this->inFullVersion()){
					$trim['id'] = $item_with_images->id;
					$trim['fuel_id'] = $item_with_images->img_id;
					$trim['state'] = $this->stage_data_provider['location_data']['state'];
					$trim['city'] = $this->stage_data_provider['location_data']['city'];
					$trim['ignite_link'] = F3deshaHelpers::prepareForUrl(
						Url::base(true) . '/' . Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::encodeForUrl(
							$this->config['make']
						) . '&model=' . F3deshaHelpers::encodeForUrl(
							$this->config['model']
						) . '&year=' . F3deshaHelpers::encodeForUrl(
							$this->config['year']
						) . '&trim=' . F3deshaHelpers::encodeForUrlIgnite($trim['trim'])
					);
					$dymt = $ymt;
					$dymt['make'] = $dymt['division'];
					unset($dymt['division']);
					$trim['available_cars'] = Dealerinventory::find()->where($dymt)->andWhere($filters)->count();
					$vehicle = $item_with_images;
					if (!empty($vehicle->engines[0])) {
						if (!empty($vehicle->engines[0]->horsepower_value)) {
							$horsepower = $vehicle->engines[0]->horsepower_value;
						} else {
							$horsepower = 'N/A';
						}
					} else {
						$horsepower = 'N/A';
					}
					$trim['horsepower'] = $horsepower;
					$trim['doors'] = $vehicle->pass_doors;
					$trim['drive_type'] = $vehicle->body_type;

					$exterior_img[0] = '';
					if(!empty($exterior_img = Yii::$app->carImages->getExterior($item_with_images->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
						$image_url = $exterior_img[0];
					} else {
						$image_url = '/statics/web/images/itemCarOptimized.jpg';
					}
					$trim['image'] = $image_url;

					$features_pack = [];
					//Get the name of features pack
					$features_pack['Trim Name'] = [
						'type' => 'value',
						'value' => $trim['trim']
					];

					//Get the First part of feature collection
					$features_pack['Starting MSRP'] = [
						'type' => 'value',
						'value' => '$' . number_format($item_with_images->base_msrp) . '*'
					];
					$trim['msrp'] = $item_with_images->base_msrp;

					if (!empty($vehicle->engines[0])) {
						$features_pack['MPG'] = [
							'type' => 'value',
							'value' => $vehicle->engines[0]->fuel_economy_city_high . ' / ' . $vehicle->engines[0]->fuel_economy_hwy_high . ' MPG'
						];
					}

					if (!empty($vehicle->engines[0])) {
						$engine = $vehicle->engines[0];
						$features_pack['Engine'] = [
							'type' => 'value',
							'value' => $engine->displacement_value . ' ' . $engine->displacement_unit . ' ' . $engine->engine_type
						];
					} else {
						$features_pack['Engine'] = [
							'type' => 'value',
							'value' => 'N/A'
						];
					}


					//Get the Second part of feature collection
					$features_pack['Drivetrain'] = [
						'type' => 'value',
						'value' => $vehicle->drivetrain
					];

					if (!empty($vehicle->engines[0])) {
						$engine = $vehicle->engines[0];
						$features_pack['HP'] = [
							'type' => 'value',
							'value' => $engine->horsepower_value . ' @ ' . $engine->horsepower_rpm
						];
					} else {
						$features_pack['HP'] = [
							'type' => 'value',
							'value' => 'N/A'
						];
					}

					//Transmission
					//$transmission_type = $vehicle->getCategories()->where(['type_id' => 16])->limit(1)->one();
					//$dealerinventory->transmissionType = $transmission_type->category_id;

					$transmission = $vehicle->getCategories()->where(['type_id' => 15])->limit(1)->one();
					if(!empty($transmission)){
						$transmissionGears = $transmission->category_id;

						$transmission = CategorySpecification::find()->where(
							['category_id' => $transmissionGears]
						)->limit(1)->one()->category_name;
						if (!empty($transmission)) {
							$features_pack['Transmission'] = [
								'type' => 'value',
								'value' => $transmission
							];
						} else {
							$features_pack['Transmission'] = [
								'type' => 'value',
								'value' => 'N/A'
							];
						}
					}


					if (!empty($vehicle->engines[0])) {
						$engine = $vehicle->engines[0];
						$features_pack['Fuel Type'] = [
							'type' => 'value',
							'value' => $engine->fuel_type
						];
					} else {
						$features_pack['HP'] = [
							'type' => 'value',
							'value' => 'N/A'
						];
					}

					$features_pack['Doors'] = [
						'type' => 'value',
						'value' => $vehicle->pass_doors
					];

					$fakeDi = new Dealerinventory();
					$mapping_set = $fakeDi->receiveHighlightsMappingSet();
					if (!empty($genEq = $vehicle->genericEquipment)) {
						$active_equipments = [];
						foreach ($genEq as $eqItem){
							if($eqItem->cause !== null){
								$active_equipments[] = $eqItem->category_id;
							}
						}
						if(!empty($active_equipments)){
							foreach ($mapping_set as $highlight) {
								$features_pack[$highlight['carvoy_name']] = [
									'type' => 'checkbox',
									'value' => in_array($highlight['category_id'], $active_equipments) ? 1 : 2
								];
							}
						}

					}


					$trim['popular_features'] = $features_pack;

					$photos['medium'] = [];
					$interior = Yii::$app->carImages->getImage(
						$trim['fuel_id'],
						'interior',
						ImportImages::$INTERIOR_THUMB_PARAMS);

					$photos['medium'] = $interior;
					$photosMedium = is_array($photos['medium']) ? $photos['medium'] : [];
					$grouped_photos = ArrayHelper::merge($grouped_photos, $photosMedium);
				}
				
				$available_trims[] = $trim;
			}


		} elseif ($this->zipExistsForLocationRoute()) {
				$managersMask = $this->stage_data_provider['managers_mask'];
				$filters = Dealerinventory::activeItemsCommonConditions($managersMask);
				$ymt = [
					'make' => $this->config['make'],
					'model' => $this->config['model']
				];
				if (!empty($this->config['year'])) {
					$ymt['year'] = $this->config['year'];
				}
				if (!empty($this->config['trim'])) {
					$ymt['short_trim'] = $this->config['trim'];
				}
				$items = Dealerinventory::find()->select(['short_trim'])->where($filters)->andWhere($ymt)->distinct()->all(
				);
				foreach ($items as $item) {
					$trim = [];

					$ymt = [
						'make' => $this->config['make'],
						'model' => $this->config['model'],
						'short_trim' => $item->short_trim
					];
					if (!empty($this->config['year'])) {
						$ymt['year'] = $this->config['year'];
					}

					$subquery = Dealerinventory::find()->where($filters)->andWhere($ymt)->min('msrp');
					$item_with_images = Dealerinventory::find()->where($filters)->andWhere($ymt)->andWhere(
						[
							'msrp' => $subquery
						]
					)->with(['photos', 'chromedataVehicle.engines'])->one();
					$trim['trim'] = $item_with_images->short_trim;
					$trim['model'] = $item_with_images->model;
					$trim['year'] = $item_with_images->year;
					$trim['make'] = $item_with_images->make;
					$prelink = Url::base(
						) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
							$this->stage_data_provider['location_data']['city']
						) . '/' . $this->config['make'] . '/';
					$trim['link'] = F3deshaHelpers::prepareForUrl(
						$prelink . F3deshaHelpers::encodeForUrl($trim['model']) . '/' . F3deshaHelpers::encodeForUrl(
							$trim['year']
						) . '/' . F3deshaHelpers::encodeForUrl($trim['trim'])
					);

					if ($this->inFullVersion()) {
						$trim['id'] = $item_with_images->id;
						$trim['fuel_id'] = $item_with_images->fuel_id;
						$trim['state'] = $this->stage_data_provider['location_data']['state'];
						$trim['city'] = $this->stage_data_provider['location_data']['city'];
						$trim['ignite_link'] = F3deshaHelpers::prepareForUrl(
							Url::base(true) . '/' . Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::encodeForUrl(
								$this->config['make']
							) . '&model=' . F3deshaHelpers::encodeForUrl(
								$this->config['model']
							) . '&year=' . F3deshaHelpers::encodeForUrl(
								$this->config['year']
							) . '&trim=' . F3deshaHelpers::encodeForUrlIgnite($trim['trim'])
						);
						$trim['available_cars'] = Dealerinventory::find()->where($filters)->andWhere($ymt)->count();
						$vehicle = $item_with_images->chromedataVehicle;
						if (!empty($vehicle->engines[0])) {
							if (!empty($vehicle->engines[0]->horsepower_value)) {
								$horsepower = $vehicle->engines[0]->horsepower_value;
							} else {
								$horsepower = 'N/A';
							}
						} else {
							$horsepower = 'N/A';
						}
						$trim['horsepower'] = $horsepower;
						$trim['doors'] = $vehicle->pass_doors;
						$trim['drive_type'] = $vehicle->body_type;
						if (!empty($item_with_images->photos[0])) {
							$image = $item_with_images->photos[0]->url;
						} else {
							$image = 'statics/web/images/itemCarOptimized.jpg';
						}
						$trim['image'] = $image;

						$features_pack = [];
						//Get the name of features pack
						$features_pack['Trim Name'] = [
							'type' => 'value',
							'value' => $trim['trim']
						];

						//Get the First part of feature collection
						$features_pack['Starting MSRP'] = [
							'type' => 'value',
							'value' => '$' . number_format($item_with_images->msrp) . '*'
						];
						$trim['msrp'] = $item_with_images->msrp;

						$features_pack['MPG'] = [
							'type' => 'value',
							'value' => $item_with_images->mpgCity . ' / ' . $item_with_images->mpgHighway . ' MPG'
						];

						if (!empty($vehicle->engines[0])) {
							$engine = $vehicle->engines[0];
							$features_pack['Engine'] = [
								'type' => 'value',
								'value' => $engine->displacement_value . ' ' . $engine->displacement_unit . ' ' . $engine->engine_type
							];
						} else {
							$features_pack['Engine'] = [
								'type' => 'value',
								'value' => 'N/A'
							];
						}


						//Get the Second part of feature collection
						$features_pack['Drivetrain'] = [
							'type' => 'value',
							'value' => $vehicle->drivetrain
						];

						if (!empty($vehicle->engines[0])) {
							$engine = $vehicle->engines[0];
							$features_pack['HP'] = [
								'type' => 'value',
								'value' => $engine->horsepower_value . ' @ ' . $engine->horsepower_rpm
							];
						} else {
							$features_pack['HP'] = [
								'type' => 'value',
								'value' => 'N/A'
							];
						}

						$transmission = CategorySpecification::find()->where(
							['category_id' => $item_with_images->transmissionGears]
						)->limit(1)->one()->category_name;
						if (!empty($transmission)) {
							$features_pack['Transmission'] = [
								'type' => 'value',
								'value' => $transmission
							];
						} else {
							$features_pack['Transmission'] = [
								'type' => 'value',
								'value' => 'N/A'
							];
						}


						if (!empty($vehicle->engines[0])) {
							$engine = $vehicle->engines[0];
							$features_pack['Fuel Type'] = [
								'type' => 'value',
								'value' => $engine->fuel_type
							];
						} else {
							$features_pack['HP'] = [
								'type' => 'value',
								'value' => 'N/A'
							];
						}

						$features_pack['Doors'] = [
							'type' => 'value',
							'value' => $item_with_images->numberOfDoors
						];

						if (!empty($item_with_images->equipmentList)) {
							$active_equipments = explode(',', $item_with_images->equipmentList->category_id);
						}
						$mapping_set = $item_with_images->receiveHighlightsMappingSet();
						foreach ($mapping_set as $highlight) {
							$features_pack[$highlight['carvoy_name']] = [
								'type' => 'checkbox',
								'value' => in_array($highlight['category_id'], $active_equipments) ? 1 : 2
							];
						}

						$trim['popular_features'] = $features_pack;

						$photos['medium'] = [];
						$interior = Yii::$app->carImages->getImage(
							$trim['fuel_id'],
							'interior',
							ImportImages::$INTERIOR_THUMB_PARAMS
						);

						$photos['medium'] = $interior;
						$photosMedium = is_array($photos['medium']) ? $photos['medium'] : [];
						$grouped_photos = ArrayHelper::merge($grouped_photos, $photosMedium);
					}

					$available_trims[] = $trim;
				}
			}

		$this->stage_data_provider['grouped_photos'] = $grouped_photos;
		ArrayHelper::multisort($available_trims, 'msrp');
		$this->stage_data_provider['available_trims'] = $available_trims;
        return $available_trims;
    }

	/**
	 * @param string $state
	 * @param string $city
	 * @param string $make
	 * @param string|null $model
	 * @param string|null $year
	 * @param string|null $trim
	 * @return mixed|string
	 */
    public static function routeToCatalog(string $state = SeoCatalog::INFO_MODE_STATE_PLACEHOLDER, 
										  string $city = SeoCatalog::INFO_MODE_CITY_PLACEHOLDER, 
										  string $make = null, 
										  string $model = null,
										  string $year = null,
										  string $trim = null){
		$prelink = Url::base() . '/find/' . $state . '/' . F3deshaHelpers::encodeForUrl($city);
		if(!empty($make)){
			$prelink .= F3deshaHelpers::prepareForUrl(
				'/' .F3deshaHelpers::encodeForUrl($make)
			);
		}
		if(!empty($model)){
			$prelink .= F3deshaHelpers::prepareForUrl(
				'/' .F3deshaHelpers::encodeForUrl($model)
			);
		}
		if(!empty($year)){
			$prelink .= F3deshaHelpers::prepareForUrl(
				'/'. F3deshaHelpers::encodeForUrl($year)
			);
		}
		if(!empty($trim)){
			$prelink .= F3deshaHelpers::prepareForUrl(
				'/'. F3deshaHelpers::encodeForUrl($trim)
			);
		}
		return $prelink;
	}

    /**
     *
     */
    public function getTrimHighlightsDetails()
    {
        $optional_equipment = [];
        if (!empty($this->stage_data_provider['available_trims'])) {
            if (count($this->stage_data_provider['available_trims']) === 1) {
				if($this->isInfoMode){
					$ymt = [
						'year' => $this->stage_data_provider['available_trims'][0]['year'],
						'division' => $this->stage_data_provider['available_trims'][0]['make'],
						'model' => $this->stage_data_provider['available_trims'][0]['model'],
						'short_trim' => $this->stage_data_provider['available_trims'][0]['trim']
					];
					$chrome_vehicle = ChromedataVehicle::find()->where($ymt)->limit(1)->all();
					$chrome_vehicle = $chrome_vehicle[0];
				} else {
					$ignite_item = Dealerinventory::find()->where(
						['id' => $this->stage_data_provider['available_trims'][0]['id']]
					)->with(['chromedataVehicle.genericEquipment.categories'])->limit(1)->all();
					$ignite_item = $ignite_item[0];
					$chrome_vehicle = $ignite_item->chromedataVehicle;
				}

				$all_options = $chrome_vehicle->getFactoryOptions()->asArray()->all();
				if (!empty($all_options)) {
					foreach ($all_options as $option) {
						$optional_equipment[$option['type']][] = $option;
					}
				}
				unset($optional_equipment['PAINT SCHEME']);
				unset($optional_equipment['PRIMARY PAINT']);

				//Full car specs section
				if($this->isInfoMode){
					$chrome_vehicle_categories = $chrome_vehicle->genericEquipment;
					$equipment['equipment'] = [];
					foreach ($chrome_vehicle_categories as $category) {
						$equipment['equipment'][$category['categories']->group_name][$category['categories']->header_name][]['name'] = $category['categories']->category_name;
					}
					$vehicle_fuel_id = $this->stage_data_provider['available_trims'][0]['fuel_id'];
				} else {
					$styleId = $ignite_item->style_id;
					$dealerinventory_id = $ignite_item->id;
					$chrome_vehicle_categories = $ignite_item->getChromedataVehicle()->with('genericEquipment')->all();
					$active_equipment = DealerinventoryHasGenericEquipment::find()->where(
						['dealerinventory_id' => $dealerinventory_id]
					)->limit(1)->all();
					if (!empty($active_equipment[0])) {
						$active_equipment = explode(',', $active_equipment[0]->category_id);
					}

					$equipment['equipment'] = [];
					foreach ($chrome_vehicle_categories[0]->genericEquipment as $category) {
						in_array($category->category_id, $active_equipment) ?
							$equipment['equipment'][$category['categories']->group_name][$category['categories']->header_name][]['name'] = $category['categories']->category_name
							: null;
					}
					$vehicle_fuel_id = $ignite_item->fuel_id;
				}
				
				$this->stage_data_provider['available_trims'][0]['full_car_specs'] = $equipment;
				////END full car specs section


				//IMAGES gallery
				if (!empty($vehicle_fuel_id)) {
					$img_params_medium = ImportImages::$EXTERIOR_THUMB_PARAMS;

					$photos['medium'] = Yii::$app->carImages
						->getImage($vehicle_fuel_id, 'exterior', $img_params_medium, 3);
					$interior = Yii::$app->carImages->getImage(
						$vehicle_fuel_id,
						'interior',
						ImportImages::$INTERIOR_THUMB_PARAMS
					);
					if (!empty($interior)) {
						$this->stage_data_provider['available_trims'][0]['main_photo'] = $interior[0];
					}
					$photos['medium'] = ArrayHelper::merge($photos['medium'], $interior);
					$image_main = (array)Yii::$app->carImages->getImage($vehicle_fuel_id, 'splash', $img_params_medium);
					if ($image_main) {
						$photos['medium'] = ArrayHelper::merge($photos['medium'], $image_main);
					}
					if (!empty($photos['medium'])) {
						$this->stage_data_provider['available_trims'][0]['gallery_images'] = $photos['medium'];
					}
				}
				//IMAGES gallery	
            }
        }
        $this->stage_data_provider['available_trims'][0]['optional_equipment'] = $optional_equipment;
    }

    /**
     * @return array|Dealerinventory[]|DealerinventoryHasGenericEquipment[]|Dealership[]|Zip[]|ActiveRecord[]
     */
    public function getRandomIgniteItemsInCityByMake()
    {
        $ignite_items = [];

        if ($this->zipExistsForLocationRoute()) {
            $managersMask = $this->stage_data_provider['managers_mask'];
            $filters = Dealerinventory::activeItemsCommonConditions($managersMask);
            $ignite_items = Dealerinventory::find()->where($filters)->andWhere(
                [
                    'make' => $this->config['make']
                ]
            )->limit(24)->orderBy(new Expression('rand()'))->with('photos')->all();
        }
        return $ignite_items;
    }

    /**
     * @return int|string
     */
    public function getModelsCountByZip()
    {
        if ($this->zipExistsForLocationRoute()) {
            $managersMask = $this->stage_data_provider['managers_mask'];
            $filters = Dealerinventory::activeItemsCommonConditions($managersMask);
            $secondary_filters = [
                'year' => $this->config['year'],
                'make' => $this->config['make'],
                'model' => $this->config['model'],
            ];
            if (!empty($this->config['trim'])) {
                $secondary_filters['short_trim'] = $this->config['trim'];
            }
            $count = Dealerinventory::find()->where($filters)->andWhere($secondary_filters)->count();
            return $count;
        }
    }

    /**
     * @return bool
     */
    public function redirectToAvailableYear()
    {
        if (empty($this->config['year']) && !empty($this->stage_data_provider['available_years'])) {
            asort($this->stage_data_provider['available_years']);
            $max_year = 0;
            foreach ($this->stage_data_provider['available_years'] as $y) {
                if (!$y['fake']) {
                    if ($y['year'] > $max_year) {
                        $year = $y['year'];
                        $max_year = $year;
                    }
                }
            }
            $this->redirect = Yii::$app->request->url . '/' . $year;
            return true;
        }
        return false;
    }

	/**
	 * @return array
	 */
    public function getWorkingYearRange(){
    	return self::getWorkingYearRangeStatic();
	}

	/**
	 * @return array
	 */
	public static function getWorkingYearRangeStatic(){
		//By default there is no offset. We just use current year, year before current year, year after current year
		//But on January we user offset minus 1 year. That means that on January of 2020 year we still show
		//2018, 2019, 2020, but no February we show 2019, 2020, 2021
		$offset = 0;
		$current_year = date('Y');
		//$offset = 1;
		$years = [$current_year - 1 - $offset, $current_year - $offset, $current_year + 1 - $offset];
		return  $years;
	}

    /**
     * @return array
     */
    public function getAvailableMakes()
    {
        $available_makes = [];
        if($this->isInfoMode){
			$makes = ChromedataVehicle::find()->select('division')->where(['year'=>$this->getWorkingYearRange()])->distinct()->asArray()->all();
			$makes = $this->crhomedataToMakesMap($makes);

		} else {
        	$makes = [];
			if ($this->zipExistsForLocationRoute()) {
				$managersMask = $this->stage_data_provider['managers_mask'];
				$filters = Dealerinventory::activeItemsCommonConditions($managersMask);
				$makes = Dealerinventory::find()->select('make')->where($filters)->distinct()->asArray()->all();
			}
		}

		$prelink = Url::base(
			) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
				$this->stage_data_provider['location_data']['city']
			) . '/';
		$makes_map = $this->getMakesMap();

		foreach ($makes as $make) {
			if (array_key_exists($make['make'], $makes_map)) {
				$available_makes[$make['make']] = $makes_map[$make['make']];
				$available_makes[$make['make']]['link'] = self::routeToCatalog(
					$this->stage_data_provider['location_data']['state'],
					$this->stage_data_provider['location_data']['city'],
					$make['make']
				);
				 
				$available_makes[$make['make']]['city'] = $this->stage_data_provider['location_data']['city'];
				$available_makes[$make['make']]['state'] = $this->stage_data_provider['location_data']['state'];
			} else {
				$available_makes[$make['make']] = [
					'image_url' => '/statics/web/images/itemCarOptimized.jpg',
					'title' => $make['make'],
					'car_image_url' => '/statics/web/images/itemCarOptimized.jpg'
				];
				$available_makes[$make['make']]['link'] = F3deshaHelpers::prepareForUrl(
					$prelink . F3deshaHelpers::encodeForUrl($make['make'])
				);
				$available_makes[$make['make']]['city'] = $this->stage_data_provider['location_data']['city'];
				$available_makes[$make['make']]['state'] = $this->stage_data_provider['location_data']['state'];
			}
		}
		ksort($available_makes);
        $allowedMakes = SelectedMake::getMakesList();
        $allowedMakes = array_flip($allowedMakes);
        $available_makes = array_intersect_key($available_makes, $allowedMakes);
		$this->stage_data_provider['available_makes'] = $available_makes;

		$this->stage_data_provider['makes_for_UALV_make_city'] = $available_makes;

        return $available_makes;
    }

	/**
	 * @param array
	 * @return array
	 */
	public function crhomedataToMakesMap($chromedataMakes){
		$makes = [];

		$map = [];

		foreach ($chromedataMakes as $division){
			$makes[]['make'] = $division['division'];
		}

		return  $makes;
	}
    /**
     * @return array
     */
    public function getMakesMap()
    {
        return [
            'Acura' =>
                [
                    'image_url' => '/statics/images/img/models/1.png',
                    'title' => 'Acura',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Acura.png'
                ],
            'Alfa Romeo' =>
                [
                    'image_url' => '/statics/images/img/models/2.png',
                    'title' => 'Alfa Romeo',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Alfa Romeo.png'
                ],
            'Audi' =>
                [
                    'image_url' => '/statics/images/img/models/3.png',
                    'title' => 'Audi',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Audi.png'
                ],
            'BMW' =>
                [
                    'image_url' => '/statics/images/img/models/4.png',
                    'title' => 'BMW',
                    'car_image_url' => '/statics/images/img/makes_img_seo/BMW.png'
                ],
            'Buick' =>
                [
                    'image_url' => '/statics/images/img/models/5.png',
                    'title' => 'Buick',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Buick.png'
                ],
            'Cadillac' =>
                [
                    'image_url' => '/statics/images/img/models/6.png',
                    'title' => 'Cadillac',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Cadillac.png'
                ],
            'Chevrolet' =>
                [
                    'image_url' => '/statics/images/img/models/7.png',
                    'title' => 'Chevrolet',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Chevrolet.png'
                ],
            'Chrysler' =>
                [
                    'image_url' => '/statics/images/img/models/8.png',
                    'title' => 'Chrysler',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Chrysler.png'
                ],
            'Dodge' =>
                [
                    'image_url' => '/statics/images/img/models/9.png',
                    'title' => 'Dodge',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Dodge.png'
                ],
            'FIAT' =>
                [
                    'image_url' => '/statics/images/img/models/10.png',
                    'title' => 'Fiat',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Fiat.png'
                ],
            'Ford' =>
                [
                    'image_url' => '/statics/images/img/models/11.png',
                    'title' => 'Ford',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Ford.png'
                ],
            'Genesis' =>
                [
                    'image_url' => '/statics/images/img/models/12.png',
                    'title' => 'Genesis',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Genesis.png'
                ],
            'GMC' =>
                [
                    'image_url' => '/statics/images/img/models/13.png',
                    'title' => 'GMC',
                    'car_image_url' => '/statics/images/img/makes_img_seo/GMC.png'
                ],
            'Honda' =>
                [
                    'image_url' => '/statics/images/img/models/14.png',
                    'title' => 'Honda',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Honda.png'
                ],
            'Hyundai' =>
                [
                    'image_url' => '/statics/images/img/models/15.png',
                    'title' => 'Hyundai',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Hyundai.png'
                ],
            'INFINITI' =>
                [
                    'image_url' => '/statics/images/img/models/16.png',
                    'title' => 'Infiniti',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Infiniti.png'
                ],
            'Jaguar' =>
                [
                    'image_url' => '/statics/images/img/models/17.png',
                    'title' => 'Jaguar',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Jaguar.png'
                ],
            'Jeep' =>
                [
                    'image_url' => '/statics/images/img/models/18.png',
                    'title' => 'Jeep',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Jeep.png'
                ],
            'Kia' =>
                [
                    'image_url' => '/statics/images/img/models/19.png',
                    'title' => 'Kia',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Kia.png'
                ],
            'Land Rover' =>
                [
                    'image_url' => '/statics/images/img/models/20.png',
                    'title' => 'Land Rover',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Land Rover.png'
                ],
            'Lexus' =>
                [
                    'image_url' => '/statics/images/img/models/21.png',
                    'title' => 'Lexus',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Lexus.png'
                ],
            'Lincoln' =>
                [
                    'image_url' => '/statics/images/img/models/22.png',
                    'title' => 'Lincoln',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Lincoln.png'
                ],
            'Maserati' =>
                [
                    'image_url' => '/statics/images/img/models/23.png',
                    'title' => 'Maserati',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Maserati.png'
                ],
            'Mazda' =>
                [
                    'image_url' => '/statics/images/img/models/24.png',
                    'title' => 'Mazda',
                    'car_image_url' => '/statics/images/img/makes_img_seo/mazda.png'
                ],
            'Mercedes-Benz' =>
                [
                    'image_url' => '/statics/images/img/models/25.png',
                    'title' => 'Mercedes',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Mercedes.png'
                ],
            'MINI' =>
                [
                    'image_url' => '/statics/images/img/models/26.png',
                    'title' => 'Mini',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Mini.png'
                ],
            'Mitsubishi' =>
                [
                    'image_url' => '/statics/images/img/models/27.png',
                    'title' => 'Mitsubishi',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Mitsubishi.png'
                ],
            'Nissan' =>
                [
                    'image_url' => '/statics/images/img/models/28.png',
                    'title' => 'Nissan',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Nissan.png'
                ],
            'Porsche' =>
                [
                    'image_url' => '/statics/images/img/models/29.png',
                    'title' => 'Porsche',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Porsche.png'
                ],
            'Ram' =>
                [
                    'image_url' => '/statics/images/img/models/30.png',
                    'title' => 'Ram',
                    'car_image_url' => '/statics/images/img/makes_img_seo/RAM.png'
                ],
            'Subaru' =>
                [
                    'image_url' => '/statics/images/img/models/31.png',
                    'title' => 'Subaru',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Subaru.png'
                ],
            'Toyota' =>
                [
                    'image_url' => '/statics/images/img/models/32.png',
                    'title' => 'Toyota',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Toyota.png'
                ],
            'Volkswagen' =>
                [
                    'image_url' => '/statics/images/img/models/33.png',
                    'title' => 'Volkswagen',
                    'car_image_url' => '/statics/images/img/makes_img_seo/Volkswagen.png'
                ],
            'Volvo' =>
                [
                    'image_url' => '/statics/images/img/models/34.png',
                    'title' => 'Volvo',
                    'car_image_url' => '/statics/images/img/makes_img_seo/volvo.png'
                ]
        ];
    }

    /**
     * @return bool
     */
    public function searchInState()
    {
        return $this->stage_data_provider['location_data']['state'] !== self::STATE_PLACEHOLDER;
    }

    /**
     * @return array
     */
    public function getAvailableCities()
    {
        //For State selected - find all cities and make data provider from them
        $selected_state = $this->config['state'];
        $key = SeoCatalog::SEO_STATES_UNIQUE_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_' . $selected_state;
        $data_from_cache = TempStorage::get($key);

        $cities = [

        ];

        if (!empty($data_from_cache)) {
            $data_from_cache = $data_from_cache['value'];
            //Creating the paginator based on all cities in state

            $pages = new Pagination(
                ['totalCount' => count($data_from_cache), 'pageSize' => SeoCatalog::SEO_UAV_AMOUNT]
            );
            $this->city_pages = $pages;

            //Define offset and limit to get the part of cities
            if ($this->inQuickVersion()) {
                $part_of_zips = $data_from_cache;
            } elseif ($this->inFullVersion()) {
                $part_of_zips = array_slice($data_from_cache, $this->city_pages->offset, $this->city_pages->limit);
            }
            //Find the cities by their zips
            $zipdata = Zip::find()->where(['zip' => $part_of_zips])->all();

            foreach ($zipdata as $city) {
                $cars_amount = '';
                $zip = F3deshaHelpers::zipCompensator($city['zip']);
                $key = SeoCatalog::SEO_CITIES_UNIQUE_CARS_AMOUNT_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_zip_' . $zip;
                if ($data = TempStorage::get($key)) {
                    $cars_amount = $data['value'];
                    $cities[] = [
                        'city' => $city['city'],
                        'link' => F3deshaHelpers::prepareForUrl(
                            Url::base(
                            ) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
                                $city['city']
                            )
                        ),
                        'ammount_of_items' => $cars_amount
                    ];
                } elseif ($this->inFullVersion()) {
                        $coverageDealersSet = Dealerinventory::getCoverageDealerSet((int)$zip);
                        if (empty($coverageDealersSet)) {
                            self::deleteCityByZipFromSeocatalogList($zip, $selected_state);
                        } else {
                            $managersMask = Dealerinventory::extractManagersMask($coverageDealersSet);
                            $filters = Dealerinventory::activeItemsCommonConditions($managersMask);
                            $items = Dealerinventory::find()->where($filters);
                            $cars_amount = $items->count();
                            $key = SeoCatalog::SEO_CITIES_UNIQUE_CARS_AMOUNT_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_zip_' . $zip;
                            TempStorage::set($key, $cars_amount, self::TEMP_STORAGE_SEOCATALOG_CLASS);
                            $cities[] = [
                                'city' => $city['city'],
                                'link' => F3deshaHelpers::prepareForUrl(
                                    Url::base(
                                    ) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
                                        $city['city']
                                    )
                                ),
                                'ammount_of_items' => $cars_amount
                            ];
                        }
                    }

            }
        }
        usort(
            $cities,
            function ($a, $b) {
                $a = $a['city'];
                $b = $b['city'];

                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            }
        );
        return $cities;
    }

    /**
     * @param $zip
     * @param string $cities_state
     */
    public static function deleteCityByZipFromSeocatalogList($zip, string $cities_state)
    {
        $states_key = SeoCatalog::SEO_STATES_UNIQUE_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_' . $cities_state;
        $data_from_cache = TempStorage::get($states_key);
        if (($key = array_search($zip, $data_from_cache['value'])) !== false) {
            unset($data_from_cache['value'][$key]);
            TempStorage::set($states_key, $data_from_cache['value'], self::TEMP_STORAGE_SEOCATALOG_CLASS);
        }
    }

    /**
     * @return bool
     */
    public function searchAnywhere()
    {
        return $this->stage_data_provider['location_data']['state'] === self::STATE_PLACEHOLDER;
    }

	/**
	 * @return bool
	 */
	public function searchInfoMode()
	{
		return $this->stage_data_provider['location_data']['state'] === self::INFO_MODE_STATE_PLACEHOLDER && $this->stage_data_provider['location_data']['city'] === self::INFO_MODE_CITY_PLACEHOLDER;
	}

    /**
     * @return array
     * @throws Exception
     */
    public function getAvailableStates()
    {
        $states = [];
        $states_grouped = [];
        $cities_summary = [];
        $state_cover_by_dealership_map = [];

        //Search if we have collection of covered states in cache table
        $data_from_cache = TempStorage::get(
            SeoCatalog::SEO_STATES_COLLECTION_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY
        );

        if (!$data_from_cache && $this->inFullVersion()) {
            //If we dont have it, lets find and save
            //Get all active dealerships
            $dealerships = Dealership::find()->where(Dealership::isActive())->all();

            //For each dealership find all cities in its radius
            foreach ($dealerships as $dealership) {
                $sql = "
				SELECT @src := Point(latitude,longitude) FROM lfl_zipdata WHERE zip = {$dealership->zip};";
                $sql2 = "CALL geobox_pt(@src, {$dealership->dealer_radius}, @top_lft, @bot_rgt);";
                $sql3 =
                    "SELECT g.city, g.state_code, g.zip, geodist(X(@src), Y(@src), latitude, longitude) AS dist
				FROM lfl_zipdata g
				WHERE g.latitude BETWEEN X(@bot_rgt) AND X(@top_lft)
				AND g.longitude BETWEEN Y(@top_lft) AND Y(@bot_rgt)
				HAVING dist < {$dealership->dealer_radius}
				ORDER BY dist asc LIMIT 100000;";


                $transaction = Yii::$app->db->beginTransaction();
                Yii::$app->db->createCommand($sql)->queryAll();
                Yii::$app->db->createCommand($sql2)->execute();
                $cities_in_range_of_dealership = Yii::$app->db->createCommand($sql3)->queryAll();
                $transaction->commit();

                //Rearrange cities by State code
                foreach ($cities_in_range_of_dealership as $i => $city) {
                    $cities_in_range_of_dealership[$city['state_code']][$city['city']] = $city;
                    unset($cities_in_range_of_dealership[$i]);
                }
                $states_in_range_of_dealership = array_keys($cities_in_range_of_dealership);

                //Form array of coverage states by dealerships
                foreach ($states_in_range_of_dealership as $state_code) {
                    $state_cover_by_dealership_map[$state_code][] = $dealership->id;
                }

                //Merge all data in one array. Cache this
                foreach ($cities_in_range_of_dealership as $state => $cities_of_state) {
                    foreach ($cities_of_state as $city_name => $city) {
                        $cities_summary[$state][$city_name] = $city;
                    }
                }
            }

            //Group zips of cities by State code
            foreach ($cities_summary as $state_code => $cities_of_state) {
                ksort($cities_of_state);
                foreach ($cities_of_state as $city) {
                    $states_grouped[$state_code][] = $city['zip'];
                }
            }

            $states_covered_collection = array_keys($states_grouped);
            sort($states_covered_collection);

            //Cache all states covered
            TempStorage::set(
                SeoCatalog::SEO_STATES_COLLECTION_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY,
                $states_covered_collection,
                self::TEMP_STORAGE_SEOCATALOG_CLASS
            );

            foreach ($states_grouped as $state_code => $cities_in_state) {
                //Cache each states cities
                TempStorage::set(
                    SeoCatalog::SEO_STATES_UNIQUE_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_' . $state_code,
                    $cities_in_state,
                    self::TEMP_STORAGE_SEOCATALOG_CLASS
                );

                $states[$state_code]['state'] = $state_code;
                $states[$state_code]['link'] = F3deshaHelpers::prepareForUrl(Url::base() . '/find/' . $state_code);

                //Find all dealerships assigned to this state
                if (!empty($state_cover_by_dealership_map[$state_code])) {
                    $managers_mask = Dealership::findAllManagersInState($state_cover_by_dealership_map[$state_code]);
                    $filters = Dealerinventory::activeItemsCommonConditions($managers_mask);
                    //Count all active cars with managers mask of this dealer managers
                    $amount = Dealerinventory::find()->where($filters)->count();
                    //Save each states amount of cars in cache
                    TempStorage::set(
                        SeoCatalog::SEO_STATES_UNIQUE_CARS_AMOUNT_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_' . $state_code,
                        $amount,
                        self::TEMP_STORAGE_SEOCATALOG_CLASS
                    );
                    $states[$state_code]['ammount_of_items'] = $amount;
                }
            }
        } else {
            //If we have cache, just load it
            $data_from_cache = $data_from_cache['value'];
            foreach ($data_from_cache as $state) {
                $states[$state]['state'] = $state;

                $states[$state]['link'] = F3deshaHelpers::prepareForUrl(Url::base() . '/find/' . $state);
                if ($this->inFullVersion()) {
                    $amount = TempStorage::get(
                        SeoCatalog::SEO_STATES_UNIQUE_CARS_AMOUNT_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_' . $state
                    );
                    $states[$state]['ammount_of_items'] = !empty($amount) ? $amount['value'] : 0;
                }
            }
        }

        return $states;
    }

    /**
     * @param string $state_to_start
     * @throws Exception
     */
    public static function generateSeolinksFile($state_to_start = '')
    {
        //first check if old file exists
        //If exists create new file with new postfix and generate links there
        //When complete, remove old file and rename new file with its name

        $all_states_seocatalog = new SeoCatalog(
            [
                'state' => SeoCatalog::STATE_PLACEHOLDER,
                'city' => null,
                'make' => null,
                'model' => null,
                'year' => null,
                'trim' => null,
                'version' => SeoCatalog::QUICK_VERSION
            ]
        );

        $path = Yii::getAlias('@runtime');
		if(file_exists($path.'/'.self::SEOLINK_FILE_NAME.'.txt')){
			$handle = fopen($path.'/'.self::SEOLINK_FILE_NAME.'_new.txt', 'a');
			$all_states_seocatalog->handleContentOfSeolinks($handle, $state_to_start);
			fclose($handle);
			unlink($path.'/'.self::SEOLINK_FILE_NAME.'.txt');
			rename($path.'/'.self::SEOLINK_FILE_NAME.'_new.txt', $path.'/'.self::SEOLINK_FILE_NAME.'.txt');
		} else {
        $handle = fopen($path . '/' . self::SEOLINK_FILE_NAME . '.txt', 'a');
        $all_states_seocatalog->handleContentOfSeolinks($handle, $state_to_start);
        fclose($handle);
        }
        unset($all_states_seocatalog);
    }

    /**
     * @param $handle
     * @param string $state_to_start
     * @throws Exception
     */
    public function handleContentOfSeolinks($handle, $state_to_start = '')
    {
        $iteration_flag = true;
        if (!empty($state_to_start)) {
            $iteration_flag = false;
        }


        foreach ($this->stage_data_provider['available_states'] as $state => $state_data) {
            if (!$iteration_flag && $state_to_start === $state) {
                $iteration_flag = true;
            }
            if ($iteration_flag) {
                $all_cities_in_state = new SeoCatalog(
                    [
                        'state' => $state,
                        'city' => null,
                        'make' => null,
                        'model' => null,
                        'year' => null,
                        'trim' => null,
                        'version' => SeoCatalog::QUICK_VERSION
                    ]
                );
                
                if($state === 'i'){
                	$all_cities_in_state->stage_data_provider['available_cities'][] = [
                		'city' => 'a',
						'link' => 'https://carvoy.com/find/i/a',
						'ammount_of_items' => '0'
					];
				}

                fwrite($handle, $state_data['link'] . "\n");
                echo "Generated " . $state_data['link'] . "\n";

                foreach ($all_cities_in_state->stage_data_provider['available_cities'] as $city_data) {
                    $file_put_contents_by_city = '';
                    $all_makes_in_city = new SeoCatalog(
                        [
                            'state' => $state,
                            'city' => $city_data['city'],
                            'make' => null,
                            'model' => null,
                            'year' => null,
                            'trim' => null,
                            'version' => SeoCatalog::QUICK_VERSION
                        ]
                    );
                    //fwrite($handle, $city_data['link']."\n");
                    $file_put_contents_by_city .= $city_data['link'] . "\n";
                    echo "Generated " . $city_data['link'] . "\n";
                    foreach ($all_makes_in_city->stage_data_provider['available_makes'] as $make => $make_data) {
                        $all_models_in_make = new SeoCatalog(
                            [
                                'state' => $state,
                                'city' => $city_data['city'],
                                'make' => $make,
                                'model' => null,
                                'year' => null,
                                'trim' => null,
                                'version' => SeoCatalog::QUICK_VERSION
                            ]
                        );
                        $file_put_contents_by_city .= $make_data['link'] . "\n";
                        //fwrite($handle, $make_data['link']."\n");
                        echo "Generated " . $make_data['link'] . "\n";

                        foreach ($all_models_in_make->stage_data_provider['available_models'] as $model_data) {
                            $all_years_of_model = new SeoCatalog(
                                [
                                    'state' => $state,
                                    'city' => $city_data['city'],
                                    'make' => $make,
                                    'model' => $model_data['model'],
                                    'year' => null,
                                    'trim' => null,
                                    'version' => SeoCatalog::QUICK_VERSION
                                ]
                            );

                            foreach ($all_years_of_model->stage_data_provider['available_years'] as $year) {
                                $all_trims_in_model_year = new SeoCatalog(
                                    [
                                        'state' => $state,
                                        'city' => $city_data['city'],
                                        'make' => $make,
                                        'model' => $model_data['model'],
                                        'year' => $year,
                                        'trim' => null,
                                        'version' => SeoCatalog::QUICK_VERSION
                                    ]
                                );
                                $file_put_contents_by_city .= $model_data['link'] . "/$year\n";
                                //fwrite($handle, $model_data['link']."/$year\n");
                                echo "Generated " . $model_data['link'] . "/$year\n";

                                foreach ($all_trims_in_model_year->stage_data_provider['available_trims'] as $trim_data) {
                                	if($trim_data['link'] === 'https://carvoy.com/find/i/a/BMW/6+Series/2019/650i'){
                                		$stop = true;
									}
                                    $file_put_contents_by_city .= $trim_data['link'] . "\n";
                                    echo "Generated " . $trim_data['link'] . "\n";
                                }
                                unset($all_trims_in_model_year);
                            }
                            unset($all_years_of_model);
                        }
                        unset($all_models_in_make);
                    }
                    fwrite($handle, $file_put_contents_by_city);
                    unset($file_put_contents_by_city);
                }
                unset($all_cities_in_state);
            }
        }
    }

    /**
     * @param $zip
     * @param $key
     * @param string $selected_state
     */
    public static function calculateAmountForZipAndCache($zip, $key, string $selected_state)
    {
        $coverageDealersSet = Dealerinventory::getCoverageDealerSet((int)$zip);
        //If for example coverage set is empty (dealer is in range but his cars not allowed to be in catalog)
        //then we need delete this city from seocatalogs list of cities.
        if (empty($coverageDealersSet)) {
            self::deleteCityByZipFromSeocatalogList($zip, $selected_state);
        } else {
            $managersMask = Dealerinventory::extractManagersMask($coverageDealersSet);
            $filters = Dealerinventory::activeItemsCommonConditions($managersMask);
            $items = Dealerinventory::find()->where($filters);
            $cars_amount = $items->count();
            TempStorage::set($key, $cars_amount, self::TEMP_STORAGE_SEOCATALOG_CLASS);
        }
    }

	/**
	 * @param array $filters
	 * @return array
	 */
    public static function getIgniteCatalogLinkForCarsByZipAndFilters(array $filters){
		$coverageDealersSet = Dealerinventory::getCoverageDealerSet((int)$filters['zip']);
		unset($filters['zip']);
		$managersMask = Dealerinventory::extractManagersMask($coverageDealersSet);
		
		$filters_mask = Dealerinventory::activeItemsCommonConditions($managersMask);
		$items = Dealerinventory::find()->where($filters_mask)->andWhere($filters);
		$cars_amount = $items->count();
		$link = F3deshaHelpers::prepareForUrl(
			Url::base(true) . '/' . Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::encodeForUrl(
				$filters['make']
			) . '&model=' . F3deshaHelpers::encodeForUrl(
				$filters['model']
			) . '&year=' . F3deshaHelpers::encodeForUrl(
				$filters['year']
			)
		);
		if(!empty($filters['short_trim'])){
			$link .= '&trim=' . F3deshaHelpers::encodeForUrlIgnite($filters['short_trim']);
		}
    	return [
			'found_in_catalog' => $cars_amount,
			'items_amount' => $cars_amount,
			'link_on_catalog' => $link
		];
	}

    /**
     *
     */
    public static function flushAllOptimizationData()
    {
        TempStorage::flushSeoCatalog();
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function runCatalogOptimization()
    {
        echo "Running cache creation for SeoCatalog\n";

        //First cache main catalog page with all states available
        TempStorage::clear(SeoCatalog::SEO_STATES_COLLECTION_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY);
        $seocatalog = new SeoCatalog(
            [
                'state' => SeoCatalog::STATE_PLACEHOLDER,
                'city' => null,
                'make' => null,
                'model' => null,
                'year' => null,
                'trim' => null
            ]
        );

        //After we have all states, lets make a cache for all of that states pages with cities
        if (!empty($seocatalog->stage_data_provider['available_states'])) {
            $states_to_cache = $seocatalog->stage_data_provider['available_states'];
            unset($seocatalog);

            foreach ($states_to_cache as $state_to_cache => $zips) {
                $data_from_cache = TempStorage::get(
                    SeoCatalog::SEO_STATES_UNIQUE_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_' . $state_to_cache
                );
                if (!empty($data_from_cache)) {
                    $zips_of_state = $data_from_cache['value'];
                    $count_of_cities = count($zips_of_state);

                    echo "Found {$count_of_cities} cities to be calculated for " . $state_to_cache . "\n";
                    sort($zips_of_state);
                    foreach ($zips_of_state as $i => $zip) {
                        $key = SeoCatalog::SEO_CITIES_UNIQUE_CARS_AMOUNT_PREFIX . SeoCatalog::SEO_STATES_CACHE_KEY . '_zip_' . $zip;
                        $divider_result = 0;
                        if ($i !== 0) {
                            $divider_result = $i / $count_of_cities;
                        }
                        $percent = round($divider_result, 2) * 100;

                        echo "Saving value for " . $key . " state " . $state_to_cache . " ({$i} of " . $count_of_cities . " | {$percent}%...) \n";
                        Yii::$app->queue->push(
                            new CountSeocatalogCarsAmountJob(
                                [
                                    'zip' => $zip,
                                    'key' => $key,
                                    'state_to_cache' => $state_to_cache,
                                ]
                            )
                        );
                        //self::calculateAmountForZipAndCache($zip, $key, $state_to_cache);

                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function buildOptionsMobileBlock()
    {
        $html = "<div class=\"trimhighlights_accardion_mobile_item\">
						<div class=\"trimhighlights_acc_top\">
							<strong>
								Optional equipment
							</strong>
							<i class=\"fa fa-angle-down\" aria-hidden=\"true\"></i>
						</div>
						<div class=\"trimhighlights_acc_content\">
							<div class=\"equipment_wrapper_mobile\">";
        if (!empty($this->stage_data_provider['available_trims'][0]['optional_equipment'])) {
            foreach ($this->stage_data_provider['available_trims'][0]['optional_equipment'] as $groupName => $option_group) {
                $html .= "<div class=\"equipment_item\">
														<strong>
															{$groupName}
														</strong>";
                foreach ($option_group as $option) {
                    $html .= "<div class=\"equipment_descr\">
															<p>{$option['description']} <i>{$option['secondary_description']}</i></p>
															<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>
														</div>";
                }
                $html .= "</div>";
            }
        }
        $html .= "</div>							
						</div>
					</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function buildOptionsBlock()
    {
        $html = "<div class=\"content_descr\">
							<div class=\"equipment_wrapper\">";
        if (!empty($this->stage_data_provider['available_trims'][0]['optional_equipment'])) {
            $visual_column_groups = [];
            $semaphor = [0, 1, 2];
            foreach ($this->stage_data_provider['available_trims'][0]['optional_equipment'] as $groupName => $option_group) {
                $current_index = current($semaphor);
                $visual_column_groups[$current_index][$groupName] = $option_group;
                $current_index = next($semaphor);
                if (!$current_index) {
                    $current_index = reset($semaphor);
                }
            }
            foreach ($visual_column_groups as $column_index => $column_group) {
                $html .= "<div class=\"equipment_collumn\">";
                foreach ($column_group as $group_name => $group) {
                    $html .= "<div class=\"equipment_item\">
										<strong>
											{$group_name}
										</strong>";
                    foreach ($group as $item) {
                        $html .= "<div class=\"equipment_descr\">
											<p>{$item['description']} <i>{$item['secondary_description']}</i></p>
											<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>
										</div>";
                    }
                    $html .= "</div>";
                }
                $html .= "</div>";
            }
        }

        $html .= "</div>
							<div class='wrap_btn_table'>
								<a href='' class='btn_read_more_table trimHilights_btn'>
									<span>read more</span> 
									<i class='fa fa-angle-double-down' aria-hidden='true'></i>
								</a>
							</div>
						</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function getTrimImagesGalleryMobile()
    {
        $html = "<div class=\"trimhighlights_accardion_mobile_item\">
						<div class=\"trimhighlights_acc_top\">
							<strong>
								Photo gallery
							</strong>
							<i class=\"fa fa-angle-down\" aria-hidden=\"true\"></i>
						</div>
						<div class=\"trimhighlights_acc_content\">
							<div class=\"model_car_gallery_list\">";
        if (!empty($this->stage_data_provider['available_trims'][0]['gallery_images'])) {
            foreach ($this->stage_data_provider['available_trims'][0]['gallery_images'] as $image_url) {
                $html .= "<div class=\"model_car_gallery_item\">
														<div class=\"model_car_gallery_content\">
															<div class=\"wrap_img single\">
																<a href=\"\">
																	<img src=\"{$image_url}\" alt=\"\" />
																</a>
															</div>
														</div>
													</div>";
            }
        }
        $html .= "</div>								
						</div>				
					</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function getTrimImagesGallery()
    {
        $html = "<div class=\"model_car_gallery_list\">";
        if (!empty($this->stage_data_provider['available_trims'][0]['gallery_images'])) {
            foreach ($this->stage_data_provider['available_trims'][0]['gallery_images'] as $image_url) {
                $html .= "<div class=\"model_car_gallery_item\">
									<div class=\"model_car_gallery_content\">
										<div class=\"wrap_img single\">
											<a href=\"\">
												<img src=\"{$image_url}\" alt=\"\" />
											</a>
										</div>
									</div>
								</div>";
            }
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function getFullCarSpecAccordionMobile()
    {
        $html = "<div class=\"trimhighlights_accardion_mobile_item\">
						<div class=\"trimhighlights_acc_top\">
							<strong>
								specifications
							</strong>
							<i class=\"fa fa-angle-down\" aria-hidden=\"true\"></i>
						</div>
						<div class=\"trimhighlights_acc_content\">
							<div class=\"specifications_accardion\">";
        if (!empty($this->stage_data_provider['available_trims'][0]['full_car_specs']['equipment'])) {
            foreach ($this->stage_data_provider['available_trims'][0]['full_car_specs']['equipment'] as $group_name => $equipment_group) {
                $html .= "<div class=\"specifications_accardion_item\">
									<div class=\"acc_spec_top\">
										<strong>
											{$group_name}
										</strong>
										<div class=\"icon_acc\">
											<i class=\"fa fa-angle-down\" aria-hidden=\"true\"></i>
										</div>
									</div>
									<div class=\"acc_content\">";
                foreach ($equipment_group as $name => $item) {
                    $html .= "<div class=\"acc_content_item\">
											<strong>
												{$name}:
											</strong>
											<div class=\"acc_content_item_descr\">
												<p>";
                    foreach ($item as $element) {
                        $html .= $element['name'] . "<br>";
                    }
                    $html .= "</p>
											</div>
										</div>";
                }

                $html .= "</div>
								</div>";
            }
        }

        $html .= "</div>							
						</div>				
					</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function getFullCarSpecAccordion()
    {
        $html = "";
        if (!empty($this->stage_data_provider['available_trims'][0]['full_car_specs']['equipment'])) {
            $html .= "<div class=\"content_descr\">
							<div class=\"specifications_accardion\">";
            foreach ($this->stage_data_provider['available_trims'][0]['full_car_specs']['equipment'] as $group_name => $equipment_group) {
                $html .= "<div class=\"specifications_accardion_item\">";
                $html .= "<div class=\"acc_spec_top\">
										<strong>
											{$group_name}
										</strong>
										<div class=\"icon_acc\">
											<i class=\"fa fa-angle-down\" aria-hidden=\"true\"></i>
										</div>
									</div>";
                $html .= "<div class=\"acc_content\">";
                foreach ($equipment_group as $name => $item) {
                    $html .= "<div class=\"acc_content_item\">
											<strong>
												{$name}:
											</strong>";

                    $html .= "<div class=\"acc_content_item_descr\">
												<p>";
                    foreach ($item as $element) {
                        $html .= $element['name'] . "<br>";
                    }
                    $html .= "</p>
											</div>
										</div>
									";
                }

                $html .= "</div></div>";
            }
            $html .= "</div>
						</div>";
        }
        return $html;
    }

    /**
     * @return string
     */
    public function buildRandomIgniteItemsUALV()
    {
        $html = "";

        if (!empty($this->stage_data_provider['random_ignite_items'])) {
            $html = "<div class=\"similar_model_list no_slider_similar_model_list\">";
            foreach ($this->stage_data_provider['random_ignite_items'] as $item) {
                ////
                if (!empty($item->photos)) {
                    $photo_url = $item->photos[0]->url;
                } else {
                    $photo_url = '/statics/web/images/itemCarOptimized.jpg';
                }

                $transmision_type = $item->transmissionType === 1130 ? 'Automatic' : 'Manual';
                $location = $item->receiveItemsLocationByZip($item->zip);
                $ignite_link = Dealerinventory::MODULE_NAME . '/' . F3deshaHelpers::prepareForUrl(
                        F3deshaHelpers::encodeForUrl($item->make) . '/' . F3deshaHelpers::encodeForUrl(
                            $item->model
                        ) . '/' . F3deshaHelpers::encodeForUrl($item->short_trim) . '/' . F3deshaHelpers::encodeForUrl(
                            $item->year
                        ) . '/' . F3deshaHelpers::encodeForUrl(
                            $location['state_code']
                        ) . '/' . F3deshaHelpers::encodeForUrl($location['city']) . '/' . F3deshaHelpers::encodeForUrl(
                            $item->id
                        ) . '?monthAlgoritm.zip=' . $this->stage_data_provider['location_data']['zip']
                    );

                ///

                $html .= "<div class=\"similar_model_item\">
						<a href=\"{$ignite_link}\" class=\"similar_model_item_content\">
							<span class=\"wrap_image\">
								<span>
									<img src=\"{$photo_url}\" alt=\"{$photo_url}\" />
								</span>
							</span>
							<span class=\"title_model\">
								<span>
									{$item->year} {$item->make} {$item->model} {$item->short_trim} (#{$item->id})
								</span>
							</span>
							<span class=\"descr_model\">
								<span>
									{$item->numberOfDoors} <span>Doors</span>
								</span>
								<span>
									{$item->body_type} <span>drive types</span>
								</span>
								<span>
									$transmision_type <span>Transmission type</span>
								</span>
							</span>
							<span class=\"wrap_btn\">
								<span class=\"btn_select\">
									Select
								</span>
							</span>								
						</a>
					</div>";
            }
            $html .= "</div>";
        }
        return $html;
    }

    /**
     * @return array|Dealerinventory[]|DealerinventoryHasGenericEquipment[]|Dealership[]|Zip[]|ActiveRecord[]
     */
    public function getRandomIgniteItemsInCityByMakeModelYearTrim()
    {
        $ignite_items = [];

        if ($this->zipExistsForLocationRoute()) {
            $managersMask = $this->stage_data_provider['managers_mask'];
            $filters = Dealerinventory::activeItemsCommonConditions($managersMask);
            $ignite_items = Dealerinventory::find()->where($filters)->andWhere(
                [
                    'make' => $this->config['make'],
                    'model' => $this->config['model'],
                    'year' => $this->config['year'],
                    'short_trim' => $this->config['trim']
                ]
            )->limit(24)->orderBy(new Expression('rand()'))->with('photos')->all();
        }
        return $ignite_items;
    }

    /**
     * @return string
     */
    public function breadcrumbsWidget()
    {
        $widget_html = "<div class=\"top_breadcrumbs\">";
        $widget_html .= '<a href="' . F3deshaHelpers::prepareForUrl(
                Url::base(
                ) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
                    $this->stage_data_provider['location_data']['city']
                )
            ) . '" > search</a>';
        if (!empty($this->config['make'])) {
            $widget_html .= '<a href="' . F3deshaHelpers::prepareForUrl(
                    Url::base(
                    ) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
                        $this->stage_data_provider['location_data']['city']
                    ) . '/' . F3deshaHelpers::encodeForUrl($this->config['make'])
                ) . '" > ' . $this->config['make'] . '</a>';
        }
        if (!empty($this->config['model'])) {
            $widget_html .= '<a href="' . F3deshaHelpers::prepareForUrl(
                    Url::base(
                    ) . '/find/' . $this->stage_data_provider['location_data']['state'] . '/' . F3deshaHelpers::encodeForUrl(
                        $this->stage_data_provider['location_data']['city']
                    ) . '/' . F3deshaHelpers::encodeForUrl($this->config['make']) . '/' . F3deshaHelpers::encodeForUrl(
                        $this->config['model']
                    )
                ) . '" > ' . $this->config['model'] . '</a>';
        }
        if (!empty($this->config['trim'])) {
            $widget_html .= '<a href="#" > ' . $this->config['trim'] . '</a>';
        }
        $widget_html .= "</div>";
        return $widget_html;
    }

    /**
     * @return string
     */
    public function buildYearWidget()
    {
        $html = "<div class=\"wrapper_year\">";
        if (!empty($this->stage_data_provider['available_years'])) {
            foreach ($this->stage_data_provider['available_years'] as $y) {
                $class = "";
                if (!empty($y['fake'])) {
                    $class = " class=\"crossed\"";
                }
                if ($this->config['year'] == $y['year']) {
                    $class = " class=\"active\"";
                }
                $html .= "<a {$class} href=\"{$y['link']}\">{$y['year']}</a>";
            }
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function buildTrimHighlightImageBlock()
    {
        if (!empty($this->stage_data_provider['available_trims'][0]['main_photo'])) {
            $img = $this->stage_data_provider['available_trims'][0]['main_photo'];
        } else {
            $img = '/statics/images/img/interior.png';
        }
        $html = "<div class=\"interior_block\" id=\"interior_block\">
				<div class=\"interior_block_wrapper\">
					<div class=\"interior_image\">
						<img src=\"{$img}\" alt=\"interior\" />
					</div>
					<div class=\"interior_descr\">
						<ul class=\"interior_listing\">
							<li>
								<strong>
									Popular features
								</strong>
							</li>";
        if (!empty($this->stage_data_provider['available_trims']) && count(
                $this->stage_data_provider['available_trims']
            ) === 1) {
            if (!empty($this->stage_data_provider['available_trims'][0]['popular_features'])) {
                $this->stage_data_provider['available_trims'][0]['popular_features'] = array_slice(
                    $this->stage_data_provider['available_trims'][0]['popular_features'],
                    9
                );
                foreach ($this->stage_data_provider['available_trims'][0]['popular_features'] as $feature_name => $feature_content) {
                    if ($feature_content['type'] === 'value') {
                        $table_items_value = $feature_content['value'];
                    } elseif ($feature_content['type'] === 'checkbox') {
                        switch ($feature_content['value']) {
                            case 1:
                                $table_items_value = "<i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i>";
                                break;
                            case 2:
                                $table_items_value = "<i class=\"fa fa-times-circle-o\" aria-hidden=\"true\"></i>";
                                break;
                            case 3:
                                $table_items_value = "<div class=\"line-horizontal\"></div>";
                                break;
                        }
                    }

                    $html .= "<li>
								<span>{$feature_name}</span>
								{$table_items_value}
							</li>
							";
                }
            }
        } else {
            $html .= "<li>
						<span>No features found</span>
					</li>";
        }
        $html .= "</ul>
					</div>
				</div>
				<div class=\"interior_bottom\">
					<span>
						<i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i>
						Included
					</span>
					<span>
						<i class=\"fa fa-times-circle-o\" aria-hidden=\"true\"></i>
						Optional
					</span>
				</div>
			</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function buildTrimSlider()
    {
        $html = "<div class=\"car_model_list\">";
        if (!empty($this->stage_data_provider['available_trims'])) {
            foreach ($this->stage_data_provider['available_trims'] as $trim) {
                $html .= "<div class=\"car_model_item\">
							<a href=\"{$trim['link']}\" class=\"car_model_item_content\">
								<span class=\"wrap_image\">
									<span>
										<img src=\"{$trim['image']}\" alt=\"1\" />
									</span>
								</span>
								<span class=\"title_model\">
									<span>
										{$trim['trim']}
									</span>
								</span>
								<span class=\"descr_model\">
									<span>
										{$trim['doors']} <span>Doors</span>
									</span>
									<span>
										{$trim['drive_type']} <span>drive types</span>
									</span>
									<span>
										{$trim['horsepower']} <span>HP</span>
									</span>
								</span>
								<span class=\"wrap_btn\">
									<span class=\"btn_select\">
										Select
									</span>
								</span>								
							</a>";
                if($trim['available_cars'] > 0) {
					$html .= "<div class=\"car_model_item_text\">
								<a href=\"{$trim['ignite_link']}\">View Inventory</a>
								<strong>
									{$trim['available_cars']} cars <span>available</span>
								</strong>
							</div>";
				}
						$html .= "</div>";
            }
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * @return string
     */
    public function buildModelSlider()
    {
        $html = '<div class="similar_model_list slider_similar_model_list">';
        if (!empty($this->stage_data_provider['available_models'])) {
            foreach ($this->stage_data_provider['available_models'] as $model) {
                $html .= "<div class=\"similar_model_item\">
						<a href=\"{$model['link']}\" class=\"similar_model_item_content\">
							<span class=\"wrap_image\">
								<span>
									<img src=\"{$model['image']}\" alt=\"11\" />
								</span>
							</span>
							<span class=\"title_model\">
								<span>
									{$model['model']}
								</span>
							</span>
							<span class=\"wrap_btn\">
								<span class=\"btn_select\">
									Select
								</span>
							</span>								
						</a>						
					</div>";
            }
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * @param $ziplocator
     */
    public function zipLocatorWidget($ziplocator)
    {
        echo "<div class='zip_locator_block'>";
        echo "<div class=\"catalog_container\">";
        echo "<strong class=\"zip_loc_title\">Location</strong>";
        echo "<div class=\"zip_locator_wrapper\">";
        $form = ActiveForm::begin(
            ['options' => ['ng-submit' => 'seocatalogObject.zipLocatorForm.changeSeocatalogLocation($event)']]
        );
        echo $form->field($ziplocator, 'zip')->textInput(
            [
                'ng-model' => 'seocatalogObject.zipLocatorForm.fields.residence.zip',
                'class' => 'zipLocatorInput',
                'placeholder' => 'Enter zip'
            ]
        )->label(false);
        echo "<button type='submit' name='contact-button'><i class=\"fa fa-search\" aria-hidden=\"true\"></i></button>";
        //echo \yii\helpers\Html::submitButton('<i class="fa fa-search" aria-hidden="true"></i>', ['class' => '', 'name' => 'contact-button']);
        ActiveForm::end();
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    /**
     * @param $zipdata
     * @return bool|mixed
     */
    public function setLocatorObject($zipdata)
    {
        if (!empty($zipdata)) {
            $location = $this->formLocatorObjectByZipdata($zipdata);
            Yii::$app->track->tracking_session->tracking_storage->setSessionProvider(
                ['users_geolocation' => $location]
            );
            return $this->getLocatorObject();
        }
        return false;
    }

    /**
     * @param $zipdata1
     * @param $zipdata2
     * @return bool
     */
    public function isZipdataTheSame($zipdata1, $zipdata2)
    {
        return $zipdata1 === $zipdata2;
    }

    /**
     * @param $object1
     * @param $object2
     * @return bool
     */
    public function isLocatorObjectsTheSame($object1, $object2)
    {
        return $object1 == $object2;
    }

    /**
     * @return bool
     */
    public function isLocatorObjectExists()
    {
        if (!empty($this->getLocatorObject())) {
            return true;
        }
        return false;
    }

    /**
     * @param $locatorObject
     * @return bool
     */
    public function getZipFromLocatorObject($locatorObject)
    {
        if (!empty($locatorObject['processed']['location_details']['zip'])) {
            return $locatorObject['processed']['location_details']['zip'];
        }
        return false;
    }

    /**
     * @return bool
     */
    public function searchInCity()
    {
        return $this->stage_data_provider['location_data']['city'] !== self::CITY_PLACEHOLDER;
    }

    /**
     * @return array
     */
    public function wrapInUALVDataProviderForStates()
    {
        $dataProviderArray = [];
        $i = 0;
        foreach ($this->stage_data_provider['available_states'] as $state) {
            $dataProviderArray[$i]['link_text'] = $state['state'];
            $dataProviderArray[$i]['link_url'] = $state['link'];
            $dataProviderArray[$i]['description'] = $state['ammount_of_items'] . ' cars found in ' . $state['state'];
            $i++;
        }
        return $dataProviderArray;
    }

    /**
     * @return array
     */
    public function wrapInUALVDataProviderForCities()
    {
        $dataProviderArray = [];
        $i = 0;
        foreach ($this->stage_data_provider['available_cities'] as $city) {
            $dataProviderArray[$i]['link_text'] = $city['city'];
            $dataProviderArray[$i]['link_url'] = $city['link'];
            $dataProviderArray[$i]['description'] = $city['ammount_of_items'] . ' cars found in ' . $city['city'];
            $i++;
        }
        return $dataProviderArray;
    }

    /**
     * @return array
     */
    public function wrapInUALVDataProviderForMakes()
    {
        $dataProviderArray = [];
        $i = 0;
        foreach ($this->stage_data_provider['makes_for_UALV_make_city'] as $link => $make) {
            $dataProviderArray[$i]['link_text'] = $make['title'] . ' in ' . $make['city'];
            $dataProviderArray[$i]['link_url'] = Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::prepareForUrl(
                    F3deshaHelpers::encodeForUrl($link)
                );
            $dataProviderArray[$i]['description'] = 'Lease ' . $make['title'] . ' in ' . $make['city'] . ', ' . $make['state'];
            $i++;
        }
        return $dataProviderArray;
    }

    /**
     * @return array
     */
    public function wrapInUALVDataProviderForModels()
    {
        $dataProviderArray = [];
        $i = 0;
        foreach ($this->stage_data_provider['available_models'] as $model) {
            $dataProviderArray[$i]['link_text'] = $model['make'] . ' ' . $model['model'] . ' in ' . $model['city'];
            $dataProviderArray[$i]['link_url'] = Dealerinventory::MODULE_NAME . '?make=' . F3deshaHelpers::prepareForUrl(
                    F3deshaHelpers::encodeForUrl($model['make'])
                ) . '&model=' . F3deshaHelpers::prepareForUrl(F3deshaHelpers::encodeForUrl($model['model']));
            $dataProviderArray[$i]['description'] = 'Lease ' . $model['make'] . ' ' . $model['model'] . ' in ' . $model['city'] . ', ' . $model['state'];
            $i++;
        }
        return $dataProviderArray;
    }

    /**
     * @return string
     */
    public function availableForCityWidget()
    {
//		<p>
//	available for <span>Great Neck</span><span class='zip_locator_dropdown_switch'><i class=\"fa fa-angle-down arrow\" aria-hidden=\"true\"></i></span>
//							</p>
		
    		$object_input = [
    			'make' => $this->config['make'],
				'model' => $this->config['model'],
				'year' => $this->config['year']
			];
    		if(!empty($this->config['trim'])){
    			$object_input['short_trim'] = $this->config['trim'];
			}
    		$object_input_encoded = json_encode($object_input);
			$html = "<div ng-show=\"showSeocatalogLocatorWidget\"><div class=\"other_cars\" ng-init='geolocate.initSeocatalogLocatorWidget({$object_input_encoded})'>
							<span>{{carsFoundInIgnite}}</span>
							<script>
							$(document).ready(function () {
								$(\"#zipdrop2\").on(\"click focusout\", function (event) {
									event.stopPropagation();
									$(this).parents(\".drop-block\").addClass(\"open\");
								});
							});

						</script>
							<div class=\"dropdown mobile-geo-offset drop-block\"  >
							<span class='avail'>{{yourLocationPlaceholder}}</span><button style=\"position: relative; left: -6px; display: inline; font-size: 16px; border: none; background: none; color: #777\" class=\"didropdown btn btn-default dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"true\">
								<i class=\"fa fa-map-marker mobile-only-show\" aria-hidden=\"true\"></i><span class=\"mobile-only-hide\">{{ geolocate.formSeocatalogInfoModeLocation() }}</span>
								<span class='zip_locator_dropdown_switch'><i class=\"fa fa-angle-down arrow\" aria-hidden=\"true\"></i></span>
							</button>
							<ul id=\"zipdrop2\" class=\"dropdown-menu\" style=\"left: 130px; top: 40px; background-color: white; border: 1px solid rgb(204, 204, 204); max-width: none; width: 240px;\" aria-labelledby=\"dropdownMenu2\" >
								<div class=\"form-group\" style=\"margin-bottom: 0px; display: flex; padding: 5px;\">
									<input ng-keyup=\"\$event.keyCode == 13 && geolocate.changeLocationZip()\" ng-model=\"geolocate.location_object.zip\" type=\"text\" maxlength=\"5\" class=\"form-control\" style=\"border-color: {{geolocate.input_color}}; padding-right: 10px; margin-right: 5px; padding-left: 10px; min-width: auto; max-width: 150px; padding-top: 0!important; padding-bottom: 0!important;\" placeholder=\"Your Location Zip\">
									<button ng-click=\"geolocate.changeLocationZip()\" type=\"submit\" class=\"btn btn-primary\">Change</button>
								</div>
							</ul>
						</div>
						</div>";
		
    	
    	$html .= '	<div style="cursor: pointer;" class="wrap_btn" ng-click="$parent.seocatalogObject.zipLocatorForm.buildCtaActionForAvailableCitiesWidget(residence_zip, ctaType)">
							<a ng-href="{{ctaButtonLinkUrl}}" class="btn_view_cars">{{ctaButtonTitle}}</a>
						</div></div>';
        return $html;
    }

    /**
     * @return string
     */
    public function getStartingMSRP()
    {
		
    	if(!$this->isInfoMode){
			$filters = [
				'year' => $this->config['year'],
				'make' => $this->config['make'],
				'model' => $this->config['model'],
			];

			if (!empty($this->config['trim'])) {
				$filters['short_trim'] = $this->config['trim'];
			}
			
			$managersMask = $this->stage_data_provider['managers_mask'];
			$filters_mask = Dealerinventory::activeItemsCommonConditions($managersMask);
			
			$msrp = Dealerinventory::find()->where($filters_mask)->andWhere($filters)->min('msrp');
			$msrp = number_format($msrp);
		} else {
			$filters = [
				'year' => $this->config['year'],
				'division' => $this->config['make'],
				'model' => $this->config['model'],
			];

			if (!empty($this->config['trim'])) {
				$filters['short_trim'] = $this->config['trim'];
			}
			
			$msrp = ChromedataVehicle::find()->where($filters)->min('base_msrp');
			$msrp = number_format($msrp);
		}
		return '$' . $msrp;
	}

    /**
     * @param string $make
     * @return bool|mixed
     */
    public function getLogoByMake(string $make)
    {
        $makes_map = $this->getMakesMap();
        if (!empty($makes_map[$make])) {
            return $makes_map[$make]['image_url'];
        }
        return false;
    }

    /**
     * @param string $make
     * @return bool|mixed
     */
    public function getCarImageByMake(string $make)
    {
		if ($this->stage === self::TRIM_STAGE || $this->stage === self::TRIM_HIGHLIGHTS_STAGE) {
			if($this->isInfoMode){
				if ($this->stage === self::TRIM_STAGE) {
					$filters_secondary = [
						'division' => $this->config['make'],
						'model' => $this->config['model'],
						'year' => $this->config['year']
					];
				} elseif ($this->stage === self::TRIM_HIGHLIGHTS_STAGE) {
					$filters_secondary = [
						'division' => $this->config['make'],
						'model' => $this->config['model'],
						'year' => $this->config['year'],
						'short_trim' => $this->config['trim']
					];
				}

				if($filters_secondary['year'] === null){
					unset($filters_secondary['year']);
				}
				$item_with_images = ChromedataVehicle::find()->where($filters_secondary)->limit(1)->all();
				$item_with_images = $item_with_images[0];

				$exterior_img[0] = '';
				if(!empty($exterior_img = Yii::$app->carImages->getExterior($item_with_images->images, ImportImages::$EXTERIOR_THUMB_PARAMS, 1))){
					$image_url = $exterior_img[0];
					return $image_url;
				}
			} else {
				$search_limit_count = 20;

				$managersMask = $this->stage_data_provider['managers_mask'];
				$filters = Dealerinventory::activeItemsCommonConditions($managersMask);

				if ($this->stage === self::TRIM_STAGE) {
					$filters_secondary = [
						'make' => $this->config['make'],
						'model' => $this->config['model'],
						'year' => $this->config['year']
					];
				} elseif ($this->stage === self::TRIM_HIGHLIGHTS_STAGE) {
					$filters_secondary = [
						'make' => $this->config['make'],
						'model' => $this->config['model'],
						'year' => $this->config['year'],
						'short_trim' => $this->config['trim']
					];
				}

				if ($this->zipExistsForLocationRoute()) {
					$items = Dealerinventory::find()->where($filters_secondary)->andWhere($filters)->limit(
						$search_limit_count
					)->all();
					if (!empty($items)) {
						foreach ($items as $ignite_item) {
							$photo = $ignite_item->getPhotos()->where(['main' => '1'])->one();
							if (!empty($photo)) {
								return $photo->url;
							}
						}
					}
				}

			}
		}
    	
		$makes_map = $this->getMakesMap();
		if (!empty($makes_map[$make])) {
			if (array_key_exists('car_image_url', $makes_map[$make])) {
				return $makes_map[$make]['car_image_url'];
			}
		}
		return false;
    }

    /**
     * @return string
     */
    public function getTrimGroupGallery()
    {
        $html = "";
        if (!empty($this->stage_data_provider['grouped_photos'])) {
            $html = "<div class=\"model_car_gallery_list\">";
            foreach ($this->stage_data_provider['grouped_photos'] as $photo_url) {
                $html .= "<div class=\"model_car_gallery_item\">
							<div class=\"model_car_gallery_content\">
								<div class=\"wrap_img single\">
									<a href=\"\">
										<img src=\"{$photo_url}\" alt=\"5\" />
									</a>
								</div>
							</div>
						</div>";
            }
            $html .= "</div>";
        }
        return $html;
    }

    /**
     * @return string
     */
    public function buildTrimCompareTable()
    {
        $html = "";
        if (!empty($this->stage_data_provider['available_trims'])) {
            //build common collection of popular features
            $common_popular_features_collection = [];
            foreach ($this->stage_data_provider['available_trims'] as $key => $items) {
                if (!empty($items['popular_features'])) {
                    foreach ($items['popular_features'] as $key => $item) {
                        $common_popular_features_collection[$key][] = $item;
                    }
                }
            }

            $html = "<div class=\"model_car_table_wrapper\">
				<strong>
					Popuplar Features
				</strong>
				<div class=\"model_car_table_container\">";
            $html .= "<table>";
            foreach ($common_popular_features_collection as $name => $table_row) {
                $html .= "<tr>";
                $html .= "<td>{$name}</td>";
                foreach ($table_row as $item) {
                    if ($item['type'] === 'value') {
                        $table_items_value = $item['value'];
                    } elseif ($item['type'] === 'checkbox') {
                        switch ($item['value']) {
                            case 1:
                                $table_items_value = "<i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i>";
                                break;
                            case 2:
                                $table_items_value = "<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>";
                                break;
                            case 3:
                                $table_items_value = "<div class=\"line-horizontal\"></div>";
                                break;
                        }
                    }

                    $html .= "<td>{$table_items_value}</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</table>";
            $html .= "</div>
				<div class=\"wrap_btn_table\">
					<a href=\"\" class=\"btn_read_more_table trim_btn\">
						<span>read more</span> 
						<i class=\"fa fa-angle-double-down\" aria-hidden=\"true\"></i>
					</a>
				</div>
			</div>";
        }
        return $html;
    }

    /**
     * @return bool|mixed|string
     */
    public function correctRoute()
    {
        $current_url = Yii::$app->request->url;
        $exploded_url = explode('?', $current_url);
        if (count($exploded_url) > 1) {
            $get_params = Yii::$app->getRequest()->getQueryParams();
            $url_concatenated = '';
            foreach ($get_params as $key => $param) {
                $url_concatenated .= $param . '/';
            }

            $correct_url = F3deshaHelpers::prepareForUrl($url_concatenated);
            $correct_url = substr_replace($correct_url, "", -1);
            return $correct_url;
        }
        return false;
    }

    /**
     * @param $controller
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function build($controller)
    {
        if ($this->canBeBuilt()) {
            //Contact form analizer section
            $model = new ContactForm();
            $model->setScenario('faq');
            if (F3deshaHelpers::generateAndAnalizeForm($model)) {
                $this->reload = true;
            }

            $ziplocator = new SeoLocatorForm();
            $this->changeLocationRoute($ziplocator, $controller);

            if ($this->reload) {
                return $controller->refresh();
            }
            if ($this->redirect) {
                return $controller->redirect([$this->redirect]);
            }
            //Contact form analizer section end

            return $controller->render(
                '@modules/seo/views/layouts/main',
                [
                    'seocatalog' => $this,
                    'contact' => $model,
                    'ziplocator' => $ziplocator
                ]
            );
        }

		if($this->isInfoMode){
			throw new NotFoundHttpException();
		} else {
			return $controller->redirect(self::routeToCatalog(
				self::INFO_MODE_STATE_PLACEHOLDER,
				self::INFO_MODE_CITY_PLACEHOLDER,
				$this->config['make'],
				$this->config['model'],
				$this->config['year'],
				$this->config['trim']
			), 301);
		}	
		
    }

    /**
     * @return bool
     */
    public function canBeBuilt()
    {
        //Seocatalog can be build only for carvoy main domain. For dealerships and dealership group just redirects
        //to main home page
        if (!Yii::$app->domain->isMainDomain) {
            return false;
        }

        if ($this->can_be_built) {
            return true;
        }

        return false;
    }

    /**
     * @param $ziplocator
     * @param $controller
     */
    public function changeLocationRoute($ziplocator, $controller)
    {
        if ($ziplocator->load(Yii::$app->request->post()) && $ziplocator->validate()) {
            //Find city and state by zip
            $zipdata = Zip::find()->where(
                [
                    'zip' => $ziplocator->zip
                ]
            )->one();
            if ($zipdata) {
                Yii::$app->track->tracking_session->tracking_storage->setSessionProvider(
                    [self::SESSION_ZIP_LOCATOR_KEY => $zipdata->zip]
                );
                $current_url = $_SERVER['REQUEST_URI'];
                $parsed_url = explode('/', $current_url);
                unset($parsed_url[0]); // remove item at index 0
                $parsed_url = array_values($parsed_url); // 'reindex' array
                $parsed_url[self::STATE_STAGE] = $zipdata->state_code;
                $parsed_url[self::CITY_STAGE] = F3deshaHelpers::encodeForUrl($zipdata->city);
                $new_url = implode('/', $parsed_url);
                $url = F3deshaHelpers::prepareForUrl('/' . Url::base() . $new_url);
                //Redirect to another url
                $controller->redirect($url);
            }
        }
    }
}
