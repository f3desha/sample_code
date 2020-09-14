<?php

namespace modules\import;

use modules\import\ImportBalancer;

/**
 * Class CsvItem
 * @package modules\import
 */
class CsvItem
{
	//--------------------------------------------------------------------
    /**
     * CsvItem constructor.
     *
     * @param array $csv_line
     * @param \modules\import\ImportBalancer $importBalancer
     * @param int $trigger_line
     */
    function __construct(array $csv_line, ImportBalancer $importBalancer, int $trigger_line)
	{
		Ibolit::collectAnamnez(__METHOD__.'_'.uniqid());
		//Fill the service trigger data needed
		$this->import_group = $importBalancer->trigger;
		$this->line_number = $trigger_line;
		$this->file = $importBalancer->trigger_settings->csv_path;

		//Fill the csv lines data
		$key = array_keys($csv_line)[0];
		foreach ($csv_line[$key] as $key=>$value){
			$this->$key = $value;
		}

		Ibolit::add(['CsvItem Creation','Init CsvItem'],['CSV Item Initialized successfully',1,get_object_vars($this)]);
		Ibolit::signAnamnez();
	}
	//--------------------------------------------------------------------

    /**
     *
     */
    function __destruct()
	{

	}
}