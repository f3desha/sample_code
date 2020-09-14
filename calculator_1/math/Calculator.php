<?php

namespace common\components\calculator\math;

use common\components\calculator\interfaces\Collectable;
use yii\base\ErrorException;

/**
 * Class Calculator
 * @package common\components\calculator\math
 */
abstract class Calculator
{

	public $collectable;
	/**
	 * @var integer
	 * Defines which scenario for calculator is used
	 */
	public $scenario = null;

	//Scenarios
	const TAXES_AND_FEES_SCENARIO = 0;
	const CUSTOMIZE_YOUR_OWN_DUE_ON_SIGNING_SCENARIO = 1;
	const FINANCE_DEFAULT_SCENARIO = 3;

	//Scenarios end

	/**
	 *
	 */
	const OPERAND_INDEX_MSRP = 'MSRP';
	/**
	 *
	 */
	const OPERAND_INDEX_MSRP_DISCOUNT = 'MSRP_DISCOUNT';
	/**
	 *
	 */
	const OPERAND_INDEX_INVOICE = 'INVOICE';
	/**
	 *
	 */
	const OPERAND_INDEX_INVOICE_DISCOUNT = 'INVOICE_DISCOUNT';
	/**
	 *
	 */
	const OPERAND_INDEX_NET_CAP_COST = 'NET_CAP_COST';
	/**
	 *
	 */
	const OPERAND_INDEX_CARVOY_PRICE = 'CARVOY_PRICE';
	/**
	 *
	 */
	const OPERAND_INDEX_DEPRECATED_VALUE = 'DEPRECATED_VALUE';
	/**
	 *
	 */
	const OPERAND_INDEX_TRADE_IN = 'TRADE_IN';
	/**
	 *
	 */
	const OPERAND_INDEX_MONTHLY_TERM = 'MONTHLY_TERM';
	/**
	 *
	 */
	const OPERAND_INDEX_MILES_PER_YEAR = 'MILES_PER_YEAR';
	/**
	 *
	 */
	const OPERAND_INDEX_RAW_MONTHLY_PAYMENT = 'RAW_MONTHLY_PAYMENT';
	/**
	 *
	 */
	const OPERAND_INDEX_RESIDUAL_PERCENT = 'RESIDUAL_PERCENT';
	/**
	 *
	 */
	const OPERAND_INDEX_RESIDUAL_BUMP = 'RESIDUAL_BUMP';
	/**
	 *
	 */
	const OPERAND_INDEX_RESIDUAL_WITH_BUMP = 'RESIDUAL_WITH_BUMP';
	/**
	 *
	 */
	const OPERAND_INDEX_RESIDUAL_LOANER_BUMP = 'RESIDUAL_LOANER_BUMP';
	/**
	 *
	 */
	const OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT = 'RESIDUAL_LOANER_ADJUSTMENT';
	/**
	 *
	 */
	const OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT = 'TOTAL_RESIDUAL_AMOUNT';
	/**
	 *
	 */
	const OPERAND_INDEX_SELLING_PRICE = 'SELLING_PRICE';
	/**
	 *
	 */
	const OPERAND_INDEX_CARVOY_FEE = 'CARVOY_FEE';
	/**
	 *
	 */
	const OPERAND_INDEX_AUTO_REBATES = 'AUTO_REBATES';
	/**
	 *
	 */
	const OPERAND_INDEX_CUSTOM_REBATES = 'CUSTOM_REBATES';
	/**
	 *
	 */
	const OPERAND_INDEX_TOTAL_REBATES = 'TOTAL_REBATES';
	/**
	 *
	 */
	const OPERAND_INDEX_AUTO_DEALERCASH = 'AUTO_DEALERCASH';
	/**
	 *
	 */
	const OPERAND_INDEX_CUSTOM_DEALERCASH = 'CUSTOM_DEALERCASH';
	/**
	 *
	 */
	const OPERAND_INDEX_TOTAL_DEALER_CASH = 'TOTAL_DEALER_CASH';
	/**
	 *
	 */
	const OPERAND_INDEX_VAL_MONEY_FACTOR = 'VAL_MONEY_FACTOR';
	/**
	 *
	 */
	const OPERAND_INDEX_INTEREST_VALUE = 'INTEREST_VALUE';
	/**
	 *
	 */
	const OPERAND_INDEX_LEASE_RATE = 'LEASE_RATE';
	/**
	 *
	 */
	const OPERAND_INDEX_MONEY_FACTOR_BUMP = 'MONEY_FACTOR_BUMP';
	/**
	 *
	 */
	const OPERAND_INDEX_TOTAL_MONEY_FACTOR = 'TOTAL_MONEY_FACTOR';
	/**
	 *
	 */
	const OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT = 'BOTTOM_MONTHLY_PAYMENT';
	/**
	 *
	 */
	const OPERAND_INDEX_TAX_PERCENTAGE = 'TAX_PERCENTAGE';
	/**
	 *
	 */
	const OPERAND_INDEX_UPFRONT_TAX = 'UPFRONT_TAX';
	/**
	 *
	 */
	const OPERAND_INDEX_ADJUSTED_UPFRONT_TAX = 'ADJUSTED_UPFRONT_TAX';
	/**
	 *
	 */
	const OPERAND_INDEX_CASHDOWN_TAX = 'CASHDOWN_TAX';
	/**
	 *
	 */
	const OPERAND_INDEX_BANK_FEE = 'BANK_FEE';
	/**
	 *
	 */
	const OPERAND_INDEX_TAXABLE_CUSTOM_FEES = 'TAXABLE_CUSTOM_FEES';
	/**
	 *
	 */
	const OPERAND_INDEX_TAXABLE_COMMON_FEES = 'TAXABLE_COMMON_FEES';
	/**
	 *
	 */
	const OPERAND_INDEX_TAXABLE_FEES = 'TAXABLE_FEES';
	/**
	 *
	 */
	const OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES = 'NONTAXABLE_CUSTOM_FEES';
	/**
	 *
	 */
	const OPERAND_INDEX_NONTAXABLE_COMMON_FEES = 'NONTAXABLE_COMMON_FEES';
	/**
	 *
	 */
	const OPERAND_INDEX_NONTAXABLE_FEES = 'NONTAXABLE_FEES';
	/**
	 *
	 */
	const OPERAND_INDEX_TAXFEES = 'TAXFEES';
	/**
	 *
	 */
	const OPERAND_INDEX_WARRANTY = 'WARRANTY';
	/**
	 *
	 */
	const OPERAND_INDEX_DUE_ON_SIGNING = 'DUE_ON_SIGNING';
	/**
	 *
	 */
	const OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE = 'ADJUSTED_MONTHLY_PRICE';
	/**
	 *
	 */
	const OPERAND_INDEX_ENTER_DUE_ON_SIGNING = 'ENTER_DUE_ON_SIGNING';
	/**
	 *
	 */
	const OPERAND_INDEX_CARVOY_SAVING = 'CARVOY_SAVING';
	/**
	 *
	 */
	const OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH = 'MSRP_DISCOUNT_WITH_DEALER_CASH';
	/**
	 *
	 */
	const OPERAND_INDEX_TAXES_BEFORE_ADJUST = 'TAXES_BEFORE_ADJUST';
	/**
	 *
	 */
	const OPERAND_INDEX_TAXES = 'TAXES';
	/**
	 *
	 */
	const OPERAND_INDEX_CAP_COST_REDUCTION = 'CAP_COST_REDUCTION';
	/**
	 *
	 */
	const OPERAND_INDEX_REQUESTOR_ZIP = 'REQUESTOR_ZIP';
	/**
	 *
	 */
	const OPERAND_INDEX_INTERMEDIAT_RESULT = 'INTERMEDIAT_RESULT';
	/**
	 *
	 */
	const OPERAND_INDEX_ENROLLED_IN_PAYMENT = 'ENROLLED_IN_PAYMENT';
	/**
	 *
	 */
	const OPERAND_INDEX_INTERNET_PRICE = 'INTERNET_PRICE';

