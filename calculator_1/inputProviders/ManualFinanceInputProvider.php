<?php namespace common\components\calculator\inputProviders;

use common\components\calculator\interfaces\Collectable;

/**
 * Class ManualFinanceInputProvider
 * @package common\components\calculator\inputProviders
 */
class ManualFinanceInputProvider extends BaseInputProvider implements Collectable
{

    /**
     * @var array
     */
    public $input_collection = [];
    /**
     * @var array
     */
    public $status_report = [];

    /**
     * ManualFinanceInputProvider constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
		foreach ($data as $key => $value){
			$this->directAssign($key, $value);
		}
		parent::assignServiceData($data['required_options']);
		parent::__construct();
	}

    /**
     * @param $key
     * @param $value
     */
    public function directAssign($key, $value)
    {
        $this->input_collection[$key] = $value;
    }

	/**
	 * @param array $data
	 */
	public function assignSpecificInputProviderData(array $data)
	{

	}

}