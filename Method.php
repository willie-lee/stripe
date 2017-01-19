<?php
namespace Dfe\Stripe;
use Df\Core\Exception as DFE;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Creditmemo as CM;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OP;
use Magento\Sales\Model\Order\Payment\Transaction as T;
use Stripe\Error\Base as EStripeLib;
use Stripe\StripeObject;
class Method extends \Df\StripeClone\Method {
	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canCapturePartial()
	 * @return bool
	 */
	public function canCapturePartial() {return true;}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canRefundPartialPerInvoice()
	 * @return bool
	 */
	public function canRefundPartialPerInvoice() {return true;}

	/**
	 * 2016-11-13
	 * https://stripe.com/docs/api/php#create_charge-amount
	 * https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
	 * @override
	 * @see \Df\Payment\Method::amountFactorTable()
	 * @used-by \Df\Payment\Method::amountFactor()
	 * @return int
	 */
	final protected function amountFactorTable() {return [
		1 => 'BIF,CLP,DJF,GNF,JPY,KMF,KRW,MGA,PYG,RWF,VND,VUV,XAF,XOF,XPF'
	];}

	/**
	 * 2016-12-28
	 * Информация о банковской карте.
	 * https://stripe.com/docs/api#charge_object-source
	 * https://stripe.com/docs/api#card_object
	 * https://stripe.com/docs/api#card_object-brand
	 * https://stripe.com/docs/api#card_object-last4
	 * @override
	 * @see \Df\StripeClone\Method::apiCardInfo()
	 * @used-by \Df\StripeClone\Method::chargeNew()
	 * @param \Stripe\Charge $charge
	 * @return array(string => string)
	 */
	final protected function apiCardInfo($charge) {
		/** @var \Stripe\Card $card */
		$card = $charge->{'source'};
		return [OP::CC_LAST_4 => $card->{'last4'}, OP::CC_TYPE => $card->{'brand'}];
	}

	/**
	 * 2016-12-28
	 * https://stripe.com/docs/api#retrieve_charge
	 * https://stripe.com/docs/api#capture_charge
	 * @override
	 * @see \Df\StripeClone\Method::apiChargeCapturePreauthorized()
	 * @used-by \Df\StripeClone\Method::charge()
	 * @param string $chargeId
	 * @return \Stripe\Charge
	 */
	final protected function apiChargeCapturePreauthorized($chargeId) {return
		\Stripe\Charge::retrieve($chargeId)->capture()
	;}

	/**
	 * 2016-12-28
	 * @override
	 * @see \Df\StripeClone\Method::apiChargeCreate()
	 * @used-by \Df\StripeClone\Method::chargeNew()
	 * @param array(string => mixed) $params
	 * @return \Stripe\Charge
	 */
	final protected function apiChargeCreate(array $params) {return \Stripe\Charge::create($params);}

	/**
	 * 2016-12-28
	 * @override
	 * @see \Df\StripeClone\Method::apiChargeId()
	 * @used-by \Df\StripeClone\Method::chargeNew()
	 * @param \Stripe\Charge $charge
	 * @return string
	 */
	final protected function apiChargeId($charge) {return $charge->id;}

	/**
	 * 2017-01-19
	 * Пример результата: «txn_19deRAFzKb8aMux1TLBWx6ZO».
	 * Структура $response:
	 * df_json_encode_pretty($response->getLastResponse()->json)
		{
			"id": "re_19deRAFzKb8aMux1eZEp32cX",
			"object": "refund",
			"amount": 269700,
			"balance_transaction": "txn_19deRAFzKb8aMux1TLBWx6ZO",
			"charge": "ch_19dePlFzKb8aMux1R0QUMP3T",
			"created": 1484826640,
			"currency": "thb",
			"metadata": {
				"Credit Memo": "RET-1-00030",
				"Invoice": "INV-00121",
				"Negative Adjustment (THB)": "359.6",
				"Negative Adjustment (USD)": "10"
			},
			"reason": "requested_by_customer",
			"receipt_number": null,
			"status": "succeeded"
		}
	 * Ключи ответа можно читать двояко:
	 * $response['balance_transaction']
	 * $response->{'balance_transaction'}
	 * @override
	 * @see \Df\StripeClone\Method::apiTransId()
	 * @used-by \Df\StripeClone\Method::_refund()
	 * @param object $response
	 * @return string
	 */
	final protected function apiTransId($response) {return $response['balance_transaction'];}

	/**
	 * 2016-12-28
	 * @override
	 * @see \Df\Payment\Method::convertException()
	 * @used-by \Df\Payment\Method::action()
	 * @param \Exception|EStripeLib $e
	 * @return \Exception
	 */
	final protected function convertException(\Exception $e) {return
		$e instanceof EStripeLib ? new Exception($e) : $e
	;}

	/**
	 * 2016-12-27
	 * @override
	 * @see \Df\StripeClone\Method::responseToArray()
	 * @used-by \Df\StripeClone\Method::transInfo()
	 * @param StripeObject $response
	 * @return array(string => mixed)
	 */
	final protected function responseToArray($response) {return $response->getLastResponse()->json;}

