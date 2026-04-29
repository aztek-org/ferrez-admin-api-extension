<?php
namespace Opencart\Catalog\Model\Extension\FerrezAdminRestApi\Api;

class Voucher extends \Opencart\System\Engine\Model {
	public function getVouchers(array $data = []): array {
		$sql = "SELECT ot.order_total_id, ot.order_id, ot.title, ot.value, o.order_status_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, o.currency_code, o.currency_value, o.date_added FROM `" . DB_PREFIX . "order_total` ot LEFT JOIN `" . DB_PREFIX . "order` o ON (ot.order_id = o.order_id) WHERE ot.code = 'voucher'";

		if (isset($data['filter_order_id']) && $data['filter_order_id'] !== null) {
			$sql .= " AND ot.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (isset($data['filter_status_id']) && $data['filter_status_id'] !== null) {
			$sql .= " AND o.order_status_id = '" . (int)$data['filter_status_id'] . "'";
		}

		$sql .= " ORDER BY ot.order_total_id DESC";

		$start = ((int)$data['page'] - 1) * (int)$data['limit'];
		if ($start < 0) {
			$start = 0;
		}

		$sql .= " LIMIT " . (int)$start . "," . (int)$data['limit'];

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalVouchers(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_total` ot LEFT JOIN `" . DB_PREFIX . "order` o ON (ot.order_id = o.order_id) WHERE ot.code = 'voucher'";

		if (isset($data['filter_order_id']) && $data['filter_order_id'] !== null) {
			$sql .= " AND ot.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (isset($data['filter_status_id']) && $data['filter_status_id'] !== null) {
			$sql .= " AND o.order_status_id = '" . (int)$data['filter_status_id'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}
