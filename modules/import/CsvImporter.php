<?php

namespace modules\import;

/**
 * Class CsvImporter
 * @package modules\import
 */
class CsvImporter
{
    /**
     * @var false|resource
     */
    private $fp;
    /**
     * @var bool
     */
    private $parse_header;
    /**
     * @var array|false|null
     */
    public $header;
    /**
     * @var string
     */
    private $delimiter;
    /**
     * @var int
     */
    private $length;
	//--------------------------------------------------------------------

    /**
     * CsvImporter constructor.
     *
     * @param $file_name
     * @param bool $parse_header
     * @param string $delimiter
     * @param int $length
     */function __construct($file_name, $parse_header=false, $delimiter="\t", $length=0)
	{
		$this->fp = fopen($file_name, "r");
		$this->parse_header = $parse_header;
		$this->delimiter = $delimiter;
		$this->length = $length;

		if ($this->parse_header)
		{
			$this->header = fgetcsv($this->fp, $this->length, $this->delimiter);
		}

	}
	//--------------------------------------------------------------------

    /**
     *
     */
    function __destruct()
	{
		if ($this->fp)
		{
			fclose($this->fp);
		}
	}
	//--------------------------------------------------------------------

    /**
     * @param int $max_lines
     *
     * @return array
     */function get($max_lines=0)
	{
		//if $max_lines is set to 0, then get all the data

		$data = array();

		if ($max_lines > 0)
			$line_count = 0;
		else
			$line_count = -1; // so loop limit is ignored

		while ($line_count < $max_lines && ($row = fgetcsv($this->fp, $this->length, $this->delimiter, '"', "^")) !== FALSE)
		{
			if ($this->parse_header)
			{
				foreach ($this->header as $i => $heading_i)
				{
					$row_new[$heading_i] = $row[$i];
				}
				$data[] = $row_new;
			}
			else
			{
				$data[] = $row;
			}

			if ($max_lines > 0)
				$line_count++;
		}
		return $data;
	}
	//--------------------------------------------------------------------
}
