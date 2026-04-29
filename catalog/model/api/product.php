<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Product extends \Opencart\System\Engine\Model {
	public function existsProduct(int $product_id): bool {
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "' LIMIT 1");

		return !empty($query->row);
	}

	public function getProduct(int $product_id, int $store_id): array {
		$sql = "SELECT p.product_id, pd.name, pd.description, p.model, p.sku, p.upc, p.ean, p.jan, p.isbn, p.mpn, p.image, p.price, p.quantity, p.minimum, p.subtract, p.stock_status_id, p.shipping, p.weight, p.weight_class_id, p.length, p.width, p.height, p.length_class_id, p.status, p.date_added, p.date_modified FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND p2s.store_id = '" . (int)$store_id . "' LIMIT 1";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getProducts(array $data = []): array {
		$sql = "SELECT p.product_id, pd.name, p.model, p.price, p.quantity, p.status, p.date_modified FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p2s.store_id = '" . (int)$data['store_id'] . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
		}

		if ($data['filter_status'] !== null) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = [
			'p.product_id',
			'pd.name',
			'p.model',
			'p.price',
			'p.quantity',
			'p.status',
			'p.date_modified'
		];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY p.product_id";
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

	public function getTotalProducts(array $data = []): int {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE p2s.store_id = '" . (int)$data['store_id'] . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
		}

		if ($data['filter_status'] !== null) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function createProduct(array $data): int {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id') ?: 1);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "product` SET model = '" . $this->db->escape((string)($data['model'] ?? '')) . "', sku = '" . $this->db->escape((string)($data['sku'] ?? '')) . "', upc = '" . $this->db->escape((string)($data['upc'] ?? '')) . "', ean = '" . $this->db->escape((string)($data['ean'] ?? '')) . "', jan = '" . $this->db->escape((string)($data['jan'] ?? '')) . "', isbn = '" . $this->db->escape((string)($data['isbn'] ?? '')) . "', mpn = '" . $this->db->escape((string)($data['mpn'] ?? '')) . "', location = '" . $this->db->escape((string)($data['location'] ?? '')) . "', quantity = '" . (int)($data['quantity'] ?? 0) . "', stock_status_id = '" . (int)($data['stock_status_id'] ?? 0) . "', image = '" . $this->db->escape((string)($data['image'] ?? '')) . "', manufacturer_id = '" . (int)($data['manufacturer_id'] ?? 0) . "', shipping = '" . (!empty($data['shipping']) ? 1 : 0) . "', price = '" . (float)($data['price'] ?? 0) . "', points = '" . (int)($data['points'] ?? 0) . "', tax_class_id = '" . (int)($data['tax_class_id'] ?? 0) . "', date_available = '" . $this->db->escape((string)($data['date_available'] ?? date('Y-m-d'))) . "', weight = '" . (float)($data['weight'] ?? 0) . "', weight_class_id = '" . (int)($data['weight_class_id'] ?? 0) . "', length = '" . (float)($data['length'] ?? 0) . "', width = '" . (float)($data['width'] ?? 0) . "', height = '" . (float)($data['height'] ?? 0) . "', length_class_id = '" . (int)($data['length_class_id'] ?? 0) . "', subtract = '" . (!empty($data['subtract']) ? 1 : 0) . "', minimum = '" . (int)($data['minimum'] ?? 1) . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "', status = '" . (!empty($data['status']) ? 1 : 0) . "', viewed = '0', date_added = NOW(), date_modified = NOW()");

		$product_id = (int)$this->db->getLastId();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` SET product_id = '" . $product_id . "', language_id = '" . $language_id . "', name = '" . $this->db->escape((string)($data['name'] ?? '')) . "', description = '" . $this->db->escape((string)($data['description'] ?? '')) . "', tag = '" . $this->db->escape((string)($data['tag'] ?? '')) . "', meta_title = '" . $this->db->escape((string)($data['meta_title'] ?? ($data['name'] ?? ''))) . "', meta_description = '" . $this->db->escape((string)($data['meta_description'] ?? '')) . "', meta_keyword = '" . $this->db->escape((string)($data['meta_keyword'] ?? '')) . "'");

		foreach ($this->normalizeStoreIds((array)($data['store_ids'] ?? [0])) as $store_id) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET product_id = '" . $product_id . "', store_id = '" . (int)$store_id . "'");
		}

		foreach ($this->normalizeIds((array)($data['category_ids'] ?? [])) as $category_id) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . $product_id . "', category_id = '" . (int)$category_id . "'");
		}

		return $product_id;
	}

	public function updateProduct(int $product_id, array $data): void {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id') ?: 1);

		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET model = '" . $this->db->escape((string)($data['model'] ?? '')) . "', sku = '" . $this->db->escape((string)($data['sku'] ?? '')) . "', upc = '" . $this->db->escape((string)($data['upc'] ?? '')) . "', ean = '" . $this->db->escape((string)($data['ean'] ?? '')) . "', jan = '" . $this->db->escape((string)($data['jan'] ?? '')) . "', isbn = '" . $this->db->escape((string)($data['isbn'] ?? '')) . "', mpn = '" . $this->db->escape((string)($data['mpn'] ?? '')) . "', location = '" . $this->db->escape((string)($data['location'] ?? '')) . "', quantity = '" . (int)($data['quantity'] ?? 0) . "', stock_status_id = '" . (int)($data['stock_status_id'] ?? 0) . "', image = '" . $this->db->escape((string)($data['image'] ?? '')) . "', manufacturer_id = '" . (int)($data['manufacturer_id'] ?? 0) . "', shipping = '" . (!empty($data['shipping']) ? 1 : 0) . "', price = '" . (float)($data['price'] ?? 0) . "', points = '" . (int)($data['points'] ?? 0) . "', tax_class_id = '" . (int)($data['tax_class_id'] ?? 0) . "', date_available = '" . $this->db->escape((string)($data['date_available'] ?? date('Y-m-d'))) . "', weight = '" . (float)($data['weight'] ?? 0) . "', weight_class_id = '" . (int)($data['weight_class_id'] ?? 0) . "', length = '" . (float)($data['length'] ?? 0) . "', width = '" . (float)($data['width'] ?? 0) . "', height = '" . (float)($data['height'] ?? 0) . "', length_class_id = '" . (int)($data['length_class_id'] ?? 0) . "', subtract = '" . (!empty($data['subtract']) ? 1 : 0) . "', minimum = '" . (int)($data['minimum'] ?? 1) . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "', status = '" . (!empty($data['status']) ? 1 : 0) . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_description` WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . $language_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` SET product_id = '" . (int)$product_id . "', language_id = '" . $language_id . "', name = '" . $this->db->escape((string)($data['name'] ?? '')) . "', description = '" . $this->db->escape((string)($data['description'] ?? '')) . "', tag = '" . $this->db->escape((string)($data['tag'] ?? '')) . "', meta_title = '" . $this->db->escape((string)($data['meta_title'] ?? ($data['name'] ?? ''))) . "', meta_description = '" . $this->db->escape((string)($data['meta_description'] ?? '')) . "', meta_keyword = '" . $this->db->escape((string)($data['meta_keyword'] ?? '')) . "'");

		if (array_key_exists('store_ids', $data)) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_store` WHERE product_id = '" . (int)$product_id . "'");

			foreach ($this->normalizeStoreIds((array)$data['store_ids']) as $store_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (array_key_exists('category_ids', $data)) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . (int)$product_id . "'");

			foreach ($this->normalizeIds((array)$data['category_ids']) as $category_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}
	}

	public function deleteProduct(int $product_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_description` WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_store` WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . (int)$product_id . "'");
	}

	private function normalizeStoreIds(array $store_ids): array {
		$ids = $this->normalizeIds($store_ids);

		if (!$ids) {
			$ids = [0];
		}

		return $ids;
	}

	private function normalizeIds(array $ids): array {
		$result = [];

		foreach ($ids as $id) {
			$value = (int)$id;

			if ($value >= 0) {
				$result[$value] = $value;
			}
		}

		return array_values($result);
	}
}
