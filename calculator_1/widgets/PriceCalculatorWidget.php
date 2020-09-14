<?php 
namespace common\components\calculator\widgets;

use common\components\calculator\widgets\interfaces\CalculatorWidget;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;


/**
 * Class PriceCalculatorWidget
 * @package common\components\calculator\widgets
 */
class PriceCalculatorWidget extends Widget
{

    /**
     * @var array
     */
    public $custom_config = [

	];

    /**
     * @var array
     */
    public $calculators_in_widget = [
		1 => 'common\components\calculator\widgets\LeasePriceCalculatorWidget',
		2 => 'common\components\calculator\widgets\FinancePriceCalculatorWidget',
		3 => 'common\components\calculator\widgets\UsedFinancePriceCalculatorWidget'
	];

    /**
     * @param CalculatorWidget $specific_calculator
     * @return mixed
     */
    public function config(CalculatorWidget $specific_calculator){
		return $specific_calculator->getConfig();
	}

    /**
     * @return bool
     */
    public function isManualMode(){
		return $this->custom_config['mode'] === 1;
	}

    /**
     * @param $field
     * @return string
     */
    public static function formValidationErrorsBlock($field){
		$block = '';

		$block .= '<div class="validation_error" ng-show="validator.showErrorsFor(\''.$field['name'].'\')">cant be empty</div>';

		return $block;
	}

    /**
     * @return bool
     */
    public function isIgniteMode(){
		return $this->custom_config['mode'] === 2;
	}

    /**
     * @return string
     */
    public function getManagementButtons(){
		$content = '
	<div class="row">
	<div class="col-sm-1">
		<div ng-click="calculate()" class="btn btn-success btn-large">Calculate</div>
	</div>';
		if($this->isLiverequestMode()){
			$content .= '<div class="col-sm-1">
		<div ng-click="edit_mode()" class="btn btn-info btn-large">Edit</div>
		</div>';
			$content .= '<div class="col-sm-1">
		<div  ng-click="save_calculation()" class="btn btn-success btn-large">Save</div>
		</div>';
			$content .= '<div class="col-sm-2">
		<div  ng-click="update_incentives()" class="btn btn-danger btn-large">Update Incentives</div>
		</div>';
			if($this->custom_config['liverequest_type'] === 1 && Yii::$app->domain->isMainDomain){
				$url = Url::toRoute(['liverequest/download-recap', 'id' => \Yii::$app->getRequest()->get('id')]);
				$content .= '<div class="col-sm-1">
		<a href="'.$url.'" class="btn btn-primary btn-large">Recap</a>
		</div>';	
			}
		}
		$content .= '
	</div>';
		return $content;
	}

    /**
     * @return bool
     */
    public function isLiverequestMode(){
		return $this->custom_config['mode'] === 3;
	}

    /**
     * @param $config
     * @param $model_name
     * @return string
     */
    public function buildInputSections($config, $model_name){
		$template_output = '';
		foreach ($config['visual_blocks'] as $block){
			$angular_model_for_visualization_init = '';
			$angular_model_for_visualization = '';
			if(array_key_exists('show_model', $block) && array_key_exists('show_by_default', $block)){
				$angular_model_for_visualization_init = "style='cursor:pointer;' ng-init='{$block['show_model']} = {$block['show_by_default']}' ng-click = '{$block['show_model']} = !{$block['show_model']}'";
				$angular_model_for_visualization = "ng-show={$block['show_model']} ";
			}
			$template_output .= '<div class="row">';
			$template_output .= "<div class='col-sm-12' {$angular_model_for_visualization_init}><h4 style='color:grey;'>{$block['block_label']}</h4></div>";
			if(!empty($block['fields_in_block'])){
				foreach ($block['fields_in_block'] as $visual_field_block_name){
					$template_output .= $this->buildInputField($config, $visual_field_block_name, $model_name, $angular_model_for_visualization);
				}
			}
			if(!empty($block['widget'])){
				$widget_class = $block['widget']['class'];
				$template_output .= $widget_class::widget($block['widget']['config']);
			}
			$template_output .= '</div>';
		}
		return $template_output;
	}

