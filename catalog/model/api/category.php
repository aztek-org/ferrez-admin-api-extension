<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Category extends \Opencart\System\Engine\Model {
	public function existsCategory(int $category_id): bool {
		$query = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "category` WHERE category_id = '" . (int)$category_id . "' LIMIT 1");

		return !empty($query->row);
	}

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

	public function createCategory(array $data): int {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id') ?: 1);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "category` SET image = '" . $this->db->escape((string)($data['image'] ?? '')) . "', parent_id = '" . (int)($data['parent_id'] ?? 0) . "', `top` = '" . (!empty($data['top']) ? 1 : 0) . "', `column` = '" . (int)($data['column'] ?? 1) . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "', status = '" . (!empty($data['status']) ? 1 : 0) . "', date_added = NOW(), date_modified = NOW()");

		$category_id = (int)$this->db->getLastId();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_description` SET category_id = '" . $category_id . "', language_id = '" . $language_id . "', name = '" . $this->db->escape((string)($data['name'] ?? '')) . "', description = '" . $this->db->escape((string)($data['description'] ?? '')) . "', meta_title = '" . $this->db->escape((string)($data['meta_title'] ?? ($data['name'] ?? ''))) . "', meta_description = '" . $this->db->escape((string)($data['meta_description'] ?? '')) . "', meta_keyword = '" . $this->db->escape((string)($data['meta_keyword'] ?? '')) . "'");

		foreach ($this->normalizeStoreIds((array)($data['store_ids'] ?? [0])) as $store_id) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET category_id = '" . $category_id . "', store_id = '" . (int)$store_id . "'");
		}

		return $category_id;
	}

	public function updateCategory(int $category_id, array $data): void {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id') ?: 1);

		$this->db->query("UPDATE `" . DB_PREFIX . "category` SET image = '" . $this->db->escape((string)($data['image'] ?? '')) . "', parent_id = '" . (int)($data['parent_id'] ?? 0) . "', `top` = '" . (!empty($data['top']) ? 1 : 0) . "', `column` = '" . (int)($data['column'] ?? 1) . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "', status = '" . (!empty($data['status']) ? 1 : 0) . "', date_modified = NOW() WHERE category_id = '" . (int)$category_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_description` WHERE category_id = '" . (int)$category_id . "' AND language_id = '" . $language_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_description` SET category_id = '" . (int)$category_id . "', language_id = '" . $language_id . "', name = '" . $this->db->escape((string)($data['name'] ?? '')) . "', description = '" . $this->db->escape((string)($data['description'] ?? '')) . "', meta_title = '" . $this->db->escape((string)($data['meta_title'] ?? ($data['name'] ?? ''))) . "', meta_description = '" . $this->db->escape((string)($data['meta_description'] ?? '')) . "', meta_keyword = '" . $this->db->escape((string)($data['meta_keyword'] ?? '')) . "'");

		if (array_key_exists('store_ids', $data)) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "category_to_store` WHERE category_id = '" . (int)$category_id . "'");

			foreach ($this->normalizeStoreIds((array)$data['store_ids']) as $store_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}
	}

	public function deleteCategory(int $category_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category` WHERE category_id = '" . (int)$category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_description` WHERE category_id = '" . (int)$category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_to_store` WHERE category_id = '" . (int)$category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE category_id = '" . (int)$category_id . "'");
	}

	private function normalizeStoreIds(array $store_ids): array {
		$ids = [];

		foreach ($store_ids as $store_id) {
			$value = (int)$store_id;

			if ($value >= 0) {
				$ids[$value] = $value;
			}
		}

		if (!$ids) {
			$ids[0] = 0;
		}

		return array_values($ids);
	}
}
