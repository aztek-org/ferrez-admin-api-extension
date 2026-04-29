<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Manufacturer extends \Opencart\System\Engine\Model {
	public function existsManufacturer(int $manufacturer_id): bool {
		$query = $this->db->query("SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = '" . (int)$manufacturer_id . "' LIMIT 1");

		return !empty($query->row);
	}

	public function getManufacturer(int $manufacturer_id, int $store_id = 0): array {
		$sql = "SELECT m.manufacturer_id, m.name, m.image, m.sort_order FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m.manufacturer_id = '" . (int)$manufacturer_id . "' AND m2s.store_id = '" . (int)$store_id . "'";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getManufacturers(array $data = []): array {
		$sql = "SELECT m.manufacturer_id, m.name, m.image, m.sort_order FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$data['store_id'] . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND m.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = [
			'm.manufacturer_id',
			'm.name',
			'm.sort_order'
		];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY m.manufacturer_id";
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

	public function getTotalManufacturers(array $data = []): int {
		$sql = "SELECT COUNT(DISTINCT m.manufacturer_id) AS total FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m2s.store_id = '" . (int)$data['store_id'] . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND m.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function createManufacturer(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer` SET name = '" . $this->db->escape((string)($data['name'] ?? '')) . "', image = '" . $this->db->escape((string)($data['image'] ?? '')) . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "'");

		$manufacturer_id = (int)$this->db->getLastId();

		foreach ($this->normalizeStoreIds((array)($data['store_ids'] ?? [0])) as $store_id) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET manufacturer_id = '" . $manufacturer_id . "', store_id = '" . (int)$store_id . "'");
		}

		return $manufacturer_id;
	}

	public function updateManufacturer(int $manufacturer_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "manufacturer` SET name = '" . $this->db->escape((string)($data['name'] ?? '')) . "', image = '" . $this->db->escape((string)($data['image'] ?? '')) . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "' WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

		if (array_key_exists('store_ids', $data)) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

			foreach ($this->normalizeStoreIds((array)$data['store_ids']) as $store_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$store_id . "'");
			}
		}
	}

	public function deleteManufacturer(int $manufacturer_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
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
