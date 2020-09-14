<?php

namespace modules\import;

use common\components\F3deshaHelpers;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\DealerinventoryActivator;
use modules\dealerinventory\models\backend\DealerinventoryDealerImportSettings;
use modules\seo\models\SeoCatalog;
use modules\users\models\User;
use yii\db\Exception;
use yii\db\Transaction;

/**
 * Class ImportBalancer
 * @package modules\import
 */
class ImportBalancer
{
    /**
     * @var string
     */
    public $trigger;
    /**
     * @var
     */
    public $trigger_settings;
    /**
     * @var
     */
    public $import_scheme;
    /**
     * @var
     */
    public $trigger_extended;
    /**
     * @var
     */
    public $trigger_range;
    /**
     * @var
     */
    public $service_params;
    /**
     * @var
     */
    public static $blueprint_to_item;
    /**
     * @var
     */
    public static $service_params_static;

    /**
     *
     */
    const IMPORT_STACK_SIZE = 1000;
    /**
     *
     */
    const CSV_LINES_OFFSET = 2;

    /**
     *
     */
    const UPDATE_MODES = [
		'soft',
		'hard'
	];

    /**
     *
     */
    const ADDITIONAL_PARAMS_TO_CSV = [
		'vin' => 'cf_vin_title',
		'year' => 'cf_year_title',
		'dealer_id' => 'cf_dealerid_title',
		'model_code' => 'cf_oem_code_title',
		'ext_color_code' => 'cf_ext_color_oem_code',
		'int_color_code' => 'cf_int_color_oem_code'
	];

    /**
     * ImportBalancer constructor.
     *
     * @param string $dealer_group
     */public function __construct(string $dealer_group)
	{
		$this->trigger = $dealer_group;
	}

    /**
     * @param array $params
     *
     * @return string
     */public static function getImportLog(array $params = []){
		if(empty($params['extension'])){
			$params['extension'] = "txt";
		}
		if(empty($params['name'])){
			$params['name'] = date("m.d.Y");
		}
		if(empty($params['path'])){
			$params['path'] = '';
		}

		switch ($params['path']){
			case 's3_log':
				$path = \Yii::getAlias('@s3_log') . '/';
				break;
			case 'log':
				$path = \Yii::getAlias('@log') . '/backend/dealerinventory/';
				break;
			default:
				$path = '';
				break;
		}

		return $path . $params['name'] . '.' . $params['extension'];
	}

    /**
     * @param $name
     * @param $key
     *
     * @return mixed
     */public static function rules($name, $key){
		$rules = [
			'import' => [
				0=>'string',
				1=>'integer',
				2=>'integer'
			]
		];
		return $rules[$name][$key];
	}

    /**
     * @return bool
     */
    public static function updateModeHard(){
		return ImportBalancer::$service_params_static['update_mode'] === 'hard';
	}

    /**
     * @return bool
     */
    public static function deleteLinkedIncentives(){
		return ImportBalancer::$service_params_static['delete_linked_incentives'] === true;
	}

    /**
     * @param array $params
     */
    public function definePointImportRange(array $params){
		$this->trigger_range = false;

		//If we have no start or end params - its full
		$range = [
			'start' => 'min',
			'end' => 'max'
		];

		try {
			if(!empty($params['groups'])){
				if(!empty($params['groups'][$this->trigger]['start'])){
					$range['start'] = $params['groups'][$this->trigger]['start'];
				}
				if(!empty($params['groups'][$this->trigger]['end'])){
					$range['end'] = $params['groups'][$this->trigger]['end'];
				}

				if(is_numeric($range['start'])
					&& is_numeric($range['end'])
					&& $range['start'] > $range['end']){
					throw new Exception('End value cant be more then start value.');

				}
			}
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}

		$this->trigger_range = $range;
	}

    /**
     *
     */
    public static function S3LogTransfer(){
		if(YII_ENV_PROD){
			$file_from = self::getImportLog(['path' => 'log']);
			$file_to = self::getImportLog(['path'=>'s3_log']);
			if(file_exists($file_from)){
				rename($file_from, $file_to);
			}
		}
	}

    /**
     * @param string $state
     *
     * @return false|int|string
     */public static function trackImportState(string $state = ''){
		$file = \Yii::getAlias('@dealerinventory_csv') .'/service/'. 'import_'.date('m-d').'.txt';
		switch ($state){
			case 'start':
				if(file_exists($file)){
					unlink($file);
				}

				$time = time();
				file_put_contents($file, $time);
				break;
			case 'line_finished':
				$time = time();
				file_put_contents($file, $time);
				break;
			case 'end':
				if(file_exists($file)){
					unlink($file);
				}
				break;
			default:
				if(file_exists($file)){
					$time = file_get_contents($file);
					return $time;
				}
				break;
		}
	}

    /**
     * @param int $current_line
     *
     * @return bool
     */public function lineInRange(int $current_line){
		if($this->trigger_range['start'] === 'min' && $this->trigger_range['end'] === 'max'){
			return true;
		}
		if(is_integer($this->trigger_range['start']) && $this->trigger_range['end'] === 'max'){
			if($current_line < $this->trigger_range['start']){
				return false;
			}
		}
		if(is_integer($this->trigger_range['start']) && is_integer($this->trigger_range['end'])){
			if($current_line < $this->trigger_range['start'] || $current_line > $this->trigger_range['end']){
				return false;
			}
		}
		return true;
	}

    /**
     * @return array
     */
    public function fillExtendedSettings(){
		$extended_fields = [];

		$extended_config = $this->extendImport();
		if(!empty($extended_config[$this->trigger]['validation']['common_optional_fields_db'])){
			foreach ($extended_config[$this->trigger]['validation']['common_optional_fields_db'] as $field => $val){
				$this->trigger_extended[$field] = $val;
				$extended_fields[] = $field;
			}
		}

		return $extended_fields;
	}

