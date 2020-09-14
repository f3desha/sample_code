<?php namespace common\components\calculator\inputProviders;

use common\components\calculator\interfaces\Collectable;
use common\components\F3deshaHelpers;
use modules\api\controllers\DefaultController;
use modules\dealerinventory\models\backend\Bankfee;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\DealerinventoryStack;
use modules\dealerinventory\models\backend\Taxrate;
use modules\users\models\Profile;
use yii\db\Exception;
use common\components\calculator\math\Calculator;

/**
 * Class IgniteItemLeaseInputProvider
 * @package common\components\calculator\inputProviders
 */
class IgniteItemLeaseInputProvider extends BaseInputProvider implements Collectable
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
     *
     */
    const DOS_TYPE_REMAPPING = [
        0 => [
            'adjbottomlinepay' => 'bottommonthlypayment',
            'total_tax' => 'taxesbeforeadjust',
            'adjupfronttax' => 'upfronttax'
        ],
        1 => [
            'dueonsigning' => 'enterdueonsigning',
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
     * @var Dealerinventory
     */
    public $ignite_item;

    //Requirements based on due_on_signing_type(dostype)
    /**
     * @var array
     */
    public $active_stack;
    /**
     * @var Profile|null
     */
    public $dealer_profile;
    /**
     * @var array
     */
    public $ignite_catalog_mapping = [
        'selling_price' => Calculator::OPERAND_INDEX_SELLING_PRICE,
        'carvoy_price' => Calculator::OPERAND_INDEX_CARVOY_PRICE,
        'carvoy_saving' => Calculator::OPERAND_INDEX_CARVOY_SAVING,
        'bump' => Calculator::OPERAND_INDEX_RESIDUAL_BUMP,
        'msrp' => Calculator::OPERAND_INDEX_MSRP,
        'msrp_discount' => Calculator::OPERAND_INDEX_MSRP_DISCOUNT,
        'msrp_discount_with_dealercash' => Calculator::OPERAND_INDEX_MSRP_DISCOUNT_WITH_DEALER_CASH,
        'invoice' => Calculator::OPERAND_INDEX_INVOICE,
        'invoice_discount' => Calculator::OPERAND_INDEX_INVOICE_DISCOUNT,
        'residual_with_bump' => Calculator::OPERAND_INDEX_RESIDUAL_WITH_BUMP,
        'residual_value' => Calculator::OPERAND_INDEX_TOTAL_RESIDUAL_AMOUNT,
        'leaserate' => Calculator::OPERAND_INDEX_TOTAL_MONEY_FACTOR,
        'rebate_discount' => Calculator::OPERAND_INDEX_TOTAL_REBATES,
        'dealercashe_discount' => Calculator::OPERAND_INDEX_TOTAL_DEALER_CASH,
        'netcapcost' => Calculator::OPERAND_INDEX_NET_CAP_COST,
        'taxes' => Calculator::OPERAND_INDEX_TAXES,
        'taxesbeforeadjust' => Calculator::OPERAND_INDEX_TAXES_BEFORE_ADJUST,
        'enterdueonsigning' => Calculator::OPERAND_INDEX_ENTER_DUE_ON_SIGNING,
        'dueonsigning' => Calculator::OPERAND_INDEX_DUE_ON_SIGNING,
        'taxrate' => Calculator::OPERAND_INDEX_TAX_PERCENTAGE,
        'deprecvalue' => Calculator::OPERAND_INDEX_DEPRECATED_VALUE,
        'rawmonthpay' => Calculator::OPERAND_INDEX_RAW_MONTHLY_PAYMENT,
        'valmonfactor' => Calculator::OPERAND_INDEX_VAL_MONEY_FACTOR,
        'interestvalue' => Calculator::OPERAND_INDEX_INTEREST_VALUE,
        'bottommonthlypayment' => Calculator::OPERAND_INDEX_BOTTOM_MONTHLY_PAYMENT,
        'upfronttax' => Calculator::OPERAND_INDEX_UPFRONT_TAX,
        'adjupfronttax' => Calculator::OPERAND_INDEX_ADJUSTED_UPFRONT_TAX,
        'cashdowntax' => Calculator::OPERAND_INDEX_CASHDOWN_TAX,
        'bankfee' => Calculator::OPERAND_INDEX_BANK_FEE,
        'total_tax' => Calculator::OPERAND_INDEX_TAXES,
        'capcostreduction' => Calculator::OPERAND_INDEX_CAP_COST_REDUCTION,
        'enrolledinpayment' => Calculator::OPERAND_INDEX_ENROLLED_IN_PAYMENT,
        'adjbottomlinepay' => Calculator::OPERAND_INDEX_ADJUSTED_MONTHLY_PRICE,
    ];

    /**
     * IgniteItemLeaseInputProvider constructor.
     * @param Dealerinventory $ignite_item
     * @param array $required_options
     * @throws Exception
     */
    public function __construct(Dealerinventory $ignite_item, array $required_options)
    {
        parent::assignServiceData($required_options, ['subtype' => 1]);
        $this->assignSpecificInputProviderData(['ignite_item' => $ignite_item]);

		$active_stack = DealerinventoryStack::getActiveStack(
            DealerinventoryStack::loadIncentiveStacks($this->ignite_item, Dealerinventory::INCENTIVE_TYPE_LEASERATE)
        );
        if (!empty($active_stack)) {
            $this->active_stack = $active_stack;
        }
        $this->dealer_profile = Profile::findOne(['user_id' => $this->ignite_item->user_id]);
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
            $this->assignTradeIn();
            $this->assignResidualBump();
            if ($this->isMakeYourOwnDOSType()) {
                $this->assignEnterDueOnSigning();
            }
            $this->assignWarranty();
            $this->assignMoneyFactorBump();
            $this->assignBankFeeAndLoaner();
            $this->assignResidualLoanerAdjustment();
            $this->assignCarvoyFee();
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
	public function assignSpecificInputProviderData(array $data){
		$this->ignite_item = $data['ignite_item'];
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
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->ignite_item->msrp)) {
            $this->directAssign(Calculator::OPERAND_INDEX_MSRP, $this->ignite_item->msrp);
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
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->ignite_item->msrp_discount)) {
            $this->directAssign(Calculator::OPERAND_INDEX_MSRP_DISCOUNT, $this->ignite_item->msrp_discount);
        }
    }

    /**
     *
     */
    public function assignInvoice()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->ignite_item->invoice)) {
            $this->directAssign(Calculator::OPERAND_INDEX_INVOICE, $this->ignite_item->invoice);
        }
    }

    /**
     *
     */
    public function assignInvoiceDiscount()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->ignite_item->invoice_discount)) {
            $this->directAssign(Calculator::OPERAND_INDEX_INVOICE_DISCOUNT, $this->ignite_item->invoice_discount);
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
            $carvoy_api = new DefaultController($this->ignite_item->id, 'dealerinventory');
            $taxrate = $carvoy_api->actionGetDealerinventoryTaxrate($zip, Taxrate::LEASE_TYPE);
            if (empty($taxrate)) {
                $this->addStatusReport('error', 'not tax rate found for zip ' . $zip);
            } else {
                $taxrate = $taxrate->gross_tax;
            }
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
    public function assignTradeIn()
    {
        $this->directAssign(Calculator::OPERAND_INDEX_TRADE_IN, 0);
    }

    /**
     *
     */
    public function assignResidualBump()
    {
        $rb = 0;
        $residual_bump = Dealerinventory::getBump(
            $this->input_collection[Calculator::OPERAND_INDEX_MILES_PER_YEAR],
            $this->ignite_item->make
        );
        if (!empty($residual_bump)) {
            $rb = $residual_bump;
        }
        $this->directAssign(Calculator::OPERAND_INDEX_RESIDUAL_BUMP, (int)$rb);
    }

    /**
     * @return bool
     */
    public function isMakeYourOwnDOSType()
    {
        return $this->required_options['dostype'] == 1;
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
        $bankfeedb = Bankfee::find()->where(['brand' => $this->ignite_item->make])->limit(1)->one();
        if (!empty($bankfeedb)) {
            if (!empty($bankfeedb->bank_fee)) {
                $bankfee = $bankfeedb->bank_fee;
                $this->directAssign(Calculator::OPERAND_INDEX_BANK_FEE, $bankfee);
            } else {
                $this->addStatusReport('error', 'no valid bankfee');
            }

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
        if ($this->ignite_item->itemHasLoaner()) {
            if (!empty($this->ignite_item->belly_miles) && !empty($this->input_collection[Calculator::OPERAND_INDEX_RESIDUAL_LOANER_BUMP])) {
                //Find residual reduction
                $loaner_reduction = $this->belly_miles * $this->input_collection[Calculator::OPERAND_INDEX_RESIDUAL_LOANER_BUMP];
            }
        }
        $this->directAssign(Calculator::OPERAND_INDEX_RESIDUAL_LOANER_ADJUSTMENT, $loaner_reduction);
    }

    /**
     *
     */
    public function assignCarvoyFee()
    {
        $this->directAssign(
            Calculator::OPERAND_INDEX_CARVOY_FEE,
            (float)$this->ignite_item->dealership->carvoy_fee
        );
    }

    /**
     * @throws Exception
     */
    public function assignTaxableCommonFees()
    {
        $common_fees = $this->ignite_item->getCommonFees();
        $taxable_common_fees = array_sum($common_fees['taxable']);
        $this->directAssign(Calculator::OPERAND_INDEX_TAXABLE_COMMON_FEES, $taxable_common_fees);
    }

    /**
     *
     */
    public function assignTaxableCustomFees()
    {
        $custom_fees = $this->ignite_item->getCustomFees();
        $taxable_custom_fees = array_sum($custom_fees['taxable']);
        $this->directAssign(Calculator::OPERAND_INDEX_TAXABLE_CUSTOM_FEES, $taxable_custom_fees);
    }

    /**
     * @throws Exception
     */
    public function assignNonTaxableCommonFees()
    {
        $common_fees = $this->ignite_item->getCommonFees();
        $nontaxable_common_fees = array_sum($common_fees['nontaxable']);
        $this->directAssign(Calculator::OPERAND_INDEX_NONTAXABLE_COMMON_FEES, $nontaxable_common_fees);
    }

    /**
     *
     */
    public function assignNonTaxableCustomFees()
    {
        $custom_fees = $this->ignite_item->getCustomFees();
        $nontaxable_custom_fees = array_sum($custom_fees['nontaxable']);
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
        $rebates_collection = Dealerinventory::getActiveRebates($this->active_stack, $this->required_options['term']);
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
            $this->required_options['term']
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
