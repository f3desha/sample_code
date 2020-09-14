<?php namespace common\components\calculator\inputProviders;

use common\components\calculator\interfaces\Collectable;
use common\components\calculator\math\Calculator;
use yii\base\Model;

abstract class BaseInputProvider extends Model {

	/**
	 * @var mixed
	 */
	public $calculator;

	/**
	 * @var array
	 */
	public $required_options = [];

	public function __construct()
	{
		$this->calculator = $this->initCalculator();
	}

	/**
	 * @return mixed
	 */
	public function initCalculator()
	{
		//Returns specific scenario calculator based on required options
		$scenario_id = Calculator::defineScenario($this);
		$calculator_for_scenario_name = Calculator::getFormulasClassBasedOnScenario($scenario_id);
		return new $calculator_for_scenario_name($this);
	}

	/**
	 * @param $required_options
	 * @param array $additional_optional
	 */
	public function assignServiceData($required_options, $additional_optional = []){
		//All input providers should have required_options for defining scenario of calculator
		$this->required_options = $required_options;
		foreach ($additional_optional as $key => $value){
			$this->required_options[$key] = $value;
		}
	}

}