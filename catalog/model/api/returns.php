<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Returns extends \Opencart\System\Engine\Model {
	public function getReturn(int $return_id): array {
		$query = $this->db->query("SELECT r.return_id, r.order_id, r.customer_id, r.firstname, r.lastname, r.email, r.telephone, r.product, r.model, r.quantity, r.opened, r.comment, r.return_status_id, (SELECT rs.name FROM `" . DB_PREFIX . "return_status` rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS return_status, r.date_added, r.date_modified FROM `" . DB_PREFIX . "return` r WHERE r.return_id = '" . (int)$return_id . "' LIMIT 1");

		$return_info = $query->row;

		if ($return_info) {
			$return_info['histories'] = $this->getHistories($return_id, 1, 1000);
		}

		return $return_info;
	}

	public function getReturns(array $data = []): array {
		$sql = "SELECT r.return_id, r.order_id, CONCAT(r.firstname, ' ', r.lastname) AS customer, r.product, r.model, r.return_status_id, (SELECT rs.name FROM `" . DB_PREFIX . "return_status` rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS return_status, r.date_added, r.date_modified FROM `" . DB_PREFIX . "return` r WHERE 1";

		if (isset($data['filter_order_id']) && $data['filter_order_id'] !== null) {
			$sql .= " AND r.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND LCASE(CONCAT(r.firstname, ' ', r.lastname)) LIKE '" . $this->db->escape(oc_strtolower($data['filter_customer']) . '%') . "'";
		}

		if (!empty($data['filter_product'])) {
			$sql .= " AND LCASE(r.product) LIKE '" . $this->db->escape(oc_strtolower($data['filter_product']) . '%') . "'";
		}

		if (isset($data['filter_return_status_id']) && $data['filter_return_status_id'] !== null) {
			$sql .= " AND r.return_status_id = '" . (int)$data['filter_return_status_id'] . "'";
		}

		$sort_data = ['r.return_id', 'r.order_id', 'customer', 'r.product', 'r.model', 'return_status', 'r.date_added', 'r.date_modified'];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.return_id";
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

	public function getTotalReturns(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return` r WHERE 1";

		if (isset($data['filter_order_id']) && $data['filter_order_id'] !== null) {
			$sql .= " AND r.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND LCASE(CONCAT(r.firstname, ' ', r.lastname)) LIKE '" . $this->db->escape(oc_strtolower($data['filter_customer']) . '%') . "'";
		}

		if (!empty($data['filter_product'])) {
			$sql .= " AND LCASE(r.product) LIKE '" . $this->db->escape(oc_strtolower($data['filter_product']) . '%') . "'";
		}

		if (isset($data['filter_return_status_id']) && $data['filter_return_status_id'] !== null) {
			$sql .= " AND r.return_status_id = '" . (int)$data['filter_return_status_id'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function addHistory(int $return_id, int $return_status_id, string $comment = '', bool $notify = false): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "return` SET return_status_id = '" . (int)$return_status_id . "', date_modified = NOW() WHERE return_id = '" . (int)$return_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "return_history` SET return_id = '" . (int)$return_id . "', return_status_id = '" . (int)$return_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape(strip_tags($comment)) . "', date_added = NOW()");
	}

	public function getHistories(int $return_id, int $page = 1, int $limit = 20): array {
		$start = ($page - 1) * $limit;

		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT rh.return_history_id, rh.return_id, rh.return_status_id, (SELECT rs.name FROM `" . DB_PREFIX . "return_status` rs WHERE rs.return_status_id = rh.return_status_id AND rs.language_id = '" . (int)$this->config->get('config_language_id') . "') AS return_status, rh.notify, rh.comment, rh.date_added FROM `" . DB_PREFIX . "return_history` rh WHERE rh.return_id = '" . (int)$return_id . "' ORDER BY rh.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalHistories(int $return_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "return_history` WHERE return_id = '" . (int)$return_id . "'");

		return (int)$query->row['total'];
	}
}