    /**
     * @param $config
     * @param $visual_field_block_name
     * @param $model_name
     * @param string $angular_model_for_visualization
     * @return string
     */
    public function buildInputField($config, $visual_field_block_name, $model_name, $angular_model_for_visualization = ''){
		$template_output = '';
		foreach ($config['fields'] as $field){
			if($field['name'] === $visual_field_block_name){


				$script_section = '';
				if(array_key_exists('script', $field)){
					foreach ($field['script'] as $key => $script_fragment){
						$script_section .= $key.'="'.$script_fragment.'" ';
					}
				}

					$override_checkbox_block = '';
					if(array_key_exists('overrides', $field)){
						$override_checkbox_block = '<div ng-show="editMode == 2" style="float: right;"><input ng-model="overrideInput.'.$model_name.'.'.$field['name'].'.checked" ng-change="override(\''.htmlspecialchars($model_name).'\',\''.htmlspecialchars($field['name']).'\')" type="checkbox"> Override</div>';
					}

					$line_class = "col-sm-4";
					if(!empty($field['class'])){
						$line_class = $field['class'];
					}

					if(array_key_exists('addon', $field)){

						$template_output .= $field['addon']['template'];

					} else {
						$template_output .= '<div '.$angular_model_for_visualization.' '.$script_section.' class="'.$line_class.'">';
						$label = ucwords(str_replace('_',' ',strtolower($field['name'])));
						if(array_key_exists('label', $field)){$label = $field['label'];}
						$template_output .= '<label class="control-label">'.$label.'</label>';
						$template_output .= $override_checkbox_block;
						$template_output .= '<input ng-model="fields.'.$model_name.'.'.$field['name'].'.value" ng-disabled="fields.'.$model_name.'.'.$field['name'].'.disabled" class="form-control" type="text" name="'.$field['name'].'"><br>';
						$template_output .= '</div>';
					}


			}
		}
		return $template_output;
	}

    /**
     * @param $additional_config
     * @return mixed
     */
    public function formFullConfig($additional_config){
		if(!empty($additional_config['liverequest_type'])){
			$index = $additional_config['liverequest_type'];
			if(!empty($additional_config['liverequest_sub_type'])){
				$index = $additional_config['liverequest_sub_type'];
			}
			$calculators[$index] = $this->calculators_in_widget[$index];
		} else {
			$calculators = $this->calculators_in_widget;
		}

		foreach ($calculators as $i => $widget_name){
			$widget = new $widget_name();
			$widget_config = $this->config($widget);
			$config[$widget_name::CALCULATOR_INDEX] = $widget_config;
		}
		return $config;
	}

    /**
     * @return string
     */
    public function run()
    {
    	if(!empty($this->custom_config['liverequest_type'])){
			$index = $this->custom_config['liverequest_type'];
			if(!empty($this->custom_config['liverequest_sub_type'])){
				$index = $this->custom_config['liverequest_sub_type'];
			}
			$calculators[$index] = $this->calculators_in_widget[$index];
		} else {
			$calculators = $this->calculators_in_widget;
		}

    	foreach ($calculators as $i => $widget_name){
    		$widget = new $widget_name($this->custom_config);
			$widget_template[$widget_name::CALCULATOR_INDEX]['tabs']['tab_label'] = $widget->getTabName();
			$widget_template[$widget_name::CALCULATOR_INDEX]['content'] = $widget->getCalculator();
		}

    	//$template[\common\components\calculator\widgets\LeasePriceCalculatorWidget::CALCULATOR_INDEX]
		$content = $this->getManagementButtons() . '<hr>';
		$content .= '<div class="row">
		<div class="col-sm-2" ng-init="tabActive=\''.$widget_template[key($widget_template)]['tabs']['tab_label'].'\'">
			Deal type:
		</div>';
		foreach ($widget_template as $key => $template){
			$content .= '<div class="col-sm-2" ng-click="tabActive=\''.$template['tabs']['tab_label'].'\'">
			'.$template['tabs']['tab_label'].'
		</div>';
		}
		$content .= '
		</div>';
		foreach ($widget_template as $key => $template){
			$content .= $template['content'];
		}
		$content .= '<hr>' . $this->getManagementButtons();

		switch($this->custom_config['mode']){
			case 1:
				$content .= '<br>Ignite Calculator in Manual Mode';
				break;
			case 2:
				$content .= '<br>Ignite Calculator in Ignite Item Mode';
				break;
			case 3:
				$content .= '<br>Ignite Calculator in Live Request Mode';
				break;
		}
		return $this->render('pricecalculator', [
			'content' => $content,
			'custom_config' => $this->custom_config
		]);
    }
}
