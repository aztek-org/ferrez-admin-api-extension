<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Manufacturer extends \Opencart\System\Engine\Model {
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
}
