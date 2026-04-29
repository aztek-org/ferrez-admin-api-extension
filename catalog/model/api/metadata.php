<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Metadata extends \Opencart\System\Engine\Model {
	public function getStores(): array {
		$this->load->model('setting/store');

		$stores = [[
			'store_id' => 0,
			'name' => (string)$this->config->get('config_name'),
			'url' => (string)$this->config->get('config_url'),
			'ssl' => (string)$this->config->get('config_ssl')
		]];

		foreach ($this->model_setting_store->getStores() as $row) {
			$stores[] = $row;
		}

		return $stores;
	}

	public function getLanguages(): array {
		$query = $this->db->query("SELECT language_id, name, code, locale, status, sort_order FROM `" . DB_PREFIX . "language` ORDER BY sort_order ASC, name ASC");

		return $query->rows;
	}

	public function getCurrencies(): array {
		$query = $this->db->query("SELECT currency_id, title, code, symbol_left, symbol_right, decimal_place, value, status FROM `" . DB_PREFIX . "currency` ORDER BY title ASC");

		return $query->rows;
	}

	public function getOrderStatuses(): array {
		$query = $this->db->query("SELECT order_status_id, language_id, name FROM `" . DB_PREFIX . "order_status` WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC");

		return $query->rows;
	}

	public function getReturnStatuses(): array {
		$query = $this->db->query("SELECT return_status_id, language_id, name FROM `" . DB_PREFIX . "return_status` WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC");

		return $query->rows;
	}
}