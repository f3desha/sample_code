<?php namespace common\components\calculator\inputProviders;

use common\components\calculator\interfaces\Collectable;
use common\components\F3deshaHelpers;
use modules\api\controllers\DefaultController;
use modules\dealerinventory\models\backend\Bankfee;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\DealerinventoryStack;
use modules\dealerinventory\models\backend\LiveRequest;
use modules\dealerinventory\models\backend\LiverequestFee;
use modules\dealerinventory\models\backend\LiverequestStack;
use modules\dealerinventory\models\backend\Taxrate;
use modules\users\models\Profile;
use common\components\calculator\math\Calculator;

/**
 * Class LiverequestLeaseInputProvider
 * @package common\components\calculator\inputProviders
 */
class LiverequestLeaseInputProvider extends BaseInputProvider implements Collectable
{

    /**
     *
     */
    const REQUIRED_FOR_ITEM_CONFIG = [
        0 => [
            'term',
            'mpy',
        ],
        1 => [
            'term',
            'mpy',
            'dueonsigning'
        ]
    ];
    /**
     * @var array
     */
    public $input_collection = [];
    /**
     * @var array
     */
    public $status_report = [];
    /**
     * @var LiveRequest
     */
    public $liverequest;
    /**
     * @var array
     */
    public $active_stack;
    /**
     * @var Profile|null
     */
    public $dealer_profile;

    /**
     * LiverequestLeaseInputProvider constructor.
     * @param LiveRequest $liveRequest
     * @param array $required_options
     */
    public function __construct(LiveRequest $liveRequest, array $required_options)
    {
		parent::assignServiceData($required_options, ['subtype' => 1]);
		$this->assignSpecificInputProviderData(['liverequest' => $liveRequest]);

        $active_stack = LiverequestStack::findStack($liveRequest->id);

        if (!empty($active_stack)) {
            $this->active_stack = $active_stack;
        }
        $this->dealer_profile = Profile::findOne(['user_id' => $this->liverequest->user_id]);
        $this->addStatusReport('raw', 'input provider initiated');

        if ($this->configHasRequiredOptions($this->required_options)) {
            $this->assignMsrp();
            $this->assignMsrpDiscount();
            $this->assignInvoice();
            $this->assignInvoiceDiscount();
            $this->assignMonthlyTerm();
            $this->assignTaxPercentageByZip();
            $this->assignTaxPercentageByTaxrate();
            $this->assignMpy();
            $this->assignResidualBump();
            if ($this->required_options['dostype'] === 1) {
                $this->assignEnterDueOnSigning();
            }
            $this->assignWarranty();
            $this->assignMoneyFactorBump();
            $this->assignBankFeeAndLoaner();
            $this->assignResidualLoanerAdjustment();
            $this->assignCarvoyFee();
            $this->assignTradeIn();

            $this->assignTaxableCommonFees();
            $this->assignTaxableCustomFees();
            $this->assignNonTaxableCommonFees();
            $this->assignNonTaxableCustomFees();

            if ($this->hasActiveIncentivesStack()) {
                $this->assignResidualPercent();
                $this->assignLeaseRate();
                $this->assignAutoRebates();
                $this->assignCustomRebates();
                $this->assignAutoDealercash();
                $this->assignCustomDealercash();
            } else {
                $this->addStatusReport('error', 'no active lease incentive stack');
            }
			parent::__construct();
		}
    }

	/**
	 * @param array $data
	 */
    public function assignSpecificInputProviderData(array $data)
	{
		$this->liverequest = $data['liverequest'];
	}

	/**
     * @param $status
     * @param $text
     */
    public function addStatusReport($status, $text)
    {
        $this->status_report['status']['code'] = $status;
        $this->status_report['status']['body'][] = $text;
    }

