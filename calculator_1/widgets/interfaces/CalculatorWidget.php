<?php namespace common\components\calculator\widgets\interfaces;

/**
 * Interface CalculatorWidget
 * @package common\components\calculator\widgets\interfaces
 */
interface CalculatorWidget {
    /**
     * @return mixed
     */
    public function getConfig();

    /**
     * @return mixed
     */
    public function getTabName();

    /**
     * @return mixed
     */
    public function getCalculator();
}