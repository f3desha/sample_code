<?php

namespace modules\import;

use common\components\F3deshaHelpers;
use yii\base\Exception;


/**
 * Class Ibolit
 * @package modules\import
 *
 * Ibolit is a diagnostic system for Ignite Import
 * On each car import you should init new Ibolit state with init() method
 * All the needed data stored in static $anamnez property
 * Ibolit forms anamnez from method calls. So to create your first anamnez item you need to call collectAnamnez()
 * After you did it, you are currently assigned to this anamnez group and you can add subdiagnosis to it
 * Anamnez part can have many groups(diagnosis) with many items(subdiagnosis) in each group
 * So to use add() method you need to define 1) group name 2) item name 3) report_content
 * After you ended with this anamnez item, you can close it using signAnamnez()
 */
class Ibolit
{
    /**
     * @var
     */
    private static $anamnez;
    /**
     * @var
     */
    private static $current_patient;
    /**
     * @var
     */
    private static $current_diagnosis;
    /**
     * @var string
     */
    private static $item_string = '';

    /**
     * @var
     */
    private static $output;
    /**
     * @var
     */
    public static $details_level;

    /**
     *
     */
    const IBOLIT_STATUS = 1;

    /**
     *
     */
    const HEALTH_SUCCESS = 'success';
    /**
     *
     */
    const HEALTH_FAIL = 'fail';
    /**
     *
     */
    const HEALTH_WARNING = 'warning';
    /**
     *
     */
    const HEALTH_SUCCESS_DEFAULT_MESSAGE = 'Successfully passed.';

    /**
     *
     */
    const CONSOLE_COLOR_TO_HTML = [
		'[32m' => '#00a65a',
		'[31m' => '#dd4b39',
		'[1;33m' => '#f39c12'
	];

	//--------------------------------------------------------------------

    /**
     * Ibolit constructor.
     */
    function __construct()
	{

	}
	//--------------------------------------------------------------------

    /**
     *
     */
    function __destruct()
	{

	}

    /**
     *
     */
    static function init(){
		if(self::IBOLIT_STATUS){
			self::$anamnez = false;
			self::$current_patient = false;
			self::$current_diagnosis = false;
			self::$item_string = '';
		}
	}

    /**
     * @param $output
     * @param $details_level
     */
    public static function setOutput($output,$details_level){
		self::$output = $output;
		self::$details_level = $details_level;
	}

    /**
     * @return bool
     */
    public static function hasNoErrors(){
		if(self::IBOLIT_STATUS){
			foreach (self::$anamnez as $group){
				foreach ($group as $diagnosis_group){
					if(!empty($diagnosis_group)){
						foreach ($diagnosis_group as $subdiagnosis)
							if(!self::isHealthy($subdiagnosis)){
								return false;
							}
					}
				}
			}
		}
		return true;
	}

    /**
     * @return int
     */
    public static function healthProgress(){
		if(self::IBOLIT_STATUS){
			$all_elements = 0;
			foreach (self::$anamnez as $group){
				foreach ($group as $diagnosis_group){
					if(!empty($diagnosis_group)){
						foreach ($diagnosis_group as $subdiagnosis)
							$all_elements++;
					}
				}
			}
			$current_element = 0;
			foreach (self::$anamnez as $group){
				foreach ($group as $diagnosis_group){
					if(!empty($diagnosis_group)){
						foreach ($diagnosis_group as $subdiagnosis) {
							if(!self::isHealthy($subdiagnosis)){
								break 3;
							}
							$current_element++;
						}
					}
				}
			}
		}
		return $all_elements !== 0 ? (int)($current_element / $all_elements * 100) : 100;
	}

    /**
     * @param $item
     *
     * @return bool
     */public static function isHealthy($item){
		return $item['health_status'] === self::HEALTH_SUCCESS
			|| $item['health_status'] === self::HEALTH_WARNING;
	}

    /**
     * @param $call_method
     */
    public static function collectAnamnez($call_method){
		if(self::IBOLIT_STATUS){
			//Consists of call method and uniqe id to prevent overriding
			self::$anamnez[$call_method] = [];
			self::$current_patient = $call_method;
		}
	}

