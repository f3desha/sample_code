<?php

namespace common\components\pdfbuilder;

//Abstraction under MPDF

/**
 * Class MainTemplateBuilder
 * @package common\components
 */
class MainTemplateBuilder extends PdfBuilderFactory implements PdfTemplatable
{
	
	private $template_file = '@pdfbuilder/main_template.php';
	private $template_style = '@pdfbuilder/main_template.css';
	private $block;
	
	private $last_key_root;
	private $last_key;
	private $last_subclass;
	private $root_class;
	
	public function __construct()
	{
		
	}
	
	public function findTemplateFile(){
		return $this->template_file;
	}
	
	public function findTemplateStyle(){
		return $this->template_style;
	}

	private function resetLastIterators(){
		$this->last_key_root = null;
		$this->last_key = null;
		$this->last_subclass = null;
	}
	
	/**
	 * 
	 */
	public function initBlock(){
		$this->root_class = 'content__item';
		$this->block = [$this->root_class => []];
		return $this;
	}

	public function initBlockShort(){
		$this->root_class = 'content__item total';
		$this->block = [$this->root_class => []];
		return $this;
	}
	
	public function fillSingleBlock(){
		$this->block[$this->root_class][] = ['simple' => []];
		$this->resetLastIterators();
		$this->buildBlockIterators();
		return $this;
	}
	
	public function addNewSection(){
		$this->block[$this->root_class][] = ['content__item--wrapper' => []];
		$this->resetLastIterators();
		$this->buildBlockIterators();
		return $this;
	}

	public function addNewSectionShort(){
		$this->block[$this->root_class][] = ['content__item--wrapper total__price' => []];
		$this->resetLastIterators();
		$this->buildBlockIterators();
		return $this;
	}
	
	public function getBlockConfig(){
		return $this->block;
	}
	
	private function buildBlockIterators(){
		if(empty($this->last_subclass)){
			$this->last_key_root = array_key_last($this->block);
			$this->last_key = array_key_last($this->block[$this->last_key_root]);
			$this->last_subclass = array_key_last($this->block[$this->last_key_root][$this->last_key]);
			
		}
	}

	public function addMainTitle(string $title = null){
		$this->block[$this->last_key_root][$this->last_key][$this->last_subclass]['Main_Title'] = $title;
		return $this;
	}

	public function addSubTitle(string $title = null){
		$this->block[$this->last_key_root][$this->last_key][$this->last_subclass]['Sub_Title'] = $title;
		return $this;
	}
	
	public function addFullWidthImage($image_url = ''){
		if(empty($image_url)) { $image_url = 'https://carvoy.com/statics/web/images/itemCarOptimized.jpg'; }
		$this->block[$this->last_key_root][$this->last_key][$this->last_subclass]['Image'] = $image_url;
		return $this;
	}	

	public function initSimpleList(){
		$this->block[$this->last_key_root][$this->last_key][$this->last_subclass]['Simple_List'] = [];
		return $this;
	}
	
	public function buildSimpleListItem($left_item = '', $right_item = '', $description = ''){
		return [
			'left_value' => $left_item,
			'right_value' => $right_item,
			'description' => $description
		];
	}
	
	public function addSimpleListItem(array $item = []){
		$this->block[$this->last_key_root][$this->last_key][$this->last_subclass]['Simple_List'][] = $item;
		return $this;
	}

	public function buildHeader($top = '', $bottom = ''){
		$content = '';
		$content .= '<div class="header__descr">
                    <strong>
                        '.$top.'
                    </strong>
                    <p>
                        '.$bottom.'
                    </p>
                </div>';
		return $content;
	}
	
	public function buildFooter($data){
		$content = '';
		if(!empty($data)){
			$content .= '<div class="footer__content">
                <p>
                    '.$data.'
                </p>
            </div>';
		}
		return $content;
	}
	/**
	 *
	 */
	public function buildBlock(array $config = [])
	{
		$content = '';
		if(!empty($config)){
			//Start block
			$key = array_key_first($config);
			$content .= '<div class="'.$key.'">';
			foreach ($config as $block){
				if(!empty($block)){
					foreach ($block as $i => $block){
						foreach ($block as $i => $block){
							if($i === 'content__item--wrapper') {$style = 'style="margin-top: 12px;"';} else { $style = ''; }
							$content .= '<div class="'.$i.'" '.$style.'>';
							foreach ($block as $block_element_name => $block_element_value){
								switch ($block_element_name){
									case 'Main_Title':
										$content .= '<p class="content__item--title">
                        		  				  '.$block_element_value.'
                       						 </p>
                       						 <div class="content__item--line"></div>';
										break;
									case 'Sub_Title':
										$content .= ' <p class="content__item--subtitle">
                                '.$block_element_value.'
                            </p>';
										break;
									case 'Image':
										$content .= ' <div class="content__item--img">
                            <img src="'.$block_element_value.'"  />
                        </div>';
										break;
									case 'Simple_List':
										if(!empty($block_element_value) && is_array($block_element_value)){
											$content .= '<div class="content__elem">';
											foreach ($block_element_value as $params){
												$content .= '<div class="content__elem--link clearfix">
                                <p style="text-align: left;">
                                    <b>'.$params['left_value'].'</b>
                                </p>
                                <p style="text-align: right;">
                                    '.$params['right_value'].'
                                </p>
                            </div>';
												if(!empty($params['description'])){
													$content .= '<div class="content__item--text">
                                        <p>
                                            '.$params['description'].'
                                        </p>
                                    </div>';
												}
											}
											$content .= '</div>';
										}
										break;
								}
							}
							$content .= '</div>';
						}
					}
				}
			}
			//End block
			$content .= '</div>';
			return $content;
		}
	}
	
	/**
	 *
	 */
	public function build($header = '', $left_block = '', $right_block = '', $footer = ''){
		return [
			'header' => $header,
			'left_block' => $left_block,
			'right_block' => $right_block,
			'footer' => $footer
		];
	}
	
	/**
	 * 
	 */
	public function stackToLeftSideOfTemplate(array $blocks = []){
		$html = '';
		foreach ($blocks as $block){
			$html .= $block;
		}
		return $html;
	}

	/**
	 *
	 */
	public function stackToRightSideOfTemplate(array $blocks = []){
		$html = '';
		foreach ($blocks as $block){
			$html .= $block;
		}
		return $html;
	}
}
