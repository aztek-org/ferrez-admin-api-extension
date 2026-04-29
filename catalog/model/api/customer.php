<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Customer extends \Opencart\System\Engine\Model {
	public function getCustomer(int $customer_id): array {
		$language_id = (int)$this->config->get('config_language_id');

		if (!$language_id) {
			$language_id = 1;
		}

		$sql = "SELECT c.customer_id, c.customer_group_id, c.store_id, c.language_id, c.firstname, c.lastname, c.email, c.telephone, c.status, c.safe, c.newsletter, c.ip, c.date_added, cgd.name AS customer_group FROM `" . DB_PREFIX . "customer` c LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (c.customer_group_id = cgd.customer_group_id AND cgd.language_id = '" . $language_id . "') WHERE c.customer_id = '" . (int)$customer_id . "'";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getCustomers(array $data = []): array {
		$language_id = (int)$this->config->get('config_language_id');

		if (!$language_id) {
			$language_id = 1;
		}

		$sql = "SELECT c.customer_id, c.customer_group_id, c.firstname, c.lastname, c.email, c.telephone, c.status, c.safe, c.newsletter, c.date_added, cgd.name AS customer_group FROM `" . DB_PREFIX . "customer` c LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (c.customer_group_id = cgd.customer_group_id AND cgd.language_id = '" . $language_id . "') WHERE 1";

		if (!empty($data['filter_firstname'])) {
			$sql .= " AND c.firstname LIKE '" . $this->db->escape($data['filter_firstname']) . "%'";
		}

		if (!empty($data['filter_lastname'])) {
			$sql .= " AND c.lastname LIKE '" . $this->db->escape($data['filter_lastname']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$sql .= " AND c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if ($data['filter_status'] !== null) {
			$sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = [
			'c.customer_id',
			'c.firstname',
			'c.lastname',
			'c.email',
			'customer_group',
			'c.status',
			'c.date_added'
		];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.customer_id";
		}

		$sql .= ($data['order'] === 'DESC') ? " DESC" : " ASC";

		$start = ((int)$data['page'] - 1) * (int)$data['limit'];
		if ($start < 0) {
			$start = 0;
		}

		$sql .= " LIMIT " . (int)$start . "," . (int)$data['limit'];

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalCustomers(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "customer` c WHERE 1";

		if (!empty($data['filter_firstname'])) {
			$sql .= " AND c.firstname LIKE '" . $this->db->escape($data['filter_firstname']) . "%'";
		}

		if (!empty($data['filter_lastname'])) {
			$sql .= " AND c.lastname LIKE '" . $this->db->escape($data['filter_lastname']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$sql .= " AND c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if ($data['filter_status'] !== null) {
			$sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}
