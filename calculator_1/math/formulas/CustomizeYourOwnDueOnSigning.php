<?php
	namespace common\components\calculator\math\formulas;
	
	use common\components\calculator\math\formulas\interfaces\FormulaInterface;

	class CustomizeYourOwnDueOnSigning extends BaseFormulas implements FormulaInterface {
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
				self::OPERAND_INDEX_TAXFEES,
				self::OPERAND_INDEX_DUE_ON_SIGNING,
				self::OPERAND_INDEX_CARVOY_SAVING,
				self::OPERAND_INDEX_TAXES_BEFORE_ADJUST,
				self::OPERAND_INDEX_TAXES,
				self::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX,
				self::OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE,
				self::OPERAND_INDEX_INTERMEDIAT_RESULT
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
					]
				],
				self::OPERAND_INDEX_NET_CAP_COST => [
					[
						self::OPERAND_INDEX_SELLING_PRICE,
						self::OPERAND_INDEX_CARVOY_FEE,
						self::OPERAND_INDEX_TOTAL_REBATES,
						self::OPERAND_INDEX_TOTAL_DEALER_CASH
					]
				],
				self::OPERAND_INDEX_TAXFEES => [
					[
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_NONTAXABLE_FEES,
						self::OPERAND_INDEX_TAXABLE_FEES,
						self::OPERAND_INDEX_WARRANTY
					]
				],
				self::OPERAND_INDEX_TAXES => [
					[
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX
					]
				],
				self::OPERAND_INDEX_INTERMEDIAT_RESULT => [
					[
						self::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
						self::OPERAND_INDEX_NET_CAP_COST,
						self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT,
						self::OPERAND_INDEX_TOTAL_MONEY_FACTOR,
						self::OPERAND_INDEX_TAXFEES,
						self::OPERAND_INDEX_TAX_PERCENTAGE
					]
				],
				self::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX => [
					[
						self::OPERAND_INDEX_INTERMEDIAT_RESULT,
						self::OPERAND_INDEX_MONTHLY_TERM,
						self::OPERAND_INDEX_TAX_PERCENTAGE
					]
				],
				self::OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE => [
					[
						self::OPERAND_INDEX_NET_CAP_COST,
						self::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
						self::OPERAND_INDEX_MONTHLY_TERM,
						self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT,
						self::OPERAND_INDEX_LEASE_RATE,
						self::OPERAND_INDEX_TAXFEES,
						self::OPERAND_INDEX_TAX_PERCENTAGE,
						self::OPERAND_INDEX_CASHDOWN_TAX,
						self::OPERAND_INDEX_TAXABLE_FEES,
						self::OPERAND_INDEX_NONTAXABLE_FEES,
						self::OPERAND_INDEX_DUE_ON_SIGNING,
						self::OPERAND_INDEX_INTERMEDIAT_RESULT,
						self::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX
					]
				]
			];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateTAXFEES(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_CASHDOWN_TAX] + $high_level_input[self::OPERAND_INDEX_NONTAXABLE_FEES] + $high_level_input[self::OPERAND_INDEX_TAXABLE_FEES] + $high_level_input[self::OPERAND_INDEX_WARRANTY];
		}

		/**
		 * @param array $high_level_input
		 * @return mixed
		 */
		public static function calculateCARVOY_PRICE(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_NET_CAP_COST];
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateINTERMEDIAT_RESULT(array $high_level_input)
		{
			$p = $high_level_input[self::OPERAND_INDEX_NET_CAP_COST] - $high_level_input[self::OPERAND_INDEX_ENTER_DUE_ON_SIGNING];

			$p1 = ($p - $high_level_input[self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT]) / $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM];
			$p2 = ($p + $high_level_input[self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT]) * $high_level_input[self::OPERAND_INDEX_TOTAL_MONEY_FACTOR];
			$res = ($p1 + $p2 + $high_level_input[self::OPERAND_INDEX_TAXFEES] * (1 / $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM] + $high_level_input[self::OPERAND_INDEX_TOTAL_MONEY_FACTOR])) / (1 - (1 + $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM] * $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE]) * (1 / $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM] + $high_level_input[self::OPERAND_INDEX_TOTAL_MONEY_FACTOR]));
			return $res;
		}

		/**
		 * @param array $high_level_input
		 * @return float|int
		 */
		public static function calculateADJUSTED_UPFRONT_TAX(array $high_level_input)
		{
			return $high_level_input[self::OPERAND_INDEX_INTERMEDIAT_RESULT] * $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM] * $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE];
		}

		/**
		 * @param array $high_level_input
		 * @return array
		 */
		public static function calculateADJUSTED_MONTHLY_PRICE(array $high_level_input)
		{
			$set = [];
			$netcapcost = $high_level_input[self::OPERAND_INDEX_NET_CAP_COST];
			$enterdueonsignin = $high_level_input[self::OPERAND_INDEX_ENTER_DUE_ON_SIGNING];
			$residual_value = $high_level_input[self::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT];
			$term = $high_level_input[self::OPERAND_INDEX_MONTHLY_TERM];
			$leaserate = $high_level_input[self::OPERAND_INDEX_TOTAL_MONEY_FACTOR];
			$taxfees = $high_level_input[self::OPERAND_INDEX_TAXFEES];
			$taxrate = $high_level_input[self::OPERAND_INDEX_TAX_PERCENTAGE];
			$cashdowntax = $high_level_input[self::OPERAND_INDEX_CASHDOWN_TAX];
			$nontaxable_fees = $high_level_input[self::OPERAND_INDEX_NONTAXABLE_FEES];
			$taxable_fees = $high_level_input[self::OPERAND_INDEX_TAXABLE_FEES];
			$due_onsigning = $high_level_input[self::OPERAND_INDEX_DUE_ON_SIGNING];
			$res = $high_level_input[self::OPERAND_INDEX_INTERMEDIAT_RESULT];
			$adj_upfront_tax = $high_level_input[self::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX];

			$fees_due = $res + $cashdowntax + $nontaxable_fees + $taxable_fees + $adj_upfront_tax;
			if ($fees_due < $due_onsigning) {
				$fees_due = (-(1 / (1 + $leaserate * $term - $term + $taxrate * $term * $term * $leaserate))) *
					($netcapcost - $enterdueonsignin - $residual_value + $taxfees * $term + $taxrate * $term * $netcapcost
						- $taxrate * $term * $residual_value + $taxrate * $term * $term * $leaserate * $netcapcost
						- $taxrate * $term * $term * $leaserate * $enterdueonsignin + $taxrate * $term * $term
						* $residual_value * $leaserate + $leaserate * $term * $netcapcost - $leaserate * $term * $enterdueonsignin
						+ $residual_value * $leaserate * $term);

				$cap_cost_reduction = $fees_due - $enterdueonsignin;
				$set[self::OPERAND_INDEX_CAP_COST_REDUCTION] = $cap_cost_reduction;
				$adj_net_cap_cost = $netcapcost + $cap_cost_reduction;
				$adj_dep_value = $adj_net_cap_cost - $residual_value;
				$adj_value_for_mf = $adj_net_cap_cost + $residual_value;
				$adj_raw_monthly = $adj_dep_value / $term;
				$adj_interest_value = $adj_value_for_mf * $leaserate;
				$adj_monthly_payment = $adj_raw_monthly + $adj_interest_value;
				$res = $adj_monthly_payment;
			} else {
				$set[self::OPERAND_INDEX_ENROLLED_IN_PAYMENT] = $fees_due - $enterdueonsignin;
			}
			$adjbottomlinepay = floor(round($res, 2) * 100) / 100;
			$set[self::OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE] = $adjbottomlinepay;

			return $set;
		}
	}