    /**
     * @param int $code
     *
     * @return bool|mixed
     */public static function getHealthByCode(int $code){
		$status_codes = [
			1 => self::HEALTH_SUCCESS,
			2 => self::HEALTH_FAIL,
			3 => self::HEALTH_WARNING
		];
		return array_key_exists($code, $status_codes) ? $status_codes[$code] : false;
	}

    /**
     * @param array $case_diagnosis
     * @param $track_id
     *
     * @return array
     */public static function generateResponseBody(array $case_diagnosis, $track_id){
		if(empty($case_diagnosis[1])){
			$case_diagnosis[1] = 2;
		}
		$diagnosis_data = [
			'health_status' => self::getHealthByCode($case_diagnosis[1]),
			'track_id' => $track_id,
		];
		if(!empty($case_diagnosis[2])){
			foreach ($case_diagnosis[2] as $key=>$additional_data){
				$diagnosis_data[$key] = $additional_data;
			}
		}
		$diagnosis_data['time'] = date("h:i:s");
		$diagnosis_data['message'] = $case_diagnosis[0];
		return $diagnosis_data;
	}

    /**
     * @param array $diagnosis_headers_array
     * @param array $case_diagnosis
     * @param array $service_params
     */
    public static function add(array $diagnosis_headers_array, array $case_diagnosis = [self::HEALTH_SUCCESS_DEFAULT_MESSAGE, 1], array $service_params = []){
		if(self::IBOLIT_STATUS){
			$track_id = uniqid();
			try{
				//$diagnosis_headers_array - always MUST HAVE first element as a diagnosis name and second element as a
				//subdiagnosis name

				//1. If group not exists - create it, if exists - add element to it
				if(empty($diagnosis_headers_array)){
					throw new Exception('Diagnosis Headers Array cannot be empty');
				}
				if(count($diagnosis_headers_array) < 2){
					throw new Exception('Diagnosis Headers Array should contain Diagnosis name and Subdiagnosis name');
				}
				if(!empty($case_diagnosis[3])){
					$track_id = $case_diagnosis[3];
				}

				self::$current_diagnosis = $diagnosis_headers_array[0];
				//Adding diagnosis group to anamnez
				if(!empty(self::$anamnez)){
					if(!array_key_exists(self::$current_diagnosis ,self::$anamnez[self::$current_patient])){
						self::$anamnez[self::$current_patient][self::$current_diagnosis] = [];
					}
				}
				
				//2. If subdiagnosis name exists - run exception for not causing overriding. If not exists - create
				$randomizer = uniqid();
				self::$anamnez[self::$current_patient][self::$current_diagnosis][$diagnosis_headers_array[1].'['.$randomizer.'] ('.$track_id.')'] = [];


				//3. Define the status of case content and fill it
				//By default third param is success health status but you can override it with [message] array to
				//define error content or with [message,status_code] array to define content and its type
				$case_response = self::generateResponseBody($case_diagnosis,$track_id);
				self::$anamnez[self::$current_patient][self::$current_diagnosis][$diagnosis_headers_array[1].'['.$randomizer.'] ('.$track_id.')'] = $case_response;
				self::$current_diagnosis = false;

				self::attachServices($service_params, $diagnosis_headers_array, $case_diagnosis, $case_response, $track_id);

			} catch (Exception $e){
				echo 'Ibolit exception thrown on line '.__LINE__.' of file '.__FILE__.': ',  $e->getMessage(), "\n";
				exit();
			}
		}
	}

    /**
     * @param array $service_params
     * @param array $diagnosis_headers_array
     * @param array $case_diagnosis
     * @param array $case_response
     * @param $track_id
     */
    public static function attachServices(array $service_params, array $diagnosis_headers_array, array $case_diagnosis, array $case_response, $track_id){

			//Attach Team Notifier service
			if(!empty($service_params['team_notifier'])){
				$notifier_message = '';
				foreach ($case_response as $key => $line){
					$notifier_message .= $key.": ".$line." | ";
				}
				$notifier_header = $diagnosis_headers_array[1].' ('.$track_id.')';
				if(!empty($service_params['team_notifier']['custom_header'])){
					$notifier_header  = $service_params['team_notifier']['custom_header'];
				}
				if(!empty($service_params['team_notifier']['custom_message'])){
					$notifier_message = $service_params['team_notifier']['custom_message'];
				}
				F3deshaHelpers::teamNotifier($notifier_header, $notifier_message);
			}

	}

