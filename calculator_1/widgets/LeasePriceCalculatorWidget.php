<?php namespace common\components\calculator\widgets;

use common\components\calculator\widgets\interfaces\CalculatorWidget;
use common\components\calculator\math\Calculator;

/**
 * Class LeasePriceCalculatorWidget
 * @package common\components\calculator\widgets
 *
 * @property string|mixed $calculator
 * @property string|mixed $tabName
 */
class LeasePriceCalculatorWidget extends PriceCalculatorWidget implements CalculatorWidget
{

    /**
     *
     */
    const CALCULATOR_INDEX = "LEASE_CALCULATOR";

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
                Calculator::OPERAND_INDEX_MSRP,
                Calculator::OPERAND_INDEX_INVOICE,
                Calculator::OPERAND_INDEX_INVOICE_DISCOUNT,
                Calculator::OPERAND_INDEX_SELLING_PRICE
            ]
        ],
        [
            'block_label' => 'Rebates',
            'fields_in_block' => [
                'custom_rebates',
                Calculator::OPERAND_INDEX_TRADE_IN,
                Calculator::OPERAND_INDEX_WARRANTY,
                Calculator::OPERAND_INDEX_TOTAL_REBATES,
                Calculator::OPERAND_INDEX_TOTAL_DEALER_CASH
            ]
        ],
        [
            'block_label' => 'Terms',
            'fields_in_block' => [
                'dostype',
                Calculator::OPERAND_INDEX_MONTHLY_TERM,
                Calculator::OPERAND_INDEX_MILES_PER_YEAR,
                Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING
            ]
        ],
        [
            'block_label' => 'Rates',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_LEASE_RATE,
                Calculator::OPERAND_INDEX_MONEY_FACTOR_BUMP,
                Calculator::OPERAND_INDEX_TOTAL_MONEY_FACTOR
            ]
        ],
        [
            'block_label' => 'Residuals',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_RESIDUAL_PERCENT,
                Calculator::OPERAND_INDEX_RESIDUAL_BUMP,
                Calculator::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT,
                Calculator::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT
            ]
        ],
        [
            'block_label' => 'Taxes',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_REQUESTOR_ZIP,
                Calculator::OPERAND_INDEX_TAX_PERCENTAGE,
                Calculator::OPERAND_INDEX_CASHDOWN_TAX,
                Calculator::OPERAND_INDEX_UPFRONT_TAX,
                Calculator::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX
            ]
        ],
        [
            'block_label' => 'Fees',
            'widget' => [
                'class' => 'common\components\calculator\widgets\liverequestFeesWidget',
                'config' => [],
            ]
        ],
        [
            'block_label' => 'Intermediat Calculations',
            'show_model' => 'rest_show',
            'show_by_default' => 'false',
            'fields_in_block' => [
                Calculator::OPERAND_INDEX_NET_CAP_COST,
                Calculator::OPERAND_INDEX_DEPRECATED_VALUE,
                Calculator::OPERAND_INDEX_RAW_MONTHLY_PAYMENT,
                Calculator::OPERAND_INDEX_VAL_MONEY_FACTOR,
                Calculator::OPERAND_INDEX_INTEREST_VALUE,
                Calculator::OPERAND_INDEX_CARVOY_SAVING,
                Calculator::OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH,
                Calculator::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT,
                Calculator::OPERAND_INDEX_DUE_ON_SIGNING
            ]
        ]
    ];
    /**
     * @var array
     */
    public $fields = [
        [
            'name' => Calculator::OPERAND_INDEX_MSRP,
            'class' => 'col-sm-2',
        ],
        [
            'name' => Calculator::OPERAND_INDEX_MSRP_DISCOUNT,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_INVOICE,
            'class' => 'col-sm-2',
        ],
        [
            'name' => Calculator::OPERAND_INDEX_INVOICE_DISCOUNT,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TRADE_IN,
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
            'name' => Calculator::OPERAND_INDEX_NET_CAP_COST,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_DEPRECATED_VALUE,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TAXFEES,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_CAP_COST_REDUCTION,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_RAW_MONTHLY_PAYMENT,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_DUE_ON_SIGNING,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_SELLING_PRICE,
            'class' => 'col-sm-3',
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
            'name' => 'dostype',
            'addon' => [
                'scope' => 'addons',
                'template' => '<div class="col-sm-3">
                                            <div class="form-group"
                                                 >
                                                <label for="request-trim"
                                                       class="control-label">Due on signing type</label>
                                                <div class="dropdown drop-block">
                                             
                                                    <button ng-cloak
                                                            class="didropdown btn btn-info dropdown-toggle ng-binding"
                                                            type="button"
                                                            id="dropdownMenu4"
                                                            data-toggle="dropdown"
                                                            aria-haspopup="true"
                                                            aria-expanded="true">
                                                        {{addons.dosTypes[additionalConfig.liverequest_dos_type]}}
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul 
                                                        class="dropdown-menu"
                                                        aria-labelledby="dropdownMenu4">
                                                        <li ng-cloak
                                                           
                                                            ng-repeat="dosType in addons.dosTypes">
                                                            <a
                                                                    ng-click="additionalConfig.liverequest_dos_type = $index; auto_calculate();"
                                                                    href="">{{dosType}}</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>'
            ]
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
                                                        {{fields.LEASE_CALCULATOR.MONTHLY_TERM.value}} months
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul 
                                                        class="dropdown-menu"
                                                        aria-labelledby="dropdownMenu1">
                                                        <li ng-cloak
                                                           
                                                            ng-repeat="term in addons.monthlyTerms">
                                                            <a
                                                                    ng-click="fields.LEASE_CALCULATOR.MONTHLY_TERM.value = term; setWarrantiesByTerms(term); setRebatesByTerms(term); auto_calculate();"
                                                                    href="">{{term}} months</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>'
            ]
        ],
        [
            'name' => Calculator::OPERAND_INDEX_MILES_PER_YEAR,
            'active_on_page_load' => true,
            'class' => 'col-sm-3',
            'addon' => [
                'scope' => 'addons',
                'template' => '<div class="col-sm-2">
                                            <div class="form-group"
                                                 >
                                                <label for="request-trim"
                                                       class="control-label">Miles Per Year</label>
                                                <div class="dropdown drop-block">
                                             
                                                    <button ng-cloak
                                                            class="didropdown btn btn-success dropdown-toggle ng-binding"
                                                            type="button"
                                                            id="dropdownMenu1"
                                                            data-toggle="dropdown"
                                                            aria-haspopup="true"
                                                            aria-expanded="true">
                                                        {{fields.LEASE_CALCULATOR.MILES_PER_YEAR.value}}
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul 
                                                        class="dropdown-menu"
                                                        aria-labelledby="dropdownMenu1">
                                                        <li ng-cloak
                                                           
                                                            ng-repeat="miles in addons.milesPerYear">
                                                            <a
                                                                    ng-click="fields.LEASE_CALCULATOR.MILES_PER_YEAR.value = miles; auto_calculate();"
                                                                    href="">{{miles}}</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>'
            ]
        ],
        [
            'name' => Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
            'active_on_page_load' => true,
            'script' => [
                'ng-show' => 'additionalConfig.liverequest_dos_type == 1',
            ],
            'class' => 'col-sm-2',
            'label' => 'Due On'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TOTAL_REBATES,
            'class' => 'col-sm-3',
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TOTAL_DEALER_CASH,
            'class' => 'col-sm-3',
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_LEASE_RATE,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_MONEY_FACTOR_BUMP,
            'class' => 'col-sm-3'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TOTAL_MONEY_FACTOR,
            'class' => 'col-sm-4',
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_RESIDUAL_PERCENT,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_RESIDUAL_BUMP,
            'class' => 'col-sm-2'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT,
            'class' => 'col-sm-3'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH,
            'overrides' => [],
            'label' => 'Discount'
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TAX_PERCENTAGE,
            'class' => 'col-sm-3',
        ],
        [
            'name' => Calculator::OPERAND_INDEX_CASHDOWN_TAX,
            'class' => 'col-sm-3',
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_UPFRONT_TAX,
            'script' => [
                'ng-show' => 'additionalConfig.liverequest_dos_type == 0',
            ],
            'class' => 'col-sm-3',
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX,
            'script' => [
                'ng-show' => 'additionalConfig.liverequest_dos_type == 1',
            ],
            'class' => 'col-sm-3',
            'label' => 'Upfront Tax',
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TAXES,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_TAXES_BEFORE_ADJUST,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_VAL_MONEY_FACTOR,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_CARVOY_SAVING,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_INTERMEDIAT_RESULT,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT,
            'overrides' => []
        ],
        [
            'name' => Calculator::OPERAND_INDEX_REQUESTOR_ZIP,
            'class' => 'col-sm-3',
            'active_on_page_load' => true,
            'label' => 'Zip Code'
        ],
    ];

    /**
     * LeasePriceCalculatorWidget constructor.
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

        $template_output .= '<div ng-show="additionalConfig.liverequest_dos_type == 1">Adj. Monthly Payment: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE . '.value}}</div>';
        $template_output .= '<div ng-show="additionalConfig.liverequest_dos_type == 0">Monthly Payment: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT . '.value}}</div>';
        $template_output .= '<div ng-show="additionalConfig.liverequest_dos_type == 1">Due On Signing: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING . '.value}}</div><br>';
        $template_output .= '<div>MSRP: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_MSRP . '.value}}</div>';
        $template_output .= '<div>Discount: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH . '.value}}</div>';
        $template_output .= '<div>Rebates: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_TOTAL_REBATES . '.value}}</div><br>';
        $template_output .= '<div>First month payment: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT . '.value}}</div>';
        $template_output .= '<div>Bank Fee: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_BANK_FEE . '.value}}</div>';
        $template_output .= '<div>Fees: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_TAXFEES . '.value}}</div>';

        $template_output .= '<div ng-show="additionalConfig.liverequest_dos_type == 1">Taxes: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_TAXES . '.value}}</div>';
        $template_output .= '<div ng-show="additionalConfig.liverequest_dos_type == 0">Taxes: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_TAXES_BEFORE_ADJUST . '.value}}</div>';
        $template_output .= '<div ng-show="additionalConfig.liverequest_dos_type == 1 && fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_CAP_COST_REDUCTION . '.value">Cap Reduct: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_CAP_COST_REDUCTION . '.value}}</div>';
        $template_output .= '<div>Total Due: {{fields.' . self:: CALCULATOR_INDEX . '.' . Calculator::OPERAND_INDEX_DUE_ON_SIGNING . '.value}}</div>';

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
        return 'Lease';
    }

    /**
     * @return string|void
     */
    public function run()
    {
    }
}
