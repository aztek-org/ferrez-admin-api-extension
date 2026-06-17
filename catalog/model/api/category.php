<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Category extends \Opencart\System\Engine\Model {
	public function getCategory(int $category_id, int $store_id): array {
		$sql = "SELECT c.category_id, cd.name, cd.description, c.image, c.parent_id, c.sort_order, c.status FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND c2s.store_id = '" . (int)$store_id . "' LIMIT 1";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getCategories(array $data = []): array {
		$sql = "SELECT c.category_id, cd.name, c.parent_id, c.sort_order, c.status FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c.category_id = c2s.category_id) WHERE c2s.store_id = '" . (int)$data['store_id'] . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND cd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if ($data['filter_status'] !== null) {
			$sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = [
			'c.category_id',
			'cd.name',
			'c.parent_id',
			'c.sort_order',
			'c.status'
		];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.category_id";
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

	public function getTotalCategories(array $data = []): int {
		$sql = "SELECT COUNT(DISTINCT c.category_id) AS total FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c.category_id = c2s.category_id) WHERE c2s.store_id = '" . (int)$data['store_id'] . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND cd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if ($data['filter_status'] !== null) {
			$sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}