    /**
     *
     */
    public static function signAnamnez(){
		if(self::IBOLIT_STATUS){
			self::$current_patient = false;
		}
	}

    /**
     * @param int $report_id
     * @param array $data
     */
    public static function report(int $report_id, array $data = []){
		try{
			Ibolit::init();
			Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
			switch ($report_id){
				case 1:
					$stacks = [];
					foreach ($data['point_import_stack'] as $i=>$s){
						$stacks['Dealer group '.++$i] = $s;
					}
					Ibolit::add(['Point Import Stack','Point Import Stack '],[
						'Point import stack defined successfully. This dealer groups will be included in import',1,
						$stacks,'j45j5kj34jh5hj363k4l6j45kjk23k4']);
					break;
				case 2:
					Ibolit::add(['Point Import','Point Import'],[
						'Started point import for current dealer group',1,$data,'gh5j344jk2h3kj523bh4j2jk3h2j3k2']);
					break;
				case 3:
					Ibolit::add(['File Checker','File Checker'],[
						'File not exists',2,$data,'k645lk5j43lk4j42k34l;k32j6l45k3'],
						['team_notifier' => ['custom_header'=> 'Import file not found',
											 'custom_message'=>'File '.$data['file_path'].' not found. Check it please.']]);
					break;
				case 4:
					Ibolit::add(['File Checker','File Checker'],[
						'File has 0kb',2,$data,'k245lk5j43lk4j42k34l;k32j6l45k3'],
						['team_notifier' => ['custom_header'=> 'Import file has 0 kb',
											 'custom_message'=>'File '.$data['file_path'].' has 0 kb size. Check it please.']]);
					break;
				default:
					throw new Exception('Report id '.$report_id.' not found in report cases list.');
					break;
			}
			Ibolit::signAnamnez();
			Ibolit::talkVerdict();
		} catch (Exception $e){
			echo 'Ibolit exception thrown on line '.__LINE__.' of file '.__FILE__.': ',  $e->getMessage(), "\n";
			exit();
		}
	}

    /**
     * @param array $track_id_set
     * @param array $names_array
     *
     * @return bool
     */public static function findByTrackId(array $track_id_set, array $names_array){
	$summary_matches = [];
		foreach ($track_id_set as $searchword){
			$matches = array_filter($names_array, function($var) use ($searchword) {
				return preg_match("/\($searchword\)/i", $var);
			});
			if(!empty($matches)){
				$summary_matches[] = $matches;
			}
		}
		return count($summary_matches) === count($track_id_set) ? true : false;
	}

