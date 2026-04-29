<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class CustomerGroup extends \Opencart\System\Engine\Model {
	public function getCustomerGroup(int $customer_group_id): array {
		$language_id = (int)$this->config->get('config_language_id');

		if (!$language_id) {
			$language_id = 1;
		}

		$sql = "SELECT cg.customer_group_id, cg.approval, cg.sort_order, cgd.language_id, cgd.name, cgd.description FROM `" . DB_PREFIX . "customer_group` cg LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cg.customer_group_id = '" . (int)$customer_group_id . "' AND cgd.language_id = '" . $language_id . "'";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getCustomerGroups(array $data = []): array {
		$language_id = !empty($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id');

		if (!$language_id) {
			$language_id = 1;
		}

		$sql = "SELECT cg.customer_group_id, cg.approval, cg.sort_order, cgd.language_id, cgd.name, cgd.description FROM `" . DB_PREFIX . "customer_group` cg LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '" . $language_id . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND cgd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = [
			'cg.customer_group_id',
			'cgd.name',
			'cg.sort_order',
			'cg.approval'
		];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY cgd.name";
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

	public function getTotalCustomerGroups(array $data = []): int {
		$language_id = !empty($data['language_id']) ? (int)$data['language_id'] : (int)$this->config->get('config_language_id');

		if (!$language_id) {
			$language_id = 1;
		}

		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "customer_group` cg LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '" . $language_id . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND cgd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}