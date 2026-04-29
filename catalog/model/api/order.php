<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Order extends \Opencart\System\Engine\Model {
	public function getOrder(int $order_id): array {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info || (int)$order_info['order_status_id'] <= 0) {
			return [];
		}

		$order_info['histories'] = $this->getHistories($order_id, 1, 1000);

		return $order_info;
	}

	public function getOrders(array $data = []): array {
		$sql = "SELECT o.order_id, o.store_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, o.email, o.order_status_id, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o WHERE o.order_status_id > '0'";

		if (isset($data['filter_store_id']) && $data['filter_store_id'] !== null) {
			$sql .= " AND o.store_id = '" . (int)$data['filter_store_id'] . "'";
		}

		if (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== null) {
			$sql .= " AND o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND LCASE(CONCAT(o.firstname, ' ', o.lastname)) LIKE '" . $this->db->escape('%' . oc_strtolower($data['filter_customer']) . '%') . "'";
		}

		if (!empty($data['filter_email'])) {
			$sql .= " AND LCASE(o.email) LIKE '" . $this->db->escape('%' . oc_strtolower($data['filter_email']) . '%') . "'";
		}

		if (!empty($data['filter_date_from'])) {
			$sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape((string)$data['filter_date_from']) . "')";
		}

		if (!empty($data['filter_date_to'])) {
			$sql .= " AND DATE(o.date_added) <= DATE('" . $this->db->escape((string)$data['filter_date_to']) . "')";
		}

		$sort_data = [
			'o.order_id',
			'customer',
			'order_status',
			'o.total',
			'o.date_added',
			'o.date_modified'
		];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
		}

		$sql .= ($data['order'] === 'ASC') ? " ASC" : " DESC";

		$start = ((int)$data['page'] - 1) * (int)$data['limit'];
		if ($start < 0) {
			$start = 0;
		}

		$sql .= " LIMIT " . (int)$start . "," . (int)$data['limit'];

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalOrders(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o WHERE o.order_status_id > '0'";

		if (isset($data['filter_store_id']) && $data['filter_store_id'] !== null) {
			$sql .= " AND o.store_id = '" . (int)$data['filter_store_id'] . "'";
		}

		if (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== null) {
			$sql .= " AND o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND LCASE(CONCAT(o.firstname, ' ', o.lastname)) LIKE '" . $this->db->escape('%' . oc_strtolower($data['filter_customer']) . '%') . "'";
		}

		if (!empty($data['filter_email'])) {
			$sql .= " AND LCASE(o.email) LIKE '" . $this->db->escape('%' . oc_strtolower($data['filter_email']) . '%') . "'";
		}

		if (!empty($data['filter_date_from'])) {
			$sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape((string)$data['filter_date_from']) . "')";
		}

		if (!empty($data['filter_date_to'])) {
			$sql .= " AND DATE(o.date_added) <= DATE('" . $this->db->escape((string)$data['filter_date_to']) . "')";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function getHistories(int $order_id, int $page = 1, int $limit = 20): array {
		$start = ($page - 1) * $limit;

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT oh.order_history_id, oh.order_id, oh.order_status_id, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = oh.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, oh.notify, oh.comment, oh.date_added FROM `" . DB_PREFIX . "order_history` oh WHERE oh.order_id = '" . (int)$order_id . "' ORDER BY oh.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalHistories(int $order_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");

		return (int)$query->row['total'];
	}
}
