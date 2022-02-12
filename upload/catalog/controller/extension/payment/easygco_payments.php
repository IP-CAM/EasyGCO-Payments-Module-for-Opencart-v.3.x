<?php
/*
 * EasyGCO Payments Module for OpenCart
 *
 * @copyright Copyright (c) EasyGCO.com
 * 
 * @author   EasyGCO ( easygco.com )
 * @version  1.0.0
 */
class ControllerExtensionPaymentEasyGCOPayments extends Controller {
	public function index() {
		
		$this->load->language('extension/payment/easygco_payments');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if (!$order_info) return null;
			
		$data['amount_total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);

		if($data['amount_total'] <= 0) return null;

		$data['currency_code'] = $order_info['currency_code'];
		$data['invoice'] = $this->session->data['order_id'] . ' - ' . $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];

		$data['success_url'] = $this->url->link('checkout/success');
		$data['notify_url'] = $this->url->link('extension/payment/easygco_payments/callback', '', true);
		$data['cancel_return'] = $this->url->link('checkout/checkout', '', true);
		$data['order_id'] = $this->session->data['order_id'];

		$apiKey = $this->config->get('payment_easygco_payments_api_key');
		$apiSecret = $this->config->get('payment_easygco_payments_api_secret');

		try {
			require_once(__DIR__ . '/easygco_payments/vendor/autoload.php');
			$ePaymentsClient = new EasyGCO\EasyGCOPayments\API($apiKey,$apiSecret);
		} catch(\Exception $e) {
			if ($this->config->get('payment_easygco_payments_debug')) {
				$this->log->write('EasyGCO Payments :: ' . $e->getMessage());
			}
			return null;
		}

		$apiPath = 'token/generate';
	
		$inputData = [
			'transaction_id' 	=> $this->session->data['order_id'],
			'description' 		=> $data['invoice'],
			'code' 				=> $data['currency_code'],
			'type' 				=> 'fiat',
			'amount' 			=> 	$data['amount_total'],
			"return_url"		=>	$data['cancel_return'],
			"notify_url"		=>	$data['notify_url'],
			"success_url"		=>	$data['success_url'],
			"cancel_url"		=>	$data['cancel_return'],
		];
	
		try {
			$apiResponse = $ePaymentsClient->doRequest($apiPath, $inputData);
			if(!$ePaymentsClient->isSuccess($apiResponse)) {
				if ($this->config->get('payment_easygco_payments_debug')) {
					$this->log->write('EasyGCO Payments :: ' . $ePaymentsClient->getMessage($apiResponse));
				}
				return null;
			}
		} catch(\Exception $e) {
			if ($this->config->get('payment_easygco_payments_debug')) {
				$this->log->write('EasyGCO Payments :: ' . $e->getMessage());
			}
			return null;
		}
	
		$responseData = $ePaymentsClient->getData($apiResponse);
		
		$data['action'] = $responseData['url'];
		$htmlOutput = '
				<form action="' . $data['action']. '" method="post">
					<div class="d-block w-100 text-center">
					<h1>'.$this->language->get('text_title').'</h1>
					<p class="lead">'.$this->language->get('text_visit').'</p>
					</div>
					<div class="buttons">
					<div class="text-center">
						<button type="submit" class="btn btn-primary btn-lg">'.$this->language->get('button_confirm').'</button>
					</div>
					</div>
				</form>
	  	';
		return $htmlOutput;
	}

	public function callback() {

		if (!isset($this->request->post['ps_response_data']) || !is_array($this->request->post['ps_response_data']))
			return null;
		
		$apiResponseData = $this->request->post['ps_response_data'];

		if(!isset($apiResponseData['payment_uid'], $apiResponseData['p_transaction_id'])) return null;

		$paymentUID = $apiResponseData['payment_uid'];
		$order_id = $apiResponseData['p_transaction_id'];

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);
		if (!$order_info) return null;

		$apiKey = $this->config->get('payment_easygco_payments_api_key');
		$apiSecret = $this->config->get('payment_easygco_payments_api_secret');

		try {
			require_once(__DIR__ . '/easygco_payments/vendor/autoload.php');
			$ePaymentsClient = new EasyGCO\EasyGCOPayments\API($apiKey,$apiSecret);
		} catch(\Exception $e) {
			if ($this->config->get('payment_easygco_payments_debug')) {
				$this->log->write('EasyGCO Payments :: ' . $e->getMessage());
			}
			return null;
		}

		$inputData = [
			'uid' => trim(urldecode($paymentUID)),
		];
		
		$apiPath = 'payment/get';
	
		try {
			$apiResponse = $ePaymentsClient->doRequest($apiPath, $inputData);
			if(!$ePaymentsClient->isSuccess($apiResponse)) {
				if ($this->config->get('payment_easygco_payments_debug')) {
					$this->log->write('EasyGCO Payments :: ' . $ePaymentsClient->getMessage($apiResponse));
				}
				return null;
			}
		} catch(\Exception $e) {
			if ($this->config->get('payment_easygco_payments_debug')) {
				$this->log->write('EasyGCO Payments :: ' . $e->getMessage());
			}
			return null;
		}

		$responseData = $ePaymentsClient->getData($apiResponse);

		if(!isset($responseData['success']) || intval($responseData['success']) !== 1 || !isset($responseData['status'])) return null;


		if (empty($responseData['status']) || !is_string($responseData['status'])) {
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
			return null;
		}

		$order_status_id = $this->config->get('config_order_status_id');
		$paidAmount = number_format($responseData['p_amount'], 8, '.', '');

		switch(str_replace(' ','_',strtolower($responseData['status']))) {
			case 'waiting':
				$order_status_id = $this->config->get('payment_easygco_payments_waiting_status_id');
				break;
			case 'partially_paid':
				$order_status_id = $this->config->get('payment_easygco_payments_partially_paid_status_id');
				break;
			case 'paid':
					$order_status_id = $this->config->get('payment_easygco_payments_paid_status_id');
					$this->model_checkout_order->addOrderHistory($order_id, $order_status_id, "Payment Received: " . (float) $paidAmount . " " . $order_info['currency_code'] . " [ EasyGCO - #" . $paymentUID . "]", true);
					return true;
				break;
			case 'overpaid':
					$order_status_id = $this->config->get('payment_easygco_payments_overpaid_status_id');
					$this->model_checkout_order->addOrderHistory($order_id, $order_status_id, "Payment Received: " . (float) $paidAmount . " " . $order_info['currency_code'] . " [ EasyGCO - #" . $paymentUID . "]", true);
					return true;
				break;
			case 'unpaid':
				$order_status_id = $this->config->get('payment_easygco_payments_unpaid_status_id');
				break;
			case 'failed':
				$order_status_id = $this->config->get('payment_easygco_payments_failed_status_id');
				break;
		}

		$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
	}
}