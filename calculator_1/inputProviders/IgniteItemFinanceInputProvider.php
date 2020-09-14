<?php namespace common\components\calculator\inputProviders;

use common\components\calculator\interfaces\Collectable;
use common\components\F3deshaHelpers;
use common\components\calculator\math\Calculator;
use modules\api\controllers\DefaultController;
use modules\dealerinventory\models\backend\Bankfee;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\DealerinventoryStack;
use modules\dealerinventory\models\backend\Taxrate;
use modules\users\models\Profile;
use yii\db\Exception;

/**
 * Class IgniteItemFinanceInputProvider
 * @package common\components\calculator\inputProviders
 */
class IgniteItemFinanceInputProvider extends BaseInputProvider implements Collectable
{

    /**
     *
     */
    const REQUIRED_FOR_ITEM_CONFIG = [
        1 => [
            'term',
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
     * @var Dealerinventory
     */
    public $ignite_item;
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
        'carvoy_price' => Calculator::OPERAND_INDEX_CARVOY_PRICE,
        'selling_price' => Calculator::OPERAND_INDEX_SELLING_PRICE,
        'rebate_discount' => Calculator::OPERAND_INDEX_ALL_REBATES,
        'apr' => Calculator::OPERAND_INDEX_FINANCERATE,
        'total_tax' => Calculator::OPERAND_INDEX_SALES_TAX,
        'trade_in' => Calculator::OPERAND_INDEX_TRADE_IN,
        'msrp_discount_with_dealercash' => Calculator::OPERAND_INDEX_DISCOUNT,
        'amount_borrowed_principle' => Calculator::OPERAND_INDEX_AMOUNT_BORROWED,
        'amount_borrowed_with_interest' => Calculator::OPERAND_INDEX_AMOUNT_BORROWED_WITH_INTEREST,
        'financemonthlypayment' => Calculator::OPERAND_INDEX_MONTHLY_PRICE,
        'cashdowntax' => Calculator::OPERAND_INDEX_CASHDOWN_TAX,
        'upfronttax' => Calculator::OPERAND_INDEX_UPFRONT_TAX,
        'sales_tax' => Calculator::OPERAND_INDEX_SALES_TAX
    ];

    /**
     * IgniteItemFinanceInputProvider constructor.
     * @param Dealerinventory $ignite_item
     * @param array $required_options
     * @throws Exception
     */
    public function __construct(Dealerinventory $ignite_item, array $required_options)
    {

		parent::assignServiceData($required_options, ['subtype' => 2]);
		$this->assignSpecificInputProviderData(['ignite_item' => $ignite_item]);

        $this->dealer_profile = Profile::findOne(['user_id' => $this->ignite_item->user_id]);
        $this->addStatusReport('raw', 'input provider initiated');

        if ($this->configHasRequiredOptions($this->required_options)) {
            if ($this->ignite_item->isNewCar()) {
                $this->assignInvoice();
                $this->assignInvoiceDiscount();
                $this->assignMsrpDiscount();
				$this->assignWarranty();

                //For new car get apr from incentives and get rebates and dealercash
                $active_stack = DealerinventoryStack::getActiveStack(
                    DealerinventoryStack::loadIncentiveStacks(
                        $this->ignite_item,
                        Dealerinventory::INCENTIVE_TYPE_FINANCERATE
                    )
                );
                if (!empty($active_stack)) {
                    $this->active_stack = $active_stack;
                }
                if ($this->hasActiveIncentivesStack()) {
                    $this->assignFinanceRate();
                    $this->assignAutoRebates();
                    $this->assignCustomRebates();
                    $this->assignAutoDealercash();
                    $this->assignCustomDealercash();
                } else {
                    $this->addStatusReport('error', 'no active finance incentive stack');
                }
            } elseif ($this->ignite_item->isUsedCar()) {
                //For Used cars get apr from dealer apr
                $this->assignInternetPrice();
                $this->assignDealerAprFinanceRate();
				$this->assignWarranty();
			}

            $this->assignTradeIn();
            $this->assignMonthlyTerm();
            $this->assignTaxPercentageByZip();
            $this->assignTaxPercentageByTaxrate();
            $this->assignEnterDueOnSigning();
            $this->assignCarvoyFee();
            $this->assignTaxableCommonFees();
            $this->assignTaxableCustomFees();
            $this->assignNonTaxableCommonFees();
            $this->assignNonTaxableCustomFees();
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

        foreach (self::REQUIRED_FOR_ITEM_CONFIG[1] as $key) {
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
    public function assignInvoice()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->ignite_item->invoice)) {
            $this->directAssign(Calculator::OPERAND_INDEX_INVOICE, $this->ignite_item->invoice);
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
    public function assignInvoiceDiscount()
    {
        if (F3deshaHelpers::isNotEmptyAndIsNumeric($this->ignite_item->invoice_discount)) {
            $this->directAssign(
                Calculator::OPERAND_INDEX_INVOICE_DISCOUNT,
                $this->ignite_item->invoice_discount
            );
        }
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
     * @return bool
     */
    public function hasActiveIncentivesStack()
    {
        return !empty($this->active_stack);
    }

    /**
     *
     */
    public function assignFinanceRate()
    {
        $financerate = Dealerinventory::getActiveFinancerate($this->active_stack, $this->required_options['term']);

        $this->directAssign(Calculator::OPERAND_INDEX_FINANCERATE, $financerate);
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
     *
     */
    public function assignInternetPrice()
    {
        if (!empty($this->ignite_item->internet_price)) {
            $this->directAssign(Calculator::OPERAND_INDEX_INTERNET_PRICE, $this->ignite_item->internet_price);
        }
    }

    /**
     *
     */
    public function assignDealerAprFinanceRate()
    {
        $user_id = $this->ignite_item->user_id;
        $currRate = 0;
        $rates = $this->ignite_item->dealership->used_cars_rates;

        if (!empty($rates) && !empty($this->required_options['term'])) {
            $rates = json_decode($rates);
            if ($rates && is_array($rates)) {
                foreach ($rates as $rate) {
                    if ($rate->from <= $this->required_options['term'] && $rate->to >= $this->required_options['term']) {
                        $currRate = (double)$rate->rate;
                    }
                }
            }
        }

        $this->directAssign(Calculator::OPERAND_INDEX_FINANCERATE, $currRate);
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
            $taxrate = $carvoy_api->actionGetDealerinventoryTaxrate($zip, Taxrate::PURCHASE_TYPE);
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

    /**
     *
     */
    public function assignMsrp()
    {
        if (!empty($this->ignite_item->msrp)) {
            $this->directAssign(Calculator::OPERAND_INDEX_MSRP, $this->ignite_item->msrp);
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
    public function assignWarranty()
    {
        $warranty = !empty($this->required_options['warranty']) ? $this->required_options['warranty'] : 0;
        $this->directAssign(Calculator::OPERAND_INDEX_WARRANTY, $warranty);
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

}