    /**
     * @return string
     */
    public static function generateVerdict(){
		$names_array = [];

		foreach (self::$anamnez as $group){
			foreach ($group as $diagnosis_group){
				if(!empty($diagnosis_group)){
					foreach ($diagnosis_group as $needed_name => $subdiagnosis)
						$names_array[] = $needed_name;
				}
			}
		}

		//Если в анамнезе найдены все элементы указанного массива, Вердикт добавляет нужное предложение
		$sentences = [
			[self::findByTrackId([
				'j45j5kj34jh5hj363k4l6j45kjk23k4'
			],$names_array) => 'Import stack defined successfully. '],
			[self::findByTrackId([
				'gh5j344jk2h3kj523bh4j2jk3h2j3k2'
			],$names_array) => 'Import file found. Starting point import.'],
			[self::findByTrackId([
				'k645lk5j43lk4j42k34l;k32j6l45k3'
			],$names_array) => 'Import file not found. Check the path.'],
			[self::findByTrackId([
				'k5l4;6l343k5345k43hk5j34j34j5k3'
			],$names_array) => 'Input data validation failed. Possible invalid csv fields: Vin, year, dealer id'],
			[self::findByTrackId([
				'hj56j354jkk4j3534kj54h63lj6l322'
			],$names_array) => 'Input data validation failed. Possible invalid csv field: internet price'],
			[self::findByTrackId([
				'k4j5hkj64l5k7k46k453l34k32;42n2'
			],$names_array) => 'Input data validation failed. Possible invalid csv fields: invoice, msrp'],
			[self::findByTrackId([
				'lk5l3k45lk346lk34j645kl7l3;;2;2'
			],$names_array) => 'Input data validation failed. Possible invalid csv fields: invoice, msrp'],
			[self::findByTrackId([
				'jk54lnk5j435l34kn6kl43lk634ll33'
			],$names_array) => 'Input data validation failed. Possible invalid csv fields: loaner_miles'],
			[self::findByTrackId([
				'll3322l43llj1l23l1ll3x7cx8xx0-x'
			],$names_array) => 'We didnt find Ignite Item by vin, so we create new Ignite Item. '],
			[self::findByTrackId([
				'h3j3l332l733k6j3l3l3ldd76f6d7d8'
			],$names_array) => 'We found Ignite Item in db, so we just will update it. '],
			[self::findByTrackId([
				'33gk4j5lk2j4lhk23j3k63lh4t;4k3j'
			],$names_array) => 'Making a pay request to Chrome ADS. '],
			[self::findByTrackId([
				'lk7j65l5l;534l5k34;j53kl766kj5k'
			],$names_array) => 'Inserting to multiple trim ban table to prevent money leak. '],
			[self::findByTrackId([
				'k4ll35j34kl35kl34ll5k34j63k3l23'
			],$names_array) => 'This car has multiple trims, so we cant import it. '],
			[self::findByTrackId([
				'vm66ghjnk34k3j4k3k43k4k3k;54v55'
			],$names_array) => 'This car is in ADS Ban table for some reason so we cant import it. '],
			[self::findByTrackId([
				'j3l234kl32k243jk42k34lj23jkl2l3'
			],$names_array) => 'This car was just added to ADS Ban table so we cant import it. '],
			[self::findByTrackId([
				'lk2k2kl6hk233k2hlk34lk223jlk3l2'
			],$names_array) => 'Dealer gave you a wrong OEM Model code. '],
			[self::findByTrackId([
				'h34k3k4lk53lkl34lk5lh2;21j;;433'
			],$names_array) => 'We have the cf_type value for this Item. So we assigned condition type based on it. '],
			[self::findByTrackId([
				'jkl7k63lk4jlh5j54j6l54j4j43l2;2'
			],$names_array) => 'We have no cf_type value for this Item so we assigned default condition type. '],
			[self::findByTrackId([
				'jl56k5lk4j3lkl4l3l4j;3k4j3;34ll'
			],$names_array) => 'This item will have no engine. '],
			[self::findByTrackId([
				'jk54j45kjjl46j45jkk4k4k5j6k4k3k'
			],$names_array) => 'For some reason dealer has no zip code. '],
			[self::findByTrackId([
				'54j6l34lk3j5l3k4kl53h73l42j32j3'
			],$names_array) => 'Creating First Level Pricing Rule. '],
			[self::findByTrackId([
				'g3jh1kh2kkj32kj54h34j22lk4h532h'
			],$names_array) => 'Creating Second Level Pricing Rule. '],
			[self::findByTrackId([
				'j776k6l8m5432kn3n4m856534mm3,34'
			],$names_array) => 'Creating Third Level Pricing Rule. '],
			[self::findByTrackId([
				'h45kj6j45kkj3jk3453jk3j2jk3j2j2',
				'lk4l3l5km43;lk53j57kb43n3lk4232',
				's5j4j64512kll434235j43l;l213;23'
			],$names_array) => 'We found exterior color by direct color code check. '],
			[self::findByTrackId([
				'7l65546kl54;3421l;.43.54mk3k4k3',
				'76j5kj4r23kl4323jl6j4lrm43,56k4',
			],$names_array) => 'We found exterior color by color name check. '],
			[self::findByTrackId([
				'j45jk4534kj543jk5j34l5k3k4673lk',
				'435lk34jl3k400g00gkm-3m34k34m33'
			],  $names_array) => 'We didnt find exterior color by any checks, so we assign first color given and mark car as with not correct color. '],
			[self::findByTrackId([
				'h546kj45hj645k6456k4j56hl5l564l',
				'lk4l3l5km43;lk53j57kb43n3lk4232',
				's5j4j64512kll434235j43l;l213;23'
			], $names_array) => 'We found exterior color for Smithtown Toyota dealer by extended color check behaviour. '],
			[self::findByTrackId([
				'lk34lk5lk3l34lk5l34l34jl34l333f'
			], $names_array) => 'We found new Loaner status by Stock Number. '],
			[self::findByTrackId([
				'6j545l3kl453;l34n7;54l6m3;k45p9',
				'j75l434k5434ho563j4h534po503333',
				'-ll345h6kj43l5k645l63k54l3l3l44'
			], $names_array) => 'We found interior color by direct color code check. '],
			[self::findByTrackId([
				'6j545l3kl453;l34n7;54l6m3;k45p9',
				'lk54l6k4l5k6lk45;32jl34;23l2;2;'
			], $names_array) => 'Interior color code wrong: given by dealer, but it doesnt exist in our db. '],
			[self::findByTrackId([
				';45;6;455452342432,4ljljlk4gk33',
				'hk65j56j56kbj3bj5ljk3j53l453lnm',
			], $names_array) => 'We found interior color by color name check. '],
			[self::findByTrackId([
				'443jl5kj34lkl5k3j;l4p5k34lk3l3l'
			],$names_array) => 'We didnt find interior color by any checks. '],
			[self::findByTrackId([
				'jn56k45j64j5hj6kl45lh6k4k5l6lk4'
			],$names_array) => 'We have no Fuel Id. Thats why car Item will have no images. '],
			[self::findByTrackId([
				'n54mb345lk34kj53k45l34kj345n3l4'
			],$names_array) => 'We have no options given in CSV Item. '],
			[self::findByTrackId([
				'3h4jk5l34lk5k34l5345jl34k53l4;l'
			],$names_array) => 'Chrome Incentives api returned unexpected status. '],
			[self::findByTrackId([
				'lk345lk34kj5k34lklk34lk35lk43l3'
			],$names_array) => 'Chrome Incentives api not responds and stack overflows. '],
			[self::findByTrackId([
				'kj5hl4l34kl543j5l3l4jb3lk353;l4'
			],$names_array) => 'Chrome give us no Incentives or Stackability rules. '],
			[self::findByTrackId([
				'nm6n4f32423hbnbm42m3b543m34nm34'
			],$names_array) => 'We have no Residuals or Leaserates. '],
			[self::findByTrackId([
				'jklalslt43lk4433klk4nkr3f33l333'
			],$names_array) => 'We have no Stackability Rules. '],
			[self::findByTrackId([
				'l45k34jl42j3b5j3l2k524ljk354l3l'
			],$names_array) => 'Ignite Item NOT ACCEPTS activation requirements. '],
		];
		$final_verdict = '';
		foreach ($sentences as $sentence){
			if(array_key_exists("1", $sentence)){
				$final_verdict .= $sentence["1"] . "\n";
			}
		}
		return $final_verdict;
	}