	/**
	 * 2017-01-19
	 * Пример ответа:
	 * df_json_encode_pretty($result->getLastResponse()->json)
		{
			"id": "re_19deRAFzKb8aMux1eZEp32cX",
			"object": "refund",
			"amount": 269700,
			"balance_transaction": "txn_19deRAFzKb8aMux1TLBWx6ZO",
			"charge": "ch_19dePlFzKb8aMux1R0QUMP3T",
			"created": 1484826640,
			"currency": "thb",
			"metadata": {
				"Credit Memo": "RET-1-00030",
				"Invoice": "INV-00121",
				"Negative Adjustment (THB)": "359.6",
				"Negative Adjustment (USD)": "10"
			},
			"reason": "requested_by_customer",
			"receipt_number": null,
			"status": "succeeded"
		}
	 * Ключи ответа можно читать двояко:
	 * $result['balance_transaction']
	 * $result->{'balance_transaction'}
	 * https://stripe.com/docs/api#create_refund
	 * @override
	 * @see \Df\StripeClone\Method::scRefund()
	 * @used-by \Df\StripeClone\Method::_refund()
	 * @param string $chargeId
	 * @param float|null $amount
	 * В формате и валюте платёжной системы.
	 * Значение готово для применения в запросе API.
	 * @return \Stripe\Refund
	 */
	final protected function scRefund($chargeId, $amount) {
		/** @var CM|null $cm */
		$cm = $this->ii()->getCreditmemo();
		// 2016-03-24
		// Credit Memo и Invoice отсутствуют в сценарии Authorize / Capture
		// и присутствуют в сценарии Capture / Refund.
		if (!$cm) {
			$metadata = [];
		}
		else {
			/** @var Invoice $invoice */
			$invoice = $cm->getInvoice();
			$metadata = df_clean([
				'Comment' => $cm->getCustomerNote()
				,'Credit Memo' => $cm->getIncrementId()
				,'Invoice' => $invoice->getIncrementId()
			])
				+ $this->metaAdjustments($cm, 'positive')
				+ $this->metaAdjustments($cm, 'negative')
			;
		}
		return \Stripe\Refund::create(df_clean([
			// 2016-03-17
			// https://stripe.com/docs/api#create_refund-amount
			'amount' => $amount
			/**
			 * 2016-03-18
			 * Хитрый трюк,
			 * который позволяет нам не заниматься хранением идентификаторов платежей.
			 * Система уже хранит их в виде «ch_17q00rFzKb8aMux1YsSlBIlW-capture»,
			 * а нам нужно лишь отсечь суффиксы (Stripe не использует символ «-»).
			 */
			,'charge' => $chargeId
			// 2016-03-17
			// https://stripe.com/docs/api#create_refund-metadata
			,'metadata' => $metadata
			// 2016-03-18
			// https://stripe.com/docs/api#create_refund-reason
			,'reason' => 'requested_by_customer'
		]));
	}

	/**
	 * 2017-01-19
	 * @override
	 * @see \Df\StripeClone\Method::scVoid()
	 * @used-by \Df\StripeClone\Method::_refund()
	 * @param string $chargeId
	 * @return \Stripe\Refund
	 */
	final protected function scVoid($chargeId) {return $this->scRefund($chargeId, null);}

	/**
	 * 2016-12-26
	 * Хотя Stripe использует для страниц транзакций адреса вида
	 * https://dashboard.stripe.com/test/payments/<id>
	 * адрес без части «test» также успешно работает (даже в тестовом режиме).
	 * Использую именно такие адреса, потому что я не знаю,
	 * какова часть вместо «test» в промышленном режиме.
	 * @override
	 * @see \Df\StripeClone\Method::transUrlBase()
	 * @used-by \Df\StripeClone\Method::transUrl()
	 * @param T $t
	 * @return string
	 */
	final protected function transUrlBase(T $t) {return 'https://dashboard.stripe.com/payments';}

	/**
	 * 2016-03-18
	 * @param CM $cm
	 * @param string $type
	 * @return array(string => float)
	 */
	private function metaAdjustments(CM $cm, $type) {
		/** @var string $iso3Base */
		$iso3Base = $cm->getBaseCurrencyCode();
		/** @var string $iso3 */
		$iso3 = $cm->getOrderCurrencyCode();
		/** @var bool $multiCurrency */
		$multiCurrency = $iso3Base !== $iso3;
		/**
		 * 2016-03-18
		 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::ADJUSTMENT_POSITIVE
		 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L32-L35
		 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::ADJUSTMENT_NEGATIVE
		 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L72-L75
		 */
		/** @var string $key */
		$key = 'adjustment_' . $type;
		/** @var float $a */
		$a = $cm[$key];
		/** @var string $label */
		$label = ucfirst($type) . ' Adjustment';
		return !$a ? [] : (
			!$multiCurrency
			? [$label => $a]
			: [
				"{$label} ({$iso3})" => $a
				/**
				 * 2016-03-18
				 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::BASE_ADJUSTMENT_POSITIVE
				 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L112-L115
				 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::BASE_ADJUSTMENT_NEGATIVE
				 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L56-L59
				 */
				,"{$label} ({$iso3Base})" => $cm['base_' . $key]
			]
		);
	}
}