	/**
	 *
	 */
	const OPERAND_INDEX_FINANCERATE = 'FINANCERATE';

	/**
	 *
	 */
	const OPERAND_INDEX_ALL_REBATES = 'ALL_REBATES';

	/**
	 *
	 */
	const OPERAND_INDEX_SALES_TAX = 'SALES_TAX';

	/**
	 *
	 */
	const OPERAND_INDEX_AMOUNT_BORROWED = 'AMOUNT_BORROWED';
	/**
	 *
	 */
	const OPERAND_INDEX_MONTHLY_PRICE = 'MONTHLY_PRICE';
	/**
	 *
	 */
	const OPERAND_INDEX_AMOUNT_BORROWED_WITH_INTEREST = 'AMOUNT_BORROWED_WITH_INTEREST';
	/**
	 *
	 */
	const OPERAND_INDEX_DISCOUNT = 'DISCOUNT';
	/**
	 *
	 */


	public function __construct(Collectable $inputProvider)
	{
		$this->collectable = $inputProvider;
		$this->scenario = self::defineScenario($inputProvider);

	}
	/**
	 * @return string
	 */
	public static function getFormulasClassBasedOnScenario(int $scenario_id){
		$path_to_formulas = "common\\components\\calculator\\math\\formulas\\";
		$classes_map = [
			self::TAXES_AND_FEES_SCENARIO => $path_to_formulas.'TaxesAndFeesScenario',
			self::CUSTOMIZE_YOUR_OWN_DUE_ON_SIGNING_SCENARIO => $path_to_formulas.'CustomizeYourOwnDueOnSigning',
			self::FINANCE_DEFAULT_SCENARIO => $path_to_formulas.'FinanceDefaultScenario'

		];
		return $classes_map[$scenario_id];
	}