    /**
     * @param array $additional
     */
    public static function talkVerdict(array $additional = []){
		if(self::IBOLIT_STATUS){
			self::$item_string = "\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>";
			$verdict = '';
			$verdict  .= !empty($additional['file']) ? "\nFile: {$additional['file']}" : "";
			$verdict .=  !empty($additional['line_number']) ? "\nLine: {$additional['line_number']}" : "";
			$verdict .=  !empty($additional['import_group']) ? "\nDealer group: {$additional['import_group']}" : "";
			$verdict .=  !empty($additional['import_time']) ? "\nImport time: {$additional['import_time']} second(s)" : "";
			$verdict .= "\nHealth: ";
			$verdict .= Ibolit::hasNoErrors() ? Ibolit::HEALTH_SUCCESS : Ibolit::HEALTH_FAIL;
			$verdict .=  !empty($additional['vin']) ? "\nVin: {$additional['vin']}" : "";
			$color_code = Ibolit::hasNoErrors() ? '[32m' : '[31m';
			$verdict .= (!empty($additional['file']) &&
				!empty($additional['vin']) &&
				!empty($additional['line_number']) &&
				!empty($additional['import_group'])) ? (self::consoleCode("\nItem Import Progress: [".str_repeat("//", (self::healthProgress() / 10)).str_repeat("  ", (10 - (self::healthProgress() / 10)))."] ".self::healthProgress()."%\n", $color_code)) : null;
			$verdict .= "\nVerdict: \n";
			$verdict .= self::generateVerdict()."\n";
			$verdict .= (!empty($additional['file']) &&
				!empty($additional['vin']) &&
				!empty($additional['line_number']) &&
				!empty($additional['import_group'])) ? (Ibolit::hasNoErrors() ? "Import of item successfully finished\n" : "Import of item failed on ".self::healthProgress()."%\n") : null;
			self::$item_string .= $verdict;
			foreach (self::$anamnez as $group_name => $group){
				self::postAnamnezGroup([$group_name => $group]);
			}
			if(self::$details_level === 'all'){
				self::$item_string .= $verdict;
			}
			self::$item_string .= "<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";

			echo self::$item_string;

			if(self::$output === 'log'){
				$file = ImportBalancer::getImportLog(['path' => 'log']);
				file_put_contents($file, self::$item_string, FILE_APPEND | LOCK_EX);
			}
		}
	}

