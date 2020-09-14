<?php
	namespace common\components\calculator\math\formulas;

	use common\components\calculator\math\Calculator;

	class BaseFormulas extends Calculator {

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateSELLING_PRICE(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_INVOICE] - $high_level_input[self::OPERAND_INDEX_INVOICE_DISCOUNT];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateNET_CAP_COST(array $high_level_input)
		{
			$high_level_input[self::OPERAND_INDEX_TRADE_IN] = !array_key_exists(
				self::OPERAND_INDEX_TRADE_IN,
				$high_level_input
			) ? 0 : $high_level_input[self::OPERAND_INDEX_TRADE_IN];

			return $high_level_input[self::OPERAND_INDEX_SELLING_PRICE] + $high_level_input[self::OPERAND_INDEX_CARVOY_FEE] - $high_level_input[self::OPERAND_INDEX_TOTAL_REBATES] - $high_level_input[self::OPERAND_INDEX_TOTAL_DEALER_CASH] + $high_level_input[self::OPERAND_INDEX_TRADE_IN];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateCARVOY_SAVING(array $high_level_input)
		{
			return abs($high_level_input[self::OPERAND_INDEX_NET_CAP_COST] - $high_level_input[self::OPERAND_INDEX_MSRP]);
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateMSRP_DISCOUNT_WITH_DEALER_CASH(array $high_level_input)
		{
			return abs(
				$high_level_input[self::OPERAND_INDEX_MSRP_DISCOUNT] + $high_level_input[self::OPERAND_INDEX_TOTAL_DEALER_CASH] - $high_level_input[self::OPERAND_INDEX_CARVOY_FEE]
			);
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateDEPRECATED_VALUE(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_NET_CAP_COST] - $high_level_input[self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int|mixed
		 */
		public static function calculateTOTAL_RESIDUAL_AMOUNT(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_MSRP] * ($high_level_input[self::OPERAND_INDEX_RESIDUAL_WITH_BUMP]) / 100 - $high_level_input[self::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateRESIDUAL_WITH_BUMP(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_RESIDUAL_PERCENT] + $high_level_input[self::OPERAND_INDEX_RESIDUAL_BUMP];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateRAW_MONTHLY_PAYMENT(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_DEPRECATED_VALUE] / $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateVAL_MONEY_FACTOR(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_NET_CAP_COST] + $high_level_input[self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTAXES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_CASHDOWN_TAX] + $high_level_input[self::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTOTAL_MONEY_FACTOR(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_LEASE_RATE] + $high_level_input[self::OPERAND_INDEX_MONEY_FACTOR_BUMP];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateINTEREST_VALUE(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_VAL_MONEY_FACTOR] * $high_level_input[self::OPERAND_INDEX_TOTAL_MONEY_FACTOR];
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
		 * @return false|float|mixed
		 */
		public static function calculateBOTTOM_MONTHLY_PAYMENT(array $high_level_input)
		{
			return round(
					$high_level_input[self::OPERAND_INDEX_RAW_MONTHLY_PAYMENT],
					2
				) + $high_level_input[self::OPERAND_INDEX_INTEREST_VALUE];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateUPFRONT_TAX(array $high_level_input)
		{
			return ($high_level_input[self::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT] * $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM]) * $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateCASHDOWN_TAX(array $high_level_input)
		{
			return ($high_level_input[self::OPERAND_INDEX_TAXABLE_FEES] + $high_level_input[self::OPERAND_INDEX_TOTAL_REBATES]) * $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTAXABLE_FEES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_TAXABLE_COMMON_FEES] + $high_level_input[self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES] + $high_level_input[self::OPERAND_INDEX_BANK_FEE];
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
		 * @return mixed
		 */
		public static function calculateDUE_ON_SIGNING(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_NONTAXABLE_FEES] + $high_level_input[self::OPERAND_INDEX_TAXABLE_FEES] + $high_level_input[self::OPERAND_INDEX_CASHDOWN_TAX] + $high_level_input[self::OPERAND_INDEX_UPFRONT_TAX] + $high_level_input[self::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT];
		}
	}