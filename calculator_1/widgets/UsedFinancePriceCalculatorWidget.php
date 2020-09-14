<?php namespace common\components\calculator\widgets;

use common\components\calculator\widgets\interfaces\CalculatorWidget;
use common\components\calculator\math\Calculator;

/**
 * Class UsedFinancePriceCalculatorWidget
 * @package common\components\calculator\widgets
 *
 * @property string|mixed $calculator
 * @property string|mixed $tabName
 */
class UsedFinancePriceCalculatorWidget extends PriceCalculatorWidget implements CalculatorWidget
{

    /**
     *
     */
    const CALCULATOR_INDEX = "FINANCE_CALCULATOR";

    /**
     * @var array
     */
    public $config = [];
    /**
     * @var array
     */
    public $custom_config = [];
    /**
     * @var array
     */
    public $visual_blocks = [
        [
            'block_label' => 'Essentials',
            'fields_in_block' => [
                //Calculator::OPERAND_INDEX_INVOICE,
                //Calculator::OPERAND_INDEX_INVOICE_DISCOUNT,
                //Calculator::OPERAND_INDEX_MSRP_DISCOUNT,
                Calculator::OPERAND_INDEX_INTERNET_PRICE,
                Calculator::OPERAND_INDEX_TRADE_IN,
                Calculator::OPERAND_INDEX_SELLING_PRICE,
                Calculator::OPERAND_INDEX_CARVOY_PRICE,
				Calculator::OPERAND_INDEX_WARRANTY,
            ],
        ],
        [
            'block_label' => 'Terms',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_MONTHLY_TERM,
                Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
            ],
        ],
        [
            'block_label' => 'Rates',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_REQUESTOR_ZIP,
                Calculator::OPERAND_INDEX_TAX_PERCENTAGE,
                Calculator::OPERAND_INDEX_FINANCERATE,
                Calculator::OPERAND_INDEX_CASHDOWN_TAX,
                Calculator::OPERAND_INDEX_UPFRONT_TAX,
            ],
        ],
        [
            'block_label' => 'Fees',
            'widget' => [
                'class' => 'common\components\calculator\widgets\liverequestFeesWidget',
                'config' => [],
            ]
        ],
        [
            'block_label' => 'Used Car Rates',
            'widget' => [
                'class' => 'common\components\calculator\widgets\liverequestUsedRatesWidget',
                'config' => [],
            ]
        ],
        [
            'block_label' => 'Rest',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_AMOUNT_BORROWED,
                Calculator::OPERAND_INDEX_AMOUNT_BORROWED_WITH_INTEREST,
                Calculator::OPERAND_INDEX_SALES_TAX,
            ],
        ]
    ];
    /**
     * @var array
     */
    public $fields = [
        [
            'name' => Calculator::OPERAND_INDEX_MSRP,
        ],
        [
            'name' => Calculator::OPERAND_INDEX_INVOICE,
            'class' => 'col-sm-2'
        ],
		[
			'name' => Calculator::OPERAND_INDEX_WARRANTY,
			'class' => 'col-sm-2',
			'addon' => [
				'scope' => 'addons',
				'template' => '
					<div class="col-sm-2">
						<label for="request-trim"
							   class="control-label">Warranties</label>
					   <div 
						   ng-dropdown-multiselect="" 
						   options="addons.warranties.warrantyList" 
						   selected-model="addons.warranties.selected"
						   extra-settings="addons.warranties.extraSettings"
						   events="addons.warranties.events" 
						   >
						   
						</div>
					</div>
						'
			]
		],
        [
            'name' => Calculator::OPERAND_INDEX_TRADE_IN,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_INVOICE_DISCOUNT,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_INTERNET_PRICE,
            'class' => 'col-sm-2'

        ],
        [
            'name' => Calculator::OPERAND_INDEX_MSRP_DISCOUNT,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_MONTHLY_PRICE,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
            'active_on_page_load' => true,
            'class' => 'col-sm-3'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TOTAL_REBATES,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_CASHDOWN_TAX,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_DISCOUNT,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_UPFRONT_TAX,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TOTAL_DEALER_CASH,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_CARVOY_PRICE,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_MONTHLY_TERM,
            'active_on_page_load' => true,
            'addon' => [
                'scope' => 'addons',
                'template' => '<div class="col-sm-2">
												<div class="form-group"
													 >
													<label for="request-trim"
														   class="control-label">Monthly
														Term</label>
													<div class="dropdown drop-block">
												 
														<button ng-cloak
																class="didropdown btn btn-success dropdown-toggle ng-binding"
																type="button"
																id="dropdownMenu1"
																data-toggle="dropdown"
																aria-haspopup="true"
																aria-expanded="true">
															{{fields.FINANCE_CALCULATOR.MONTHLY_TERM.value}} months
															<span class="caret"></span>
														</button>
														<ul 
															class="dropdown-menu"
															aria-labelledby="dropdownMenu1">
															<li ng-cloak
															   
																ng-repeat="term in addons.monthlyTerms">
																<a
																		ng-click="fields.FINANCE_CALCULATOR.MONTHLY_TERM.value = term; auto_calculate();"
																		href="">{{term}} months</a>
															</li>
														</ul>
													</div>
												</div>
											</div>'
            ]
        ],
        [
            'name' => Calculator::OPERAND_INDEX_AMOUNT_BORROWED,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TAX_PERCENTAGE,
        ],
        [
            'name' => Calculator::OPERAND_INDEX_REQUESTOR_ZIP,
            'active_on_page_load' => true,
        ],
        [
            'name' => Calculator::OPERAND_INDEX_AMOUNT_BORROWED_WITH_INTEREST,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_FINANCERATE,
        ],
        [
            'name' => Calculator::OPERAND_INDEX_SALES_TAX,
            'overrides' => []
        ],
        [
            'name' => 'custom_rebates',
            'addon' => [
                'scope' => 'addons',
                'template' => '
					<div class="col-sm-2">
						<label for="custom-rebates"
							   class="control-label">Rebates</label>
						<div class="dropdown drop-block">
							<multiselect
									disabled="editMode == 1"
									data-ng-model="addons.rebates.selected"
									options="r.label for r in addons.rebates.allCustomRebatesList"
									data-multiple="true"
									data-compare-by="signatureID"
									id="custom-rebates"
									scroll-after-rows="10"
							></multiselect>
						</div>
					</div>
						'
            ]
        ],
        [
            'name' => Calculator::OPERAND_INDEX_SELLING_PRICE,
            'overrides' => []
        ],
    ];

    /**
     * UsedFinancePriceCalculatorWidget constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->custom_config = $config;
    }

    /**
     * @return mixed|string
     */
    public function getCalculator()
    {
        $widget_config = $this->getConfig();

        $template_output = '';
        $template_output .= '<div class="row" ng-show="tabActive==\'' . $this->getTabName() . '\'">';
        $template_output .= '<div class="col-sm-9">';
        //BUILD LEFT BLOCK
        //BUILD INPUT SECTIONS
        $template_output .= $this->buildInputSections($widget_config, self::CALCULATOR_INDEX);
        //BUILD INPUT FIELD

        $template_output .= '</div>';
        $template_output .= '<div class="col-sm-3">';
        //BUILD RIGHT BLOCK
        $template_output .= '<div>Deal Summary</div>';
        $template_output .= '<div>Adj. Monthly Payment: {{fields.' . self::CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_MONTHLY_PRICE . '.value}}</div>';
        $template_output .= '<div>Due On Signing: {{fields.' . self::CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING . '.value}}</div><br>';

        $template_output .= '</div>';
        $template_output .= '</div>';


        return $template_output;
    }

    /**
     * @return array|mixed
     */
    public function getConfig()
    {
        $this->config['fields'] = $this->fields;
        $this->config['visual_blocks'] = $this->visual_blocks;
        return $this->config;
    }

    /**
     * @return mixed|string
     */
    public function getTabName()
    {
        return 'Finance';
    }

    /**
     * @return string|void
     */
    public function run()
    {
    }
}