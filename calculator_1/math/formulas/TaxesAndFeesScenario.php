<?php
	namespace common\components\calculator\math\formulas;
	
	use common\components\calculator\interfaces\Collectable;
	use common\components\calculator\math\formulas\interfaces\FormulaInterface;

	class TaxesAndFeesScenario extends BaseFormulas implements FormulaInterface {

		/**
		 * @return array
		 */
		public function getCalculatableOperands(){
			return [
					self::OPERAND_INDEX_SELLING_PRICE,
					self::OPERAND_INDEX_NET_CAP_COST,
					self::OPERAND_INDEX_CARVOY_PRICE,
					self::OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH,
					self::OPERAND_INDEX_DEPRECATED_VALUE,
					self::OPERAND_INDEX_RESIDUAL_WITH_BUMP,
					self::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT,
					self::OPERAND_INDEX_TOTAL_MONEY_FACTOR,
					self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT,
					self::OPERAND_INDEX_TOTAL_REBATES,
					self::OPERAND_INDEX_TOTAL_DEALER_CASH,
					self::OPERAND_INDEX_RAW_MONTHLY_PAYMENT,
					self::OPERAND_INDEX_VAL_MONEY_FACTOR,
					self::OPERAND_INDEX_INTEREST_VALUE,
					self::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT,
					self::OPERAND_INDEX_UPFRONT_TAX,
					self::OPERAND_INDEX_CASHDOWN_TAX,
					self::OPERAND_INDEX_TAXABLE_FEES,
					self::OPERAND_INDEX_NONTAXABLE_FEES,
					self::OPERAND_INDEX_DUE_ON_SIGNING,
					self::OPERAND_INDEX_CARVOY_SAVING,
					self::OPERAND_INDEX_TAXES_BEFORE_ADJUST,
			];
		}

		public function getFormulasDependencies()
		{
			return [
				self::OPERAND_INDEX_SELLING_PRICE => [
					[
						self::OPERAND_INDEX_INVOICE,
						self::OPERAND_INDEX_INVOICE_DISCOUNT
					]
				],
				self::OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH => [
					[
						self::OPERAND_INDEX_MSRP_DISCOUNT,
						self::OPERAND_INDEX_TOTAL_DEALER_CASH,
						self::OPERAND_INDEX_CARVOY_FEE
					]
				],
				self::OPERAND_INDEX_RESIDUAL_WITH_BUMP => [
					[
						self::OPERAND_INDEX_RESIDUAL_PERCENT,
						self::OPERAND_INDEX_RESIDUAL_BUMP
					]
				],
				self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT => [
					[
						self::OPERAND_INDEX_MSRP,
						self::OPERAND_INDEX_RESIDUAL_WITH_BUMP,
						self::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT
					]
				],
				self::OPERAND_INDEX_DEPRECATED_VALUE => [
					[
						self::OPERAND_INDEX_NET_CAP_COST,
						self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT
					]
				],
				self::OPERAND_INDEX_RAW_MONTHLY_PAYMENT => [
					[
						self::OPERAND_INDEX_MONTHLY_TERM,
						self::OPERAND_INDEX_DEPRECATED_VALUE
					]
				],
				self::OPERAND_INDEX_VAL_MONEY_FACTOR => [
					[
						self::OPERAND_INDEX_NET_CAP_COST,
						self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT
					]
				],
				self::OPERAND_INDEX_TOTAL_MONEY_FACTOR => [
					[
						self::OPERAND_INDEX_LEASE_RATE,
						self::OPERAND_INDEX_MONEY_FACTOR_BUMP
					]
				],
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
				self::OPERAND_INDEX_INTEREST_VALUE => [
					[
						self::OPERAND_INDEX_LEASE_RATE,
						self::OPERAND_INDEX_VAL_MONEY_FACTOR
					]
				],
				self::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT => [
					[
						self::OPERAND_INDEX_RAW_MONTHLY_PAYMENT,
						self::OPERAND_INDEX_INTEREST_VALUE
					]
				],
				self::OPERAND_INDEX_UPFRONT_TAX => [
					[
						self::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT,
						self::OPERAND_INDEX_MONTHLY_TERM,
						self::OPERAND_INDEX_TAX_PERCENTAGE
					]
				],
				self::OPERAND_INDEX_CASHDOWN_TAX => [
					[
						self::OPERAND_INDEX_TAXABLE_FEES,
						self::OPERAND_INDEX_TOTAL_REBATES,
						self::OPERAND_INDEX_TAX_PERCENTAGE
					]
				],
				self::OPERAND_INDEX_TAXABLE_FEES => [
					[
						self::OPERAND_INDEX_TAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_TAXABLE_COMMON_FEES,
						self::OPERAND_INDEX_BANK_FEE
					]
				],
				self::OPERAND_INDEX_NONTAXABLE_FEES => [
					[
						self::OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES,
						self::OPERAND_INDEX_NONTAXABLE_COMMON_FEES,
					]
				],
				self::OPERAND_INDEX_CARVOY_SAVING => [
					[
						self::OPERAND_INDEX_NET_CAP_COST,
						self::OPERAND_INDEX_MSRP
					]
				],
				self::OPERAND_INDEX_DUE_ON_SIGNING => [
					[
						self::OPERAND_INDEX_NONTAXABLE_FEES,
						self::OPERAND_INDEX_TAXABLE_FEES,
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_UPFRONT_TAX,
						self::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT
					]
				],
				self::OPERAND_INDEX_CARVOY_PRICE => [
					[
						self::OPERAND_INDEX_NET_CAP_COST,
						self::OPERAND_INDEX_WARRANTY
					]
				],
				self::OPERAND_INDEX_NET_CAP_COST => [
					[
						self::OPERAND_INDEX_SELLING_PRICE,
						self::OPERAND_INDEX_CARVOY_FEE,
						self::OPERAND_INDEX_TOTAL_REBATES,
						self::OPERAND_INDEX_TOTAL_DEALER_CASH,
						self::OPERAND_INDEX_WARRANTY
					]
				],
				self::OPERAND_INDEX_TAXES_BEFORE_ADJUST => [
					[
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_UPFRONT_TAX
					]
				],
				self::OPERAND_INDEX_TAXFEES => [
					[
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_NONTAXABLE_FEES,
						self::OPERAND_INDEX_TAXABLE_FEES,
					]
				],
			];
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

			return $high_level_input[self::OPERAND_INDEX_SELLING_PRICE] + $high_level_input[self::OPERAND_INDEX_CARVOY_FEE] - $high_level_input[self::OPERAND_INDEX_TOTAL_REBATES] - $high_level_input[self::OPERAND_INDEX_TOTAL_DEALER_CASH] + $high_level_input[self::OPERAND_INDEX_TRADE_IN] + $high_level_input[self::OPERAND_INDEX_WARRANTY];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateCARVOY_PRICE(array $high_level_input)
		{

			return $high_level_input[self::OPERAND_INDEX_NET_CAP_COST] - $high_level_input[self::OPERAND_INDEX_WARRANTY];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTAXES_BEFORE_ADJUST(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_CASHDOWN_TAX] + $high_level_input[self::OPERAND_INDEX_UPFRONT_TAX];
		}
	}
