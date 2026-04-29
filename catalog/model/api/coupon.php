<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Coupon extends \Opencart\System\Engine\Model {
	public function getCoupon(int $coupon_id): array {
		$query = $this->db->query("SELECT coupon_id, name, code, discount, type, total, logged, shipping, date_start, date_end, uses_total, uses_customer, status, date_added FROM `" . DB_PREFIX . "coupon` WHERE coupon_id = '" . (int)$coupon_id . "' LIMIT 1");

		return $query->row;
	}

	public function getCoupons(array $data = []): array {
		$sql = "SELECT coupon_id, name, code, discount, type, total, logged, shipping, date_start, date_end, uses_total, uses_customer, status, date_added FROM `" . DB_PREFIX . "coupon` WHERE 1";

		if (!empty($data['filter_name'])) {
			$sql .= " AND name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_code'])) {
			$sql .= " AND code LIKE '" . $this->db->escape($data['filter_code']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== null) {
			$sql .= " AND status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = ['coupon_id', 'name', 'code', 'discount', 'date_start', 'date_end', 'status', 'date_added'];

		if (!empty($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	public function getTotalCoupons(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon` WHERE 1";

		if (!empty($data['filter_name'])) {
			$sql .= " AND name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_code'])) {
			$sql .= " AND code LIKE '" . $this->db->escape($data['filter_code']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== null) {
			$sql .= " AND status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}