    /**
     * @param array $required_options
     * @return bool
     */
    public function configHasRequiredOptions(array $required_options)
    {
        $has_fatal_error = false;

        foreach (self::REQUIRED_FOR_ITEM_CONFIG[$required_options['dostype']] as $key) {
            if (!array_key_exists($key, $required_options)) {
                $this->addStatusReport('error', 'no ' . $key . ' input');
                $has_fatal_error = true;
            }
        }

        if ($has_fatal_error) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    public function assignMsrp()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->liverequest->msrp)) {
            $this->directAssign(Calculator::OPERAND_INDEX_MSRP, $this->liverequest->msrp);
        }
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
     *
     */
    public function assignMsrpDiscount()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->liverequest->msrp_discount)) {
            $this->directAssign(Calculator::OPERAND_INDEX_MSRP_DISCOUNT, $this->liverequest->msrp_discount);
        }
    }

    /**
     *
     */
    public function assignInvoice()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->liverequest->invoice)) {
            $this->directAssign(Calculator::OPERAND_INDEX_INVOICE, $this->liverequest->invoice);
        }
    }

    /**
     *
     */
    public function assignInvoiceDiscount()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->liverequest->invoice_discount)) {
            $this->directAssign(Calculator::OPERAND_INDEX_INVOICE_DISCOUNT, $this->liverequest->invoice_discount);
        }
    }

    /**
     *
     */
    public function assignMonthlyTerm()
    {
        $this->directAssign(Calculator::OPERAND_INDEX_MONTHLY_TERM, (int)$this->required_options['term']);
    }

    /**
     *
     */
    public function assignTaxPercentageByZip()
    {
        $taxrate = false;
        if (!empty($this->required_options['zip'])) {
            //If we dont have taxrates but have zip, find taxrate by zip
            $zip = $this->required_options['zip'];
            $carvoy_api = new DefaultController($this->liverequest->id, 'dealerinventory');
            $taxrate = $carvoy_api->actionGetDealerinventoryTaxrate($zip, Taxrate::LEASE_TYPE);
            $taxrate = $taxrate->gross_tax;
        }

        if ($taxrate) {
            $this->directAssign(Calculator::OPERAND_INDEX_TAX_PERCENTAGE, $taxrate);
        }
    }

    /**
     *
     */
    public function assignTaxPercentageByTaxrate()
    {
        $taxrate = false;
        if (!empty($this->required_options['taxrate'])) {
            $taxrate = $this->required_options['taxrate'];
        }

        if ($taxrate) {
            $this->directAssign(Calculator::OPERAND_INDEX_TAX_PERCENTAGE, $taxrate);
        }
    }

    /**
     *
     */
    public function assignMpy()
    {
        $mpy = str_replace(' ', '', $this->required_options['mpy']);
        $this->directAssign(Calculator::OPERAND_INDEX_MILES_PER_YEAR, (int)$mpy);
    }

    /**
     *
     */
    public function assignResidualBump()
    {
        $rb = 0;
        $residual_bump = Dealerinventory::getBump(
            $this->input_collection[Calculator::OPERAND_INDEX_MILES_PER_YEAR],
            $this->liverequest->make
        );
        if (!empty($residual_bump)) {
            $rb = $residual_bump;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_RESIDUAL_BUMP, (int)$rb);
    }

    /**
     *
     */
    public function assignEnterDueOnSigning()
    {
        $this->directAssign(
            Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
            (int)$this->required_options['dueonsigning']
        );
    }

    /**
     *
     */
    public function assignWarranty()
    {
        $warranty = !empty($this->required_options['warranty']) ? $this->required_options['warranty'] : 0;
        $this->directAssign(Calculator::OPERAND_INDEX_WARRANTY, $warranty);
    }

    /**
     *
     */
    public function assignMoneyFactorBump()
    {
        $mf_bump = 0;
        if (!empty($this->dealer_profile->mf_bump)) {
            $mf_bump = $this->dealer_profile->mf_bump;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_MONEY_FACTOR_BUMP, $mf_bump);
    }

    /**
     *
     */
    public function assignBankFeeAndLoaner()
    {
        $bankfeedb = Bankfee::find()->where(['brand' => $this->liverequest->make])->limit(1)->one();
        if (!empty($bankfeedb)) {
            $bankfee_fee = $this->liverequest->getRelatedFees(
                [
                    'type' => LiverequestFee::BANK_FEE_TYPE,
                ],
                'value'
            );
            if (is_null($bankfee_fee)) {
                $bankfee_fee = 0;
            }
            $this->directAssign(Calculator::OPERAND_INDEX_BANK_FEE, $bankfee_fee);


            if (!empty($bankfeedb->loaner_bump)) {
                $loaner_bump = $bankfeedb->loaner_bump;
                $this->directAssign(Calculator::OPERAND_INDEX_RESIDUAL_LOANER_BUMP, $loaner_bump);
            } else {
                $this->addStatusReport('error', 'no loaner bump given');
            }
        }
    }

    /**
     *
     */
    public function assignResidualLoanerAdjustment()
    {
        $loaner_reduction = 0;
        if (!empty($this->input_collection[Calculator::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT])) {
            //Find residual reduction
            $loaner_reduction = $this->input_collection[Calculator::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT];
        }
        $this->directAssign(Calculator::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT, $loaner_reduction);
    }

    /**
     *
     */
    public function assignCarvoyFee()
    {
        $carvoy_fee = $this->liverequest->getRelatedFees(
            [
                'type' => LiverequestFee::CARVOY_FEE_TYPE,
            ],
            'value'
        );
        if (is_null($carvoy_fee)) {
            $carvoy_fee = 0;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_CARVOY_FEE, (float)$carvoy_fee);
    }

    /**
     *
     */
    public function assignTradeIn()
    {
        if (!empty($this->liverequest->trade_in)) {
            $this->directAssign(Calculator::OPERAND_INDEX_TRADE_IN, $this->liverequest->trade_in);
        }
    }

    /**
     *
     */
    public function assignTaxableCommonFees()
    {
        $taxable_common_fees = $this->liverequest->getRelatedFees(
            [
                'type' => LiverequestFee::COMMON_FEE_TYPE,
                'is_taxable' => LiverequestFee::TAXABLE
            ],
            'value'
        );
        if (is_null($taxable_common_fees)) {
            $taxable_common_fees = 0;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_TAXABLE_COMMON_FEES, $taxable_common_fees);
    }

    /**
     *
     */
    public function assignTaxableCustomFees()
    {
        $taxable_custom_fees = $this->liverequest->getRelatedFees(
            [
                'type' => LiverequestFee::CUSTOM_FEE_TYPE,
                'is_taxable' => LiverequestFee::TAXABLE
            ],
            'value'
        );
        if (is_null($taxable_custom_fees)) {
            $taxable_custom_fees = 0;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_TAXABLE_CUSTOM_FEES, $taxable_custom_fees);
    }

    /**
     *
     */
    public function assignNonTaxableCommonFees()
    {
        $nontaxable_common_fees = $this->liverequest->getRelatedFees(
            [
                'type' => LiverequestFee::COMMON_FEE_TYPE,
                'is_taxable' => LiverequestFee::NON_TAXABLE
            ],
            'value'
        );
        if (is_null($nontaxable_common_fees)) {
            $nontaxable_common_fees = 0;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_NONTAXABLE_COMMON_FEES, $nontaxable_common_fees);
    }

    /**
     *
     */
    public function assignNonTaxableCustomFees()
    {
        $nontaxable_custom_fees = $this->liverequest->getRelatedFees(
            [
                'type' => LiverequestFee::CUSTOM_FEE_TYPE,
                'is_taxable' => LiverequestFee::NON_TAXABLE
            ],
            'value'
        );
        if (is_null($nontaxable_custom_fees)) {
            $nontaxable_custom_fees = 0;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_NONTAXABLE_CUSTOM_FEES, $nontaxable_custom_fees);
    }

    /**
     * @return bool
     */
    public function hasActiveIncentivesStack()
    {
        return !empty($this->active_stack);
    }

    /**
     *
     */
    public function assignResidualPercent()
    {
        $residual_percent = Dealerinventory::getActiveResidual($this->active_stack, $this->required_options['term']);
        if (!is_array($residual_percent)) {
            $this->directAssign(Calculator::OPERAND_INDEX_RESIDUAL_PERCENT, $residual_percent);
        } else {
            $this->addStatusReport('error', 'no valid residuals');
        }
    }

    /**
     *
     */
    public function assignLeaseRate()
    {
        $leaserate = Dealerinventory::getActiveLeaserate($this->active_stack, $this->required_options['term']);
        if (is_array($leaserate)) {
            $this->addStatusReport('error', 'no valid leaserates');
        } else {
            $this->directAssign(Calculator::OPERAND_INDEX_LEASE_RATE, $leaserate);
        }
    }

    /**
     *
     */
    public function assignAutoRebates()
    {
        $rebates_collection = Dealerinventory::getActiveRebates(
            $this->active_stack,
            $this->required_options['term'],
            [],
            true
        );
        $rebate_discount = $rebates_collection['rebates_discount'];
        $this->directAssign(Calculator::OPERAND_INDEX_AUTO_REBATES, $rebate_discount);
    }

    /**
     *
     */
    public function assignCustomRebates()
    {
        $custom_rebates = 0;
        if (!empty($this->required_options['rebates_custom_id_array'])) {
            $rebates_custom_collection = Dealerinventory::getCustomRebates(
                $this->active_stack,
                $this->required_options['term'],
                $this->required_options["rebates_custom_id_array"]
            );
            $custom_rebates = $rebates_custom_collection['rebates_discount'];
        }
        $this->directAssign(Calculator::OPERAND_INDEX_CUSTOM_REBATES, $custom_rebates);
    }

    /**
     *
     */
    public function assignAutoDealercash()
    {
        $dealercash_collection = Dealerinventory::getActiveDealerCash(
            $this->active_stack,
            $this->required_options['term'],
            [],
            true
        );
        $dealercashe_discount = $dealercash_collection['dealerincentives_discount'];
        $this->directAssign(Calculator::OPERAND_INDEX_AUTO_DEALERCASH, $dealercashe_discount);
    }

    /**
     *
     */
    public function assignCustomDealercash()
    {
        $custom_dealercash = 0;
        if (!empty($this->required_options['rebates_custom_id_array'])) {
            $dealercash_custom_collection = Dealerinventory::getCustomDealerCash(
                $this->active_stack,
                $this->required_options['term'],
                $this->required_options["rebates_custom_id_array"]
            );
            $custom_dealercash += $dealercash_custom_collection['dealerincentives_discount'];
        }
        $this->directAssign(Calculator::OPERAND_INDEX_CUSTOM_DEALERCASH, $custom_dealercash);
    }

    /**
     * @param Dealerinventory $ignite_item
     * @param array $required_options
     */
    public function initInput(Dealerinventory $ignite_item, array $required_options)
    {
        $this->ignite_item = $ignite_item;
        $this->required_options = $required_options;

        $active_stack = DealerinventoryStack::getActiveStack(
            DealerinventoryStack::loadIncentiveStacks($this->ignite_item, Dealerinventory::INCENTIVE_TYPE_LEASERATE)
        );
        if (!empty($active_stack)) {
            $this->active_stack = $active_stack;
        }
        $this->dealer_profile = Profile::findOne(['user_id' => $this->ignite_item->user_id]);
        $this->addStatusReport('raw', 'input provider initiated');
    }

}