    /**
     * @param string $service_params
     *
     * @return array
     */public static function serviceParamsParser(string $service_params){
		$params = [];
		$params['hide_failed_validation'] = false;
		$params['update_mode'] = self::UPDATE_MODES[0]; //By default update mode is soft
		$params['delete_linked_incentives'] = false;
		try{
			if(!empty($service_params) && $service_params[0] === '[' && $service_params[strlen($service_params)-1] === ']'){
				//Params given in [] as array separated by , and key:val by :
				$service_params = str_replace('[','',$service_params);
				$service_params = str_replace(']','',$service_params);
				$data_array = explode(',',$service_params);
				foreach ($data_array as $data_row){
					$key_val = explode(':',$data_row);
					if($key_val[1] === "true"){
						$key_val[1] = true;
					}elseif($key_val[1] === "false"){
						$key_val[1] = false;
					}
					$params[$key_val[0]] = $key_val[1];
				}

				//Additinal params check
				if(!empty($params['update_mode'])){
					if(!in_array($params['update_mode'],self::UPDATE_MODES)){
						//Vin not correct
						throw new Exception('update_mode invalid value. Allowed values are '.implode(',',self::UPDATE_MODES));
					}
				}
				if(!empty($params['hide_failed_validation'])){
					if(!is_bool($params['hide_failed_validation'])){
						//Vin not correct
						throw new Exception('hide_failed_validation invalid value. Allowed values are '.implode(',',['true','false']));
					}
				}
			}
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}
		return $params;
	}

    /**
     * @param string $additional_params
     *
     * @return array
     */public static function additionalParamsParser(string $additional_params){
		$params = [];
		try{
			if (!empty($additional_params) && $additional_params[0] === '[' && $additional_params[strlen($additional_params)-1] === ']'){
		        //Params given in [] as array separated by , and key:val by :
				$additional_params = str_replace('[','',$additional_params);
				$additional_params = str_replace(']','',$additional_params);
				$data_array = explode(',',$additional_params);
				foreach ($data_array as $data_row){
					$key_val = explode(':',$data_row);
					if(!array_key_exists($key_val[0], self::ADDITIONAL_PARAMS_TO_CSV)){
						throw new Exception('Unknown param '.$key_val[0]. ' given');
					}
					$key_val[1] === "true" || $key_val[1] === "false" ? $key_val[1] = (bool)$key_val[1] : null;
					$params[$key_val[0]] = $key_val[1];
				}

				//Additinal params check
				if(!empty($params['vin'])){
					if(!(strlen($params['vin']) === 17 && !is_numeric($params['vin']))){
						//Vin not correct
						throw new Exception('Vin should be 17 characters long. You gave a vin '.$params['vin'].' which is '.strlen($params['vin']).' characters long');
					}
				}

				if(!empty($params['year'])){
					if(!(strlen($params['year']) === 4 && is_numeric($params['year']) && $params['year'] >= 2000 && $params['year'] <= (date('Y')+2))){
						//Year not correct
						throw new Exception('Year should be 4 characters long number between 2000 and '.(date('Y')+2).'.');
					}
				}

				if(!empty($params['dealer_id'])){
					if(!(is_numeric($params['dealer_id']))){
						//Year not correct
						throw new Exception('Dealer id should be an integer number.');
					}
				}
			}
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}
		return $params;
	}

    /**
     * @param array $additional_params
     * @param \modules\import\CsvItem $csvItem
     *
     * @return bool
     */public function passedByAdditionalParams(array $additional_params, CsvItem $csvItem){
		$need_matches_count = count($additional_params);
		$matches_found = 0;

		if(!empty($additional_params)){
			foreach (self::ADDITIONAL_PARAMS_TO_CSV as $key => $value){
				if(!empty($additional_params[$key])){
					if($additional_params[$key] === $csvItem->$value){
						$matches_found++;
					}
				}
			}
		}

		if($need_matches_count === $matches_found){
			return true;
		} else {
			return false;
		}
	}

    /**
     * @param $line_params
     *
     * @return array
     */public function validation($line_params){
		$extended_fields = $line_params['extended_fields'];

		Ibolit::init();
		Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());


		$csv_line = [$line_params['line_number'] => $line_params['csv_row']];
		$csv_line = $this->verticalValidation($csv_line, $extended_fields);
		if($this->checkout()){
			$csv_line = $this->horizontalValidation($csv_line);

			if($this->checkout()){
				$csv_line = $this->conditionTypeValidation($csv_line);
			}
		}