    /**
     * @param array $group
     */
    public static function postAnamnezGroup(array $group){
		foreach ($group as $item){
			self::postDiagnosis($item);
		}
	}

    /**
     * @param $diagnosis_name
     * @param $item_contents
     */
    public static function printDiagnosis($diagnosis_name, $item_contents)
	{

		self::$item_string .= "\n>>>>>>>>>>>>>>>> ".$diagnosis_name."\n";
		self::$item_string .= "Number of checks:".count($item_contents)."\n";
		self::$item_string .= "Details:\n";
	}

    /**
     * @param array $subdiagnosis_unit
     * @param $color_code
     */
    public static function printSubdiagnosisUnit(array $subdiagnosis_unit, $color_code){

			$unit_name = array_keys($subdiagnosis_unit)[0];
			foreach ($subdiagnosis_unit as $unit_part){
				self::$item_string .= self::consoleCode("\t* ".$unit_name.": ".$unit_part."\n", $color_code);
			}

	}

    /**
     * @param $unit_part
     *
     * @return string
     */public static function getColorCodeByHealthStatus($unit_part){
		$code = '[0m';
		if(!empty($unit_part)){
			if($unit_part['health_status'] === self::HEALTH_SUCCESS){
				$code = "[32m";
			}
			elseif($unit_part['health_status'] === self::HEALTH_FAIL){
				$code = "[31m";
			}
			elseif($unit_part['health_status'] === self::HEALTH_WARNING){
				$code = "[1;33m";
			}
		}
		return $code;
	}

    /**
     * @param $subdiagnosis_name
     * @param $subdiagnosis_body
     */
    public static function printSubdiagnosis($subdiagnosis_name, $subdiagnosis_body)
	{
		self::$item_string .= "\n".$subdiagnosis_name.":\n";
		$color_code = self::getColorCodeByHealthStatus($subdiagnosis_body);
		foreach ($subdiagnosis_body as $key=>$value){
			self::printSubdiagnosisUnit([$key=>$value], $color_code);
		}
	}

    /**
     * @param array $item
     */
    public static function postDiagnosis(array $item){
		if(self::$details_level === 'all'){
			foreach ($item as $item_name => $item_contents){
				self::printDiagnosis($item_name, $item_contents);

				foreach ($item_contents as $suddiagnosis_name => $suddiagnosis){
					self::printSubdiagnosis($suddiagnosis_name, $suddiagnosis);
				}

			}
		}
	}

    /**
     * @param string $string
     * @param string $code
     *
     * @return string
     */public static function consoleCode(string $string, string $code){
		$format = $string;
		if(self::$output === 'console'){
			$format = "\033{$code} ".$string."\033[0m";
		}
		if(self::$output === 'web'){
			$format = self::colorForWeb($string, $code);
		}
		return $format;
	}

    /**
     * @param string $string
     * @param $code
     *
     * @return string
     */public static function colorForWeb(string $string, $code){
		if(array_key_exists($code, self::CONSOLE_COLOR_TO_HTML)){
			$string = "<span style=color:".self::CONSOLE_COLOR_TO_HTML[$code]." >".$string."</span>";
		}
		return $string;
	}
}
