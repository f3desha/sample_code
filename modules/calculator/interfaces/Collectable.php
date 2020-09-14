<?php namespace common\components\calculator\interfaces;

/**
 * Interface Collectable
 * @package common\components\calculator\interfaces
 */
interface Collectable
{
    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function directAssign($key, $value);

	/**
	 * @param array $data
	 */
    public function assignSpecificInputProviderData(array $data);

}
