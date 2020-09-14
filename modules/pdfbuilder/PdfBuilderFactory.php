<?php

namespace common\components\pdfbuilder;

//Abstraction under MPDF

/**
 * Class PdfBuilder
 * @package common\components
 */
class PdfBuilderFactory
{
	
	const CARVOY_PDF_OFFICIAL = 0;
	private $template_id;
	
	private $templates = [
		'common\components\pdfbuilder\MainTemplateBuilder'
	];
	
	public function __construct(int $template_id)
	{
		$this->template_id = $template_id;
	}
	
	public function getBuilder(){
		$classname = $this->templates[$this->template_id];
		return new $classname();
	}
}
