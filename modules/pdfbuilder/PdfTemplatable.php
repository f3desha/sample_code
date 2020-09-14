<?php
namespace common\components\pdfbuilder;

interface  PdfTemplatable {
	public function findTemplateFile();
	public function findTemplateStyle();
	public function build();
}
