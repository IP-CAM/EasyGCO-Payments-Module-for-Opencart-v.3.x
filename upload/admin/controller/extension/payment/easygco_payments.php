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
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/easygco_payments');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_easygco_payments', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['api_key'])) {
			$data['error_api_key'] = $this->error['api_key'];
		} else {
			$data['error_api_key'] = '';
		}

		if (isset($this->error['api_secret'])) {
			$data['error_api_secret'] = $this->error['api_secret'];
		} else {
			$data['error_api_secret'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/easygco_payments', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/easygco_payments', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_easygco_payments_api_key'])) {
			$data['payment_easygco_payments_api_key'] = $this->request->post['payment_easygco_payments_api_key'];
		} else {
			$data['payment_easygco_payments_api_key'] = $this->config->get('payment_easygco_payments_api_key');
		}

		if (isset($this->request->post['payment_easygco_payments_api_secret'])) {
			$data['payment_easygco_payments_api_secret'] = $this->request->post['payment_easygco_payments_api_secret'];
		} else {
			$data['payment_easygco_payments_api_secret'] = $this->config->get('payment_easygco_payments_api_secret');
		}

		if (isset($this->request->post['payment_easygco_payments_debug'])) {
			$data['payment_easygco_payments_debug'] = $this->request->post['payment_easygco_payments_debug'];
		} else {
			$data['payment_easygco_payments_debug'] = $this->config->get('payment_easygco_payments_debug');
		}

		if (isset($this->request->post['payment_easygco_payments_total'])) {
			$data['payment_easygco_payments_total'] = $this->request->post['payment_easygco_payments_total'];
		} else {
			$data['payment_easygco_payments_total'] = $this->config->get('payment_easygco_payments_total');
		}

		if (isset($this->request->post['payment_easygco_payments_paid_status_id'])) {
			$data['payment_easygco_payments_paid_status_id'] = $this->request->post['payment_easygco_payments_paid_status_id'];
		} else {
			$data['payment_easygco_payments_paid_status_id'] = $this->config->get('payment_easygco_payments_paid_status_id');
		}

		if (isset($this->request->post['payment_easygco_payments_overpaid_status_id'])) {
			$data['payment_easygco_payments_overpaid_status_id'] = $this->request->post['payment_easygco_payments_overpaid_status_id'];
		} else {
			$data['payment_easygco_payments_overpaid_status_id'] = $this->config->get('payment_easygco_payments_overpaid_status_id');
		}

		if (isset($this->request->post['payment_easygco_payments_partially_paid_status_id'])) {
			$data['payment_easygco_payments_partially_paid_status_id'] = $this->request->post['payment_easygco_payments_partially_paid_status_id'];
		} else {
			$data['payment_easygco_payments_partially_paid_status_id'] = $this->config->get('payment_easygco_payments_partially_paid_status_id');
		}

		if (isset($this->request->post['payment_easygco_payments_waiting_status_id'])) {
			$data['payment_easygco_payments_waiting_status_id'] = $this->request->post['payment_easygco_payments_waiting_status_id'];
		} else {
			$data['payment_easygco_payments_waiting_status_id'] = $this->config->get('payment_easygco_payments_waiting_status_id');
		}

		if (isset($this->request->post['payment_easygco_payments_unpaid_status_id'])) {
			$data['payment_easygco_payments_unpaid_status_id'] = $this->request->post['payment_easygco_payments_unpaid_status_id'];
		} else {
			$data['payment_easygco_payments_unpaid_status_id'] = $this->config->get('payment_easygco_payments_unpaid_status_id');
		}

		if (isset($this->request->post['payment_easygco_payments_failed_status_id'])) {
			$data['payment_easygco_payments_failed_status_id'] = $this->request->post['payment_easygco_payments_failed_status_id'];
		} else {
			$data['payment_easygco_payments_failed_status_id'] = $this->config->get('payment_easygco_payments_failed_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_easygco_payments_geo_zone_id'])) {
			$data['payment_easygco_payments_geo_zone_id'] = $this->request->post['payment_easygco_payments_geo_zone_id'];
		} else {
			$data['payment_easygco_payments_geo_zone_id'] = $this->config->get('payment_easygco_payments_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_easygco_payments_status'])) {
			$data['payment_easygco_payments_status'] = $this->request->post['payment_easygco_payments_status'];
		} else {
			$data['payment_easygco_payments_status'] = $this->config->get('payment_easygco_payments_status');
		}

		if (isset($this->request->post['payment_easygco_payments_sort_order'])) {
			$data['payment_easygco_payments_sort_order'] = $this->request->post['payment_easygco_payments_sort_order'];
		} else {
			$data['payment_easygco_payments_sort_order'] = $this->config->get('payment_easygco_payments_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/easygco_payments', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/easygco_payments')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_easygco_payments_api_key']) {
			$this->error['api_key'] = $this->language->get('error_api_key');
		}

		if (!$this->request->post['payment_easygco_payments_api_secret']) {
			$this->error['api_secret'] = $this->language->get('error_api_secret');
		}

		return !$this->error;
	}
}