<?php namespace common\components\calculator\math\formulas\interfaces;
interface FormulaInterface {

	/**
	 * @return array
	 */
	public function getCalculatableOperands();

	/**
	 * @return array
	 */
	public function getFormulasDependencies();
}