		Ibolit::signAnamnez();
		return $csv_line;
	}

    /**
     * @param \modules\import\CsvItem $csvItem
     */
    public function import(CsvItem $csvItem){
		$line_started = F3deshaHelpers::microtime_float();
		////////THE BODY OF CSV LINE IMPORT///////////////////////////

		//Each item import is based on stage-layered structure config
			//Stage1
				//Layer1
				//Layer2
				//Layer...
			//Stage2
				//Layer1
				//Layer...

		//Stage level has logic on start (preStage) and on end (postStage) of each stage
		//After preStage finished Layer level begins and executes foreach layer of stage
		//After all layers ran postStage executes and stage cycle ends

		//All import is tracked by Ibolit. If some layer or stage will have fail diagnosis - on end of layer
		//that has such an error checkout() method will exit layer level and igniteImportFinishTransaction() will rollback
		//all the queries linked with ignite item

		//Import config level
		$this->importBuilder($csvItem);

		//Ignite Item level

		$igniteItem = false;
		self::$service_params_static = null;
		self::$service_params_static = $this->service_params;

		//Stage level
		foreach ($this->importBuilderConfig as $import_stage => $layers_stack){
			$igniteItem = $this->preStage($import_stage, $igniteItem, $layers_stack['staging_options']);

			//Layer level
			foreach ($layers_stack['layers'] as $layer_name=>$layer_config){
				$class = $layer_config['class'];
				$igniteItem = $class::$layer_name($igniteItem, $csvItem);

				//Check the status of layer. If error cancel the import of line
				if(!$this->checkout()){
					break 2;
				}
			}
			/////Layer level
			$igniteItem = $this->postStage($import_stage, $igniteItem, $layers_stack['staging_options']);
			//Stage level
		}

		Ibolit::talkVerdict([
			'file' => $csvItem->file,
			'line_number' => $csvItem->line_number,
			'import_group' => $csvItem->import_group,
			'vin' => $csvItem->cf_vin_title,
			'import_time' => round(F3deshaHelpers::microtime_float() - $line_started, 2)
		]);
		////////THE BODY OF CSV LINE IMPORT//

		//End ignite item transaction
		if(!$this->checkout()){
			self::influenceTransaction($igniteItem, 'igniteItemTransaction');
		}
		self::endTransaction($igniteItem, ['igniteItemTransaction']);
		//End ignite item transaction

		//Cleaning section. At the end of line cycle lets manually launch garbage collection clean
		unset($igniteItem);
		unset($csvItem);
		unset($this->importBuilderConfig);
		unset($class);
		unset($import_stage);
		unset($layer_name);
		unset($layers_stack);
		unset($line_started);
		unset($layer_config);
	}


    /**
     * @param array $line_params
     */
    public function processLine(array $line_params){
		//1. Form clean csv line with only minimal needed data
		//2. Get the CSV Item object from clean csv line
		//3. Start the import

		//First level of filtering - Range filtering
		//Second level of filtering - mutational Validation
		//If no errors in Ibolit - Lets start import. Else make verdict of error
		//Third level of filtering - Additional params filtering

		if($this->lineInRange($line_params['line_number'])){
			$clean_csv_line = $this->validation($line_params);
			if($this->checkout()){
				$csvItem = new CsvItem($clean_csv_line, $this, $line_params['line_number']);
				if($this->passedByAdditionalParams($line_params['additional_params'], $csvItem)){
					$this->import($csvItem);
				}
				unset($csvItem);
			} else {
				if(!$this->hideFailedValidation()){
					Ibolit::talkVerdict([
						'file' => $this->trigger_settings->csv_path,
						'line_number' => $line_params['line_number'],
						'import_group' => $this->trigger_settings->import_group,
						'vin' => $clean_csv_line[$line_params['line_number']]['cf_vin_title']
					]);
				}
			}
		}
	}

    /**
     * @return mixed
     */
    public function hideFailedValidation(){
		return $this->service_params['hide_failed_validation'];
	}

    /**
     *
     */
    public function fillTriggerSettings(){
		//Start of the point import
		//Define the settings for current trigger import
		$trigger_settings = DealerinventoryDealerImportSettings::getByImportGroup($this->trigger);
		$this->trigger_settings = $trigger_settings[0];
	}

    /**
     * @param $path
     *
     * @return int
     */public function countAllElementsInCsv($path){
		$c =0;
		$fp = fopen($path,"r");
		if($fp){
			while(!feof($fp)){
				$content = fgets($fp);
				if($content)    $c++;
			}
		}
		fclose($fp);
		return $c;
	}

    /**
     * @param array $service_params
     */
    public function installServiceParams(array $service_params){
		$this->service_params = $service_params;
	}

    /**
     * @param array $params
     * @param array $additional_params
     * @param array $service_params
     */
    public function runPointImport(array $params, array $additional_params, array $service_params){
		try {
			$global_counter = 0;
			$this->fillTriggerSettings();
			$extended_fields = $this->fillExtendedSettings();
			//Define the range of current trigger import
			$this->installServiceParams($service_params);
			$this->definePointImportRange($params);
			$path_to_csv = \Yii::getAlias('@dealerinventory_csv') .'/'. $this->trigger_settings->csv_path;
			if(file_exists($path_to_csv)){
				$file_size = filesize($path_to_csv);
				if($file_size > 0){
					$all_lines_count = $this->countAllElementsInCsv($path_to_csv);
					Ibolit::report(2, [
						'dealer_group' => $this->trigger,
						'file_path' => $path_to_csv,
						'total_lines_count' => $all_lines_count
					]);

					$importer = new CsvImporter($path_to_csv,true, ',');

					//Lets get our data partially to prevent memory overflow
					while($data = $importer->get(self::IMPORT_STACK_SIZE))
					{
						foreach ($data as $old_iterator=>$line){
							//Lets find line number key
							$line_number = $old_iterator + $global_counter + self::CSV_LINES_OFFSET;

							$this->processLine([
								'line_number' => $line_number,
								'csv_row' => $line,
								'extended_fields' => $extended_fields,
								'additional_params' => $additional_params
							]);
							ImportBalancer::trackImportState('line_finished');
						}
						unset($data);
						$global_counter += self::IMPORT_STACK_SIZE;
					}

					//End of the point import
					$this->postImportTrigger($additional_params);
					unset($importer);
				} else {
					//File 0kb report
					Ibolit::report(4,[
						'dealer_group' => $this->trigger,
						'file_path'=>$path_to_csv
					]);
				}
			} else {
				//File not exists report
				Ibolit::report(3,[
					'dealer_group' => $this->trigger,
					'file_path'=>$path_to_csv
				]);
			}
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}
	}

    /**
     * @param $e
     */
    public static function formExceptionOutput($e){
		echo __CLASS__.' exception thrown on line '.__LINE__.' of file '.__FILE__.': ',  $e->getMessage(), "\n";
		exit();
	}

    /**
     * @param array $additional_params
     */
    public function postImportTrigger(array $additional_params){
		if($this->trigger_range['start'] === 'min' && empty($additional_params)) {
			DealerinventoryActivator::resetIgniteItemsByParams(
				['and',
					['=','import_group',$this->trigger],
					['<','updated_at',strtotime(date('m', time()) . '/' . date('d', time()) . '/' . date('Y', time()))]
				]);

		}
	}

    /**
     * @return bool
     */
    public function checkout(){
		if(Ibolit::hasNoErrors()){
			//Cancel the import cause error was caused
			return true;
		}
		return false;
	}

    /**
     * @param string $import_stage
     * @param $igniteItem
     * @param array $staging_options
     *
     * @return array|\modules\dealerinventory\models\backend\Dealerinventory|\yii\db\ActiveRecord|null
     */public function preStage(string $import_stage, $igniteItem, array $staging_options = []) {
		switch ($import_stage) {
			case 'afterStage':
				Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
				//Cause we need to have item with updated incentives links lets take it from db again
				$transactionStack = $igniteItem->transactionsStack;
				$igniteItem = Dealerinventory::find()->where(['id'=>$igniteItem->id])->one();
				$igniteItem->transactionsStack = $transactionStack;
				Ibolit::add(['PreStage','Getting ignite item'],['Getting ignite item for activation',1]);
				Ibolit::signAnamnez();
				break;
		}
		return $igniteItem;
	}

    /**
     * @param string $import_stage
     * @param $igniteItem
     * @param array $staging_options
     *
     * @return mixed
     */public function postStage(string $import_stage, $igniteItem, array $staging_options = []){
		switch ($import_stage) {
			case 'compilingStage':
				Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
				//At the end of presave stage fill the data of $igniteItem with trigger data
				// and save ignite item at the end of presave stage
				if(!empty($igniteItem)){
					if(!empty($staging_options['save_scenario'])){
						$igniteItem->setScenario($staging_options['save_scenario']);
					}
					if($igniteItem->validate()){
						$igniteItem->save();
						Ibolit::add(['PostStage','Saving Ignite Item'],['Ignite Item successfully saved',1]);
					} else {
						var_dump($igniteItem->errors);
					}
				}
				Ibolit::signAnamnez();
				break;
			case 'linkingStage':
				Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
				//At the end of presave stage fill the data of $igniteItem with trigger data
				// and save ignite item at the end of presave stage
				if(!empty($igniteItem)){
					if(!empty($staging_options['save_scenario'])){
						$igniteItem->setScenario($staging_options['save_scenario']);
					}
					if($igniteItem->validate()){
						$igniteItem->save();
						Ibolit::add(['PostStage','Saving Ignite Item'],['Ignite Item successfully saved',1]);
					} else {
						var_dump($igniteItem->errors);
					}
				}
				Ibolit::signAnamnez();
				break;
			case 'afterStage':
				Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
				//At the end of presave stage fill the data of $igniteItem with trigger data
				// and save ignite item at the end of presave stage
				if(!empty($igniteItem)){
					if(!empty($staging_options['save_scenario'])){
						$igniteItem->setScenario($staging_options['save_scenario']);
					}
					if($igniteItem->validate()){
						$igniteItem->save();
						Ibolit::add(['PostStage','Saving Ignite Item'],['Ignite Item successfully saved',1]);
					} else {
						var_dump($igniteItem->errors);
					}
				}
				Ibolit::signAnamnez();
				break;
		}
		return $igniteItem;
	}

    /**
     * @param $igniteItem
     * @param string $transactionName
     *
     * @return mixed
     */public static function startTransaction($igniteItem, string $transactionName){
		Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
		$connection = \Yii::$app->db;
		$igniteItem->transactionsStack[$transactionName] = [
			'transactionBody' => $connection->beginTransaction(Transaction::READ_UNCOMMITTED),
		];
		Ibolit::add(['transaction_action','Transaction'],['Begin transaction',1,[
			'transaction_name'=>$transactionName
		]]);
		Ibolit::signAnamnez();
		return $igniteItem;
	}

    /**
     * @param $igniteItem
     * @param string $transactionName
     */
    public static function influenceTransaction($igniteItem, string $transactionName){
		Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
		$connection = \Yii::$app->db;
		$igniteItem->transactionsStack[$transactionName]['rollback'] = true;
		Ibolit::add(['transaction_action','Transaction'],['Transaction rollback influence breakpoint',3,['transaction_name'=>$transactionName,'transaction_action'=>'influence']]);
		Ibolit::signAnamnez();
	}

    /**
     * @param $igniteItem
     * @param array $transaction_names
     */
    public static function endTransaction($igniteItem, array $transaction_names){
		Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
		foreach ($transaction_names as $t_name){
			if(!empty($igniteItem->transactionsStack[$t_name]) && !empty($igniteItem->transactionsStack[$t_name]['transactionBody'])){
				if(!empty($igniteItem->transactionsStack[$t_name]['rollback'])){
					if($igniteItem->transactionsStack[$t_name]['rollback'] === true){
						$igniteItem->transactionsStack[$t_name]['transactionBody']->rollback();
						Ibolit::add(['transaction_action','Transaction'],['Transaction rollbacked',2,['transaction_name'=>$t_name,'transaction_action'=>'rollback']]);
					}
				} else {
					$igniteItem->transactionsStack[$t_name]['transactionBody']->commit();
					Ibolit::add(['transaction_action','Transaction'],['Transaction commited',1,['transaction_name'=>$t_name,'transaction_action'=>'commit']]);
				}
			}
		}
		Ibolit::signAnamnez();
	}


    /**
     * @param array $config_part
     */
    public function buildCofig(array $config_part){
		$required_elements = [
			'class','priority'
		];
		try{
			foreach ($config_part as $stage){
				if(!empty($stage['layers'])){
					foreach ($stage['layers'] as $layer){
						foreach ($required_elements as $element){
							if(!array_key_exists($element, $layer)){
								throw new Exception($element.' not found in config.');
							}
						}
					}
				}
			}

			$this->importBuilderConfig[] = $config_part;
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}
	}

    /**
     *
     */
    public function buildInitPart(){
		//Resetting config on begin
		$this->importBuilderConfig = [];

		$this->buildCofig([
			'compilingStage' => [
				'layers' => [
					'instanceLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 1
					],
					'blueprintLayer' => [
						'class' => 'modules\chromedata\models\ChromedataVehicle',
						'priority' => 2
					],
				],
				'staging_options' => [

				]
			],
		]);
	}

    /**
     *
     */
    public function configBasedOnUpdateMode(){
		switch ($this->service_params['update_mode']){
			case 'hard':
				$this->configPartForCreateItem();
				break;
			default:
				$this->buildCofig(['compilingStage'=> [
					'staging_options' => [
						'save_scenario' => 'import-update'
					]
				]
				]);
				$this->buildCofig(['linkingStage'=> [
					'staging_options' => [
						'save_scenario' => 'import-update'
					]
				]
				]);
				break;
		}
	}

    /**
     *
     */
    public function configPartForCreateItem(){
		$this->buildCofig([
			'compilingStage' => [
				'layers' => [
					'carLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 3
					],
					'pricingRulesLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Pricing',
						'priority' => 4
					],
				],
				'staging_options' => [
					'save_scenario' => 'admin-create'
				]

			],
			'linkingStage' => [
				'layers' => [
					'optionsLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 5
					],
				],
				'staging_options' => [
					'save_scenario' => 'admin-create'
				]
			]
		]);
	}

    /**
     * @param \modules\import\CsvItem $csvItem
     *
     * @return string
     */public function importMode(CsvItem $csvItem){
		if($ignite_item = Dealerinventory::find()->where(['vin'=>$csvItem->cf_vin_title])->limit(1)->all()){
			$mode = 'update';
		} else {
			$mode = 'create';
		}
		return $mode;
	}

    /**
     *
     */
    public function dealerExtender(){
		$extended_config = $this->extendImport();
		if(!empty($extended_config[$this->trigger]['additional_logic_if_item_not_exists'])){
			$this->buildCofig($extended_config[$this->trigger]['additional_logic_if_item_not_exists']);
		}
		if(!empty($extended_config[$this->trigger]['additional_logic_if_item_exists'])){
			$this->buildCofig($extended_config[$this->trigger]['additional_logic_if_item_exists']);
		}
	}

    /**
     * @param \modules\import\CsvItem $csvItem
     */
    public function buildPartBasedOnImportMode(CsvItem $csvItem){
		switch ($this->importMode($csvItem)){
			case 'create':
				//Create - build create part based on create mode
				$this->configPartForCreateItem();
				break;
			case 'update':
				//Update - build update part based on update mode
				$this->configBasedOnUpdateMode();
				break;
		}

		$this->dealerExtender();

	}

    /**
     * @param \modules\import\CsvItem $csvItem
     */
    public function buildPartBasedOnIgniteItemCarCondition(CsvItem $csvItem){
		$condition_type = 0;
		if(!empty($csvItem->cf_type)){
			$condition_type = Dealerinventory::getConditionStatusByField($csvItem->cf_type);
		}
		if($condition_type === 0 || $condition_type === 2){
			$this->buildCofig([
				'linkingStage' => [
					'layers' => [
						'incentivesLayer' => [
							'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
							'priority' => 6
						],
					],
				]
			]);
		}
	}

    /**
     *
     */
    public function buildEndPart(){
		$this->buildCofig([
			'compilingStage' => [
				'layers' => [
					'bellyLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 4
					],
					'colorLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 4
					],
					'ownerLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 5
					],
					'itemPricingLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 6
					],
				],
			],
			'linkingStage' => [
				'layers' => [
					'picturesLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 5
					],
					'standardsLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 6
					],
				],
				'staging_options' => [

				]
			],
			'afterStage' => [
				'layers' => [
					'activationLayer' => [
						'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
						'priority' => 7
					],
				],
				'staging_options' => [
					'save_scenario' => 'activation-update'
				]
			]
		]);
	}

    /**
     *
     */
    public function buildPartForExtenedImport(){
		$extended_config = $this->extendImport();
		if(!empty($extended_config[$this->trigger]['additional_logic_common'])){
			$this->buildCofig($extended_config[$this->trigger]['additional_logic_common']);
		}
	}

    /**
     * @param \modules\import\CsvItem $csvItem
     */
    public function importBuilder(CsvItem $csvItem){
		//The task of blueprint layer is to fill db with chrome vehicles and to get chrome id

		//The task of itemLayer is to create new clean ignite item instance or to get existing
		//IF item not exists - create new instance
		//If item exists - get it from db

		//The task of fillerLayer is to fill common data on ignite item creation. Its one time procedure
		//If item not exists - lets take our instance from itemLayer and fill it with data

		//The task of color layer is to get colors for ignite item on its creation. Its one time procedure
		//If item not exists - lets take our instance from itemLayer and fill it with data

		//The task of options layer is to activate options for ignite item on its creation. Its one time procedure
		//If item not exists - lets take our instance from itemLayer and fill it with data

		//The task of pictures layer is to create pictures for ignite item on its creation. Its one time procedure
		//If item not exists - lets take our instance from itemLayer and fill it with data

		//The task of belly layer is to get ignite belly data and to update it on each item process (update or create)
		//Get item from itemLayer and fill it with data

		//The task of incentives layer is to create or update incentives. Its regular process
		//Get item from itemLayer and fill it with data

		//The task of activation layer is to activate or deactivate ignite item. Its regular process.
		//Get item from itemLayer and fill it with data

		////////////////SCHEME
		///
		/// [
		//			'STAGE_NAME' => [
		//				'layers' => [
		//					'layerName' => [
		//						'class' => 'classNameSpace',
		//						'priority' => 1
		//					],
		//					...
		//				],
		//				'staging_options' => [
		//
		//				]
		//			],
		//		]
		///
		/// //////////////////

		$this->buildInitPart(); //Common begin
		$this->buildPartBasedOnImportMode($csvItem);
		$this->buildPartBasedOnIgniteItemCarCondition($csvItem);
		$this->buildPartForExtenedImport();
		$this->buildEndPart(); //Common end

		//Compile config based on previous actions. This always should be at the end
		$this->generateImportConfig();
	}

    /**
     *
     */
    public function generateImportConfig(){
		$merged_set = [];
		foreach($this->importBuilderConfig as $i=>$set){
			$merged_set = array_merge_recursive($merged_set, $set);
		}

		//Make a sort based on priority
		foreach ($merged_set as $index => $set){
			if(!empty($set['layers'])){
				uasort($merged_set[$index]['layers'], function ($a, $b) {
					return $a["priority"] > $b["priority"];
				});
			}
		}
		$this->importBuilderConfig = $merged_set;
	}

    /**
     * @param $data
     * @param $global_counter
     *
     * @return array
     */public function inputValidation($data, $global_counter){
		//Get extended data
		$extended_config = $this->extendImport();
		$extended_fields = [];
		if(!empty($extended_config[$this->trigger]['validation']['common_optional_fields_db'])){
			foreach ($extended_config[$this->trigger]['validation']['common_optional_fields_db'] as $field => $value){
				$extended_fields[] = $field;
			}
		}

		$clean_data_part = [];
		foreach ($data as $i=>$row){
			$key = $i + $global_counter + self::CSV_LINES_OFFSET;
			if(!$this->notInRange($key)){
				Ibolit::init();
				Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());

				unset($data[$i]);
				$data[$key] = $row;

				$csv_line = [$key => $row];
				$csv_line = $this->verticalValidation($csv_line, $extended_fields);
				$csv_line = $this->horizontalValidation($csv_line);
				$csv_line = $this->conditionTypeValidation($csv_line);

				Ibolit::signAnamnez();
				if($this->checkout()){
					//If no errors in Ibolit - add line to clean validated array. Else make verdict of error
					$clean_data_part[$key] = $csv_line[$key];
				} else {
					Ibolit::talkVerdict([
						'file' => $this->trigger_settings->csv_path,
						'line_number' => $key,
						'import_group' => $this->trigger_settings->import_group,
						'vin' => $csv_line[$key]['cf_vin_title']
					]);
				}
			}
		}

		return $clean_data_part;
	}

    /**
     * @param array $groups
     *
     * @return bool|mixed
     */public static function isInvalidDealerGroups(array $groups){
		$IMPORT_CONFIG = DealerinventoryDealerImportSettings::getConfigForIgniteImport();

		foreach ($groups as $group){
			if(!array_key_exists($group, $IMPORT_CONFIG)){
				return $group;
			}
		}
		return false;
	}

    /**
     * @return array
     */
    public function extendImport(){
		$extended_config = [];
		//Custom extendable behavior config of import goes here
		//It will extend your import with additional cases
		switch ($this->trigger){
			case 'Smithtown_Toyota':
				$extended_config[$this->trigger] = [];
				$extended_config[$this->trigger]['validation']['common_optional_fields_db'] = [
					'cf_color_codes' => 'exteriorcolorcode'
				];
				$extended_config[$this->trigger]['additional_logic_if_item_not_exists'] = [
					'compilingStage' => [
						'layers' => [
							'smithtowncolorsLayer' => [
								'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
								'priority' => 5
							]
						]
					]
				];
				break;
			case 'NEW_DEALER_HERE':
				$extended_config[$this->trigger] = [];
				$extended_config[$this->trigger]['additional_logic_if_item_not_exists'] = [
					'compilingStage' => [
						'layers' => [
							'stockloanerLayer' => [
								'class' => 'modules\dealerinventory\models\backend\Dealerinventory',
								'priority' => 5
							]
						]
					]
				];
				break;
		}
		return $extended_config;
	}

    /**
     * @param array $csv_line
     * @param array $extended_fields
     *
     * @return array
     */public function verticalValidation(array $csv_line, array $extended_fields = []){
		$vertically_validated_data = [];

		//we have required and optional fields
		$common_required_fields_db = [
			'cf_vin_title',
			'cf_dealerid_title',
			'cf_oem_code_title',
			'cf_msrp_title',
			'cf_invoice_title',
			'cf_year_title',
		];

		$common_optional_fields_db = [
			'cf_ext_color_oem_code',
			'cf_int_color_oem_code',
			'cf_ext_color_name_title',
			'cf_int_color_name_title',
			'cf_option_oem_codes',
			'cf_type',
			'cf_dealer_stock_number',
			'cf_miles',
			'cf_trim',
			'cf_internet_price',
			'cf_certified',
			'cf_transmission',
			'cf_transmission_gears',
			'cf_transmission_full',
			'cf_driven_wheels'
		];

		if(!empty($extended_fields)){
			$common_optional_fields_db = array_merge($common_optional_fields_db, $extended_fields);
		}

		$common_required_fields_csv = [];
		$common_optional_fields_csv = [];


		//Lets form vertical requirements array
		try{
			foreach ($common_required_fields_db as $common_required_field_db){
					$common_required_fields_csv[$common_required_field_db] = $this->trigger_settings->$common_required_field_db;
			}

			foreach ($common_optional_fields_db as $common_optional_field_db){
				if(!empty($this->trigger_settings->$common_optional_field_db)){
					$common_optional_fields_csv[$common_optional_field_db] = $this->trigger_settings->$common_optional_field_db;
				}
				if(!empty($this->trigger_extended[$common_optional_field_db])){
					$common_optional_fields_csv[$common_optional_field_db] = $this->trigger_extended[$common_optional_field_db];
				}
			}

			$key = array_keys($csv_line)[0];

			$csv_line = $csv_line[$key];
			//foreach ($fixed_size_array as $key=>$csv_line){
				foreach ($common_required_fields_csv as $dbkey=>$common_required_field_csv){
					if(array_key_exists($common_required_field_csv,$csv_line)){
						$vertically_validated_data[$key][$dbkey] = $csv_line[$common_required_field_csv];
					} else {
						throw new Exception('Required field "'.$common_required_field_csv.'" not found in CSV file.');
					}
				}

				foreach ($common_optional_fields_csv as $dbkey=>$common_optional_field_csv){
					if(array_key_exists($common_optional_field_csv,$csv_line)){
						$vertically_validated_data[$key][$dbkey] = $csv_line[$common_optional_field_csv];
					} else {
						throw new Exception('Optional field "'.$common_optional_field_csv.'" not found in CSV file.');
					}
				}
			//}
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}

		return $vertically_validated_data;
	}

    /**
     * @param array $csv_line
     *
     * @return array
     */public function horizontalValidation(array $csv_line){
			$line_number = array_keys($csv_line)[0];
			$data_line = $csv_line[$line_number];
			//There are several types of horizontal checks we need to do: availability, type and length validation
			//foreach ($fixed_size_array as $dirty_line_number => $data_line){

			if($this->availabilityCheck($data_line, [
					'cf_vin_title',
					'cf_dealerid_title',
				]) &&
				$this->typeCheck($data_line, [
					['cf_dealerid_title','numeric'],
				]) &&
				$this->lengthCheck($data_line, [
					['cf_vin_title',17],
					['cf_year_title',4]
				])){
				//Data horizontally validated successfully
			} else {
				$this->reportValidationFailed(1, $line_number, $data_line);
			}

			//}

		return $csv_line;
	}

    /**
     * @param $validation_id
     * @param $line_number
     * @param $data_line
     */
    public function reportValidationFailed($validation_id, $line_number, $data_line){
			switch ($validation_id){
				case 1:
					//Horizontal data validation failed
					Ibolit::add(['ValidationLayer','Horizontal Validation'],[
						'Horizontal data validation failed',2,[
							'csv_line->cf_vin_title' => $data_line['cf_vin_title'],
							'csv_line->cf_dealerid_title' => $data_line['cf_dealerid_title'],
							'csv_line->cf_year_title' => $data_line['cf_year_title'],
						],'k5l4;6l343k5345k43hk5j34j34j5k3']);
					break;
				case 2:
						Ibolit::add(['ValidationLayer','Conditional Validation'],[
							'Conditional data validation failed. Used car has zero value of internet price.',2,[
								'csv_line->cf_internet_price' => $data_line['cf_internet_price'],
							],'hj56j354jkk4j3534kj54h63lj6l322']);
					break;
				case 3:
						Ibolit::add(['ValidationLayer','Conditional Validation'],[
							'Conditional data validation failed. New car has either zero value of invoice, or msrp, or invoice not available.',2,[
								'csv_line->cf_invoice_title' => $data_line['cf_invoice_title'],
								'csv_line->cf_msrp_title' => $data_line['cf_msrp_title'],
							],'k4j5hkj64l5k7k46k453l34k32;42n2']);
					break;
				case 4:
						Ibolit::add(['ValidationLayer','Conditional Validation'],[
							'Conditional data validation failed. New car has either zero value of invoice, or msrp',2,[
								'csv_line->cf_invoice_title' => $data_line['cf_invoice_title'],
								'csv_line->cf_msrp_title' => $data_line['cf_msrp_title'],
							],'lk5l3k45lk346lk34j645kl7l3;;2;2']);
					break;
				case 5:
					Ibolit::add(['ValidationLayer','Conditional Validation'],[
						'Conditional data validation failed. Loaner car has no loaner miles available',2,[
							'csv_line->cf_miles' => $data_line['cf_miles'],
							'csv_line->cf_type' => $data_line['cf_type'],
						],'jk54lnk5j435l34kn6kl43lk634ll33']);
					break;
				case 6:
					Ibolit::add(['ValidationLayer','Conditional Validation'],[
						'Conditional data validation failed. No Dealer found with such a dealer id',2,[
							'csv_line->cf_dealerid_title' => $data_line['cf_dealerid_title'],
						]]);
					break;
			}
	}

    /**
     * @param array $data_line
     * @param array $rules
     *
     * @return bool
     */public function availabilityCheck(array $data_line, array $rules){
		foreach ($rules as $rule){
			if(empty($data_line[$rule])){
				return false;
			}
		}
		return true;
	}

    /**
     * @param array $data_line
     * @param array $rules
     *
     * @return bool
     */public function typeCheck(array $data_line, array $rules){
		$checks_passed = 0;
		foreach ($rules as $rule){
			$property = $rule[0];

			if(in_array($rule[1],['numeric'])){
				if($rule[1] === 'numeric'){
					if(is_numeric($data_line[$property])){
						$checks_passed++;
					}
				}
			}
		}
		if($checks_passed === count($rules)){
			return true;
		}
		return false;
	}

    /**
     * @param array $data_line
     * @param array $rules
     *
     * @return bool
     */public function lengthCheck(array $data_line, array $rules){
		foreach ($rules as $rule){
			$property = $rule[0];
			if(strlen($data_line[$property]) !== $rule[1]){
				return false;
			}

		}
		return true;
	}

    /**
     * @param array $data_line
     * @param array $rules
     *
     * @return bool
     */public function nonZeroCheck(array $data_line, array $rules){
		foreach ($rules as $rule){
			if($data_line[$rule] === '0'){
				return false;
			}
			if(!is_numeric($data_line[$rule])){
				return false;
			}
		}
		return true;
	}

    /**
     * @param array $csv_line
     *
     * @return array
     */public function conditionTypeValidation(array $csv_line){
		$clean_data = [];
			$dirty_line_number = array_keys($csv_line)[0];
			$data_line = $csv_line[$dirty_line_number];
			//foreach ($fixed_size_array as $dirty_line_number => $data_line){
			if(array_key_exists('cf_type',$data_line)){
				if($this->availabilityCheck($data_line, [
					'cf_type',
				])){
					$condition_type = Dealerinventory::getConditionStatusByField($data_line['cf_type']);
					if($condition_type === 1){
						if($this->availabilityCheck($data_line, [
							'cf_internet_price',
						])){
							//validated
						} else {
							$this->reportValidationFailed(2, $dirty_line_number, $data_line);
						}
					}
					else if($condition_type === 0 || $condition_type === 2){
						$check_fields = ['cf_msrp_title','cf_invoice_title'];
						$availabitlity_fields = ['cf_invoice_title'];

						$dealer = User::findOne($data_line['cf_dealerid_title']);
						if(!empty($dealer)){
							$ig = $dealer->dealership->invoice_guesser;
							if ($ig == 1) {
								$availabitlity_fields = [];
								unset($check_fields[1]);
							}
							if($this->availabilityCheck($data_line, $availabitlity_fields) &&
								$this->nonZeroCheck($data_line, $check_fields)){
								if($condition_type === 2 ) {
									if($this->availabilityCheck($data_line, [
										'cf_miles',
									])) {
										//validated
									} else {
										$this->reportValidationFailed(5, $dirty_line_number, $data_line);
									}
								}
							} else {
								$this->reportValidationFailed(3, $dirty_line_number, $data_line);
							}
						} else {
							$this->reportValidationFailed(6, $dirty_line_number, $data_line);
						}
					}
				}
			} else {
				$dealer = User::findOne($data_line['cf_dealerid_title']);
				if(!empty($dealer)){
					$ig = $dealer->dealership->invoice_guesser;
					if ($ig == 1) {
						if($this->nonZeroCheck($data_line, [
							'cf_msrp_title'
						])){
							//validated
						} else {
							$this->reportValidationFailed(4, $dirty_line_number, $data_line);
						}
					} else {
						if($this->nonZeroCheck($data_line, [
							'cf_invoice_title',
							'cf_msrp_title'
						])){
							//validated
						} else {
							$this->reportValidationFailed(4, $dirty_line_number, $data_line);
						}
					}
				} else {
					$this->reportValidationFailed(6, $dirty_line_number, $data_line);
				}

			}
			//}

		return $csv_line;
	}

    /**
     * @param $params_string
     *
     * @return mixed
     */public static function commandParser($params_string){
		//Define import cases
		if(empty($params_string) || $params_string[0] === '*'){
			$params['import_type'] = 1;
		} else {
			$params['import_type'] = 2;
		}
		//Get the set of point
		if(!empty($params_string) && $params_string[0] === '*'){
			$params_string = ltrim($params_string,'*');
		}
		if(!empty($params_string)){
			$point_import_sets = explode('/',$params_string);
			if($params['import_type'] === 1){
				if (count($point_import_sets) > 2){
						//If we have more then 2 params group - delete rest - we don't need them in full import
						array_splice($point_import_sets, 2);

				}
			}
			foreach ($point_import_sets as $point_import_set){
				$point_import_set = str_replace('[','',$point_import_set);
				$point_import_set = str_replace(']','',$point_import_set);
				$point_import_set = explode(',',$point_import_set);
				try {
					if(!empty($point_import_set)){
						//Type verification
						if(empty($point_import_set[0])){
							throw new Exception('Param 0 should not be empty.');
						}
						if(!is_string($point_import_set[0]))
						{
							throw new Exception('Param 0 should be a '.self::rules('import', 0).' type.');
						}

						if(is_string($point_import_set)) {
							$point_import_set[0] = $point_import_set;
						}
						if(!empty($point_import_set[0])){
							$params['groups'][$point_import_set[0]] = [];
							if(!empty($point_import_set[1])){
								//Start from type verification
								if(!is_numeric($point_import_set[1])) {
									throw new Exception('Param 1 should be a '.self::rules('import', 1).' type.');
								}
								$params['groups'][$point_import_set[0]]['start'] = (int)$point_import_set[1];
							} elseif(empty($point_import_set[1]) && !empty($point_import_set[2])){
								throw new Exception('Param 1 shouldnt be empty or 0.');
							}
							if(!empty($point_import_set[2])){
								//End at type verification
								if(!is_numeric($point_import_set[2])) {
									throw new Exception('Param 2 should be a '.self::rules('import', 2).' type.');
								}
								$params['groups'][$point_import_set[0]]['end'] = (int)$point_import_set[2];
							}
						}
					}


				} catch (Exception $e){
					self::formExceptionOutput($e);
				}
			}
		}
		return $params;
	}

    /**
     * @param array $params
     *
     * @return array
     */public static function getPointImportStack(array $params){
		$IMPORT_CONFIG = DealerinventoryDealerImportSettings::getConfigForIgniteImport();
		$dealer_groups = [];
		try{
			//Full import this is always one or more point imports
			switch ($params['import_type']){
				case 1:
					//Full import
					//If we have no groups - get ALL
					if(empty($params['groups'])){
						foreach ($IMPORT_CONFIG as $group_name => $group){
							if($group['active'] === true){
								$dealer_groups[] = $group_name;
							}
						}
					}
					//If we have only one group - start from it and go till the end
					if(!empty($params['groups']) && count($params['groups']) === 1){
						$group_found = false;
						$start_from_dealergroup = array_keys($params['groups'])[0];
						if($returned_invalid_group = self::isInvalidDealerGroups([$start_from_dealergroup])){

						    var_dump($returned_invalid_group);
						    throw new Exception($start_from_dealergroup. ' dealer group not found in database.');
						}
						if($IMPORT_CONFIG[$start_from_dealergroup]['active'] === false){
							throw new Exception($start_from_dealergroup. ' dealer group is inactive. Please activate it to run import.');
						}

						foreach ($IMPORT_CONFIG as $group_name => $group){
							if($group_name === $start_from_dealergroup){
								$group_found = true;
							}
							if($group_found && $group['active'] === true){
								$dealer_groups[] = $group_name;
							}
						}
					}
					//If we have two groups
					//we have full import starting from first params import group and ending with second params import group
					if(!empty($params['groups']) && count($params['groups']) === 2){
						$group_found = false;
						$end_match = false;
						$start_from_dealergroup = array_keys($params['groups'])[0];
						if($returned_invalid_group = self::isInvalidDealerGroups([$start_from_dealergroup])){
							throw new Exception($start_from_dealergroup. ' dealer group not found in database.');
						}
						if($IMPORT_CONFIG[$start_from_dealergroup]['active'] === false){
							throw new Exception($start_from_dealergroup. ' dealer group is inactive. Please activate it to run import.');
						}

						$end_at_dealergroup = array_keys($params['groups'])[1];
						if($returned_invalid_group = self::isInvalidDealerGroups([$end_at_dealergroup])){
							throw new Exception($end_at_dealergroup. ' dealer group not found in database.');
						}
						if($IMPORT_CONFIG[$start_from_dealergroup]['active'] === false){
							throw new Exception($end_at_dealergroup. ' dealer group is inactive. Please activate it to run import.');
						}

						foreach ($IMPORT_CONFIG as $group_name => $group){
							if($group_name === $start_from_dealergroup){
								$group_found = true;
							}
							if($group_found && $group['active'] === true){
								$dealer_groups[] = $group_name;
							}
							if($group_name === $end_at_dealergroup && $group_found === true){
								$group_found = false;
								$end_match = true;
							}
						}
						if(!$end_match) {
							$dealer_groups = [];
							throw new Exception('Your end param goes before start param.');
						}
					}
					break;
				case 2:
					//Point import
					//get only the groups pointed in groups
					foreach ($params['groups'] as $group_name => $group){
						if(array_key_exists($group_name, $IMPORT_CONFIG)){
							if($IMPORT_CONFIG[$group_name]['active'] === true){
								$dealer_groups[] = $group_name;
							}
						} else {
							throw new Exception($group_name. ' dealer group not found in database.');
						}
					}
					break;
			}
		} catch (Exception $e){
			self::formExceptionOutput($e);
		}
		return $dealer_groups;
	}
}