	/**
	 * @param Collectable $inputProvider
	 * @return integer
	 */
	public static function defineScenario(Collectable $inputProvider)
	{
		if(array_key_exists('direct_scenario_id', $inputProvider->required_options)){
			$scenario = $inputProvider->required_options['direct_scenario_id'];
		} else {
			if(array_key_exists('subtype',$inputProvider->required_options)){
				switch ($inputProvider->required_options['subtype']){
					case 1:
						if(array_key_exists('dostype',$inputProvider->required_options)){
							switch ($inputProvider->required_options['dostype']){
								case self::TAXES_AND_FEES_SCENARIO:
									$scenario = self::TAXES_AND_FEES_SCENARIO;
									break;
								case self::CUSTOMIZE_YOUR_OWN_DUE_ON_SIGNING_SCENARIO:
									$scenario = self::CUSTOMIZE_YOUR_OWN_DUE_ON_SIGNING_SCENARIO;
									break;
							}
						}
						break;
					case 2:
						$scenario = self::FINANCE_DEFAULT_SCENARIO;
						break;
				}
			}
		}

		return $scenario;
	}

	/**
	 * @param Collectable $input_provider
	 * @return array|mixed
	 */
	public function calculate()
	{
		$input_provider = $this->collectable;
		$collection = $this->calculatorKernel($input_provider->input_collection, $this->getCalculatableOperands());
		$collection['required_options']['direct_scenario_id'] = $input_provider->calculator->scenario;
		return $collection;
	}

	/**
	 * @return boolean | ErrorException
	 */
	public function validateInput(){
		return true;
		//throw new ErrorException('Calculator input validation failed.');
	}

	/**
	 * @param $item
	 * @param $high_level_input
	 * @return bool|mixed
	 */
	public function canBeCalculated($item, $high_level_input)
	{
		$calculation_dependencies = $this->getFormulasDependencies();

			if(!empty($calculation_dependencies[$item])) {
				foreach ($calculation_dependencies[$item] as $formula_group) {
					$needed_operands_count = count($formula_group);
					$available_operands_count = 0;
					foreach ($formula_group as $operand) {
						if (array_key_exists($operand, $high_level_input)) {
							$available_operands_count++;
						}
					}
					if ($needed_operands_count === $available_operands_count) {
						return true;
					}
				}
				return false;
			}

	}

    /**
     * @param array $high_level_input
     * @param $calculatable_operands
     * @return array
     */
    public function calculatorKernel(array $high_level_input, $calculatable_operands)
    {
        $composed_input = $high_level_input;


        $cycles = 0;
        $need_to_run = true;

        while ($need_to_run) {
            $inner_counter = 0;
            foreach ($calculatable_operands as $item) {
                if (!array_key_exists($item, $composed_input)) {
                    //If VALUE can be calculated - calculate
                    if ($this->canBeCalculated($item, $composed_input)) {
                        $method_name = 'calculate' . $item;
                        $current_class_name = static::class;
                        if (method_exists($current_class_name, $method_name)) {
                            $composed_input_set = $current_class_name::$method_name($composed_input);
                            if (is_array($composed_input_set)) {
                                $composed_input = array_merge_recursive($composed_input, $composed_input_set);
                            } else {
                                $composed_input[$item] = $composed_input_set;
                            }
                            $inner_counter++;
                        } else {
                        	throw new ErrorException($method_name.' method not found.');
						}
                    }
                }
            }
            if ($inner_counter === 0) {
                $need_to_run = false;
            }
            $cycles++;
        }

        return $composed_input;
    }

}
