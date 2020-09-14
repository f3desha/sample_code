<?php
	namespace common\components\calculator\math\formulas;

	use common\components\calculator\math\formulas\interfaces\FormulaInterface;

	class FinanceDefaultScenario extends BaseFormulas implements FormulaInterface {
		/**
		 * @return array
		 */
		public function getCalculatableOperands(){
			return [
				self::OPERAND_INDEX_TOTAL_REBATES,
				self::OPERAND_INDEX_TOTAL_DEALER_CASH,
				self::OPERAND_INDEX_SELLING_PRICE,
				self::OPERAND_INDEX_TAXABLE_FEES,
				self::OPERAND_INDEX_NONTAXABLE_FEES,
				self::OPERAND_INDEX_CASHDOWN_TAX,
				self::OPERAND_INDEX_UPFRONT_TAX,
				self::OPERAND_INDEX_SALES_TAX,
				self::OPERAND_INDEX_AMOUNT_BORROWED,
				self::OPERAND_INDEX_MONTHLY_PRICE,
				self::OPERAND_INDEX_AMOUNT_BORROWED_WITH_INTEREST,
				self::OPERAND_INDEX_DISCOUNT,
				self::OPERAND_INDEX_ALL_REBATES,
				self::OPERAND_INDEX_CARVOY_PRICE
			];
		}

		/**
		 * @return array
		 */
		public function getFormulasDependencies()
		{
			return [
				self::OPERAND_INDEX_TOTAL_REBATES => [
					[
						self::OPERAND_INDEX_AUTO_REBATES,
						self::OPERAND_INDEX_CUSTOM_REBATES
					]
				],
				self::OPERAND_INDEX_TOTAL_DEALER_CASH => [
					[
						self::OPERAND_INDEX_AUTO_DEALERCASH,
						self::OPERAND_INDEX_CUSTOM_DEALERCASH
					]
				],
				self::OPERAND_INDEX_CARVOY_PRICE => [
					[
						self::OPERAND_INDEX_SELLING_PRICE,
						self::OPERAND_INDEX_CARVOY_FEE
					]
				],
				self::OPERAND_INDEX_ALL_REBATES => [
					[
						self::OPERAND_INDEX_TOTAL_REBATES,
						self::OPERAND_INDEX_TOTAL_DEALER_CASH
					]
				],
				self::OPERAND_INDEX_DISCOUNT => [
					[
						self::OPERAND_INDEX_MSRP_DISCOUNT,
						self::OPERAND_INDEX_TOTAL_DEALER_CASH,
						self::OPERAND_INDEX_CARVOY_FEE
					]
				],
				self::OPERAND_INDEX_TAXABLE_FEES => [
					[
						self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_TAXABLE_COMMON_FEES,
					]
				],
				self::OPERAND_INDEX_NONTAXABLE_FEES => [
					[
						self::OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_NONTAXABLE_COMMON_FEES,
					]
				],
				self::OPERAND_INDEX_SELLING_PRICE => [
					[
						self::OPERAND_INDEX_INVOICE,
						self::OPERAND_INDEX_INVOICE_DISCOUNT,
					],
					[
						self::OPERAND_INDEX_INTERNET_PRICE,
					]
				],
				self::OPERAND_INDEX_UPFRONT_TAX => [
					[
						self::OPERAND_INDEX_CARVOY_PRICE,
						self::OPERAND_INDEX_TAX_PERCENTAGE,
					]
				],
				self::OPERAND_INDEX_CASHDOWN_TAX => [
					[
						self::OPERAND_INDEX_TAXABLE_COMMON_FEES,
						self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_TAX_PERCENTAGE
					]
				],
				self::OPERAND_INDEX_SALES_TAX => [
					[
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_UPFRONT_TAX,
					]
				],
				self::OPERAND_INDEX_AMOUNT_BORROWED_WITH_INTEREST => [
					[
						self::OPERAND_INDEX_MONTHLY_TERM,
						self::OPERAND_INDEX_MONTHLY_PRICE,
					]
				],
				self::OPERAND_INDEX_AMOUNT_BORROWED => [
					[
						self::OPERAND_INDEX_CARVOY_PRICE,
						self::OPERAND_INDEX_SALES_TAX,
						self::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
						self::OPERAND_INDEX_TAXABLE_COMMON_FEES,
						self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_NONTAXABLE_COMMON_FEES,
						self::OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_WARRANTY
					]
				],
				self::OPERAND_INDEX_MONTHLY_PRICE => [
					[
						self::OPERAND_INDEX_FINANCERATE,
						self::OPERAND_INDEX_MONTHLY_TERM,
						self::OPERAND_INDEX_AMOUNT_BORROWED
					]
				],
				self::OPERAND_INDEX_DUE_ON_SIGNING => [
					[
						self::OPERAND_INDEX_SALES_TAX,
						self::OPERAND_INDEX_TAXABLE_FEES,
						self::OPERAND_INDEX_NONTAXABLE_FEES
					]
				]
			];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateSELLING_PRICE(array $high_level_input)
		{
			if (array_key_exists(self::OPERAND_INDEX_INTERNET_PRICE, $high_level_input)) {
				if ($high_level_input[self::OPERAND_INDEX_INTERNET_PRICE] != null) {
					return $high_level_input[self::OPERAND_INDEX_INTERNET_PRICE];
				}
			}
			return $high_level_input[self::OPERAND_INDEX_INVOICE] - $high_level_input[self::OPERAND_INDEX_INVOICE_DISCOUNT];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateCARVOY_PRICE(array $high_level_input)
		{
			$carvoy_price = $high_level_input[self::OPERAND_INDEX_SELLING_PRICE] + $high_level_input[self::OPERAND_INDEX_CARVOY_FEE];
			if (array_key_exists(self::OPERAND_INDEX_TOTAL_REBATES, $high_level_input)) {
				$carvoy_price -= ($high_level_input[self::OPERAND_INDEX_TOTAL_REBATES]);
			}
			if (array_key_exists(self::OPERAND_INDEX_TOTAL_DEALER_CASH, $high_level_input)) {
				$carvoy_price -= ($high_level_input[self::OPERAND_INDEX_TOTAL_DEALER_CASH]);
			}
			return $carvoy_price;
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTOTAL_REBATES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_AUTO_REBATES] + $high_level_input[self::OPERAND_INDEX_CUSTOM_REBATES];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTOTAL_DEALER_CASH(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_AUTO_DEALERCASH] + $high_level_input[self::OPERAND_INDEX_CUSTOM_DEALERCASH];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateALL_REBATES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_TOTAL_REBATES] + $high_level_input[self::OPERAND_INDEX_TOTAL_DEALER_CASH];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateDISCOUNT(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_MSRP_DISCOUNT] + $high_level_input[self::OPERAND_INDEX_TOTAL_DEALER_CASH] - $high_level_input[self::OPERAND_INDEX_CARVOY_FEE];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTAXABLE_FEES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_TAXABLE_COMMON_FEES] + $high_level_input[self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateNONTAXABLE_FEES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_NONTAXABLE_COMMON_FEES] + $high_level_input[self::OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateUPFRONT_TAX(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_CARVOY_PRICE] * $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateCASHDOWN_TAX(array $high_level_input)
		{
			return ($high_level_input[self::OPERAND_INDEX_TAXABLE_COMMON_FEES] + $high_level_input[self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES]) * $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateSALES_TAX(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_CASHDOWN_TAX] + $high_level_input[self::OPERAND_INDEX_UPFRONT_TAX];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateAMOUNT_BORROWED(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_CARVOY_PRICE] + $high_level_input[self::OPERAND_INDEX_SALES_TAX] - $high_level_input[self::OPERAND_INDEX_ENTER_DUE_ON_SIGNING] + ($high_level_input[self::OPERAND_INDEX_TAXABLE_COMMON_FEES] + $high_level_input[self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES]) + ($high_level_input[self::OPERAND_INDEX_NONTAXABLE_COMMON_FEES] + $high_level_input[self::OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES]) + $high_level_input[self::OPERAND_INDEX_WARRANTY];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateAMOUNT_BORROWED_WITH_INTEREST(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_MONTHLY_PRICE] * $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM];
		}

		/**
		 * @param array $high_level_input
		 * @return false|float
		 */
		public static function calculateMONTHLY_PRICE(array $high_level_input)
		{
			$rate = $high_level_input[self::OPERAND_INDEX_FINANCERATE] / 100 / 12;
			if ($rate) {
				//1.
				$negative_term = $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM] * -1;
				$PMT = ($rate * $high_level_input[self::OPERAND_INDEX_AMOUNT_BORROWED]) / (1 - ((1 + $rate) ** $negative_term));
			} else {
				$PMT = $high_level_input[self::OPERAND_INDEX_AMOUNT_BORROWED] / $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM];
			}

			return round($PMT, 2);
		}
	}