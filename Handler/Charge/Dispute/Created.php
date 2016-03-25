<?php
namespace Dfe\Stripe\Handler\Charge\Dispute;
use Dfe\Stripe\Handler\Charge\Dispute;
// 2016-03-25
// https://stripe.com/docs/api#event_types-charge.dispute.created
// Occurs whenever a customer disputes a charge with their bank (chargeback).
class Created extends Dispute {
	/**
	 * 2016-03-25
	 * @override
	 * @see \Dfe\Stripe\Handler::_process()
	 * @used-by \Dfe\Stripe\Handler::process()
	 * @return mixed
	 */
	protected function process() {
		/** @var int $paymentId */
		//$paymentId = df_fetch_one('sales_payment_transaction', 'payment_id', [
		//	'txn_id' => dfa_deep($request, 'data/object/id')
		//]);
		return __CLASS__;
	}
}