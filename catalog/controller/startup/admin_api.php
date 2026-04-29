<?php
namespace Opencart\Catalog\Controller\Extension\FerrezAdminRestApi\Startup;

class AdminApi extends \Opencart\System\Engine\Controller {
	private function normalizeResource(string $resource): string {
		$resource = strtolower(trim($resource));

		$map = [
			'products' => 'product',
			'categories' => 'category',
			'manufacturers' => 'manufacturer',
			'customers' => 'customer',
			'customer_groups' => 'customer_group',
			'customer-groups' => 'customer_group',
			'orders' => 'order',
			'coupons' => 'coupon',
			'vouchers' => 'voucher',
			'returns' => 'return'
		];

		return $map[$resource] ?? $resource;
	}

	private function mapRestCall(string $resource, string $method, array $segments): string {
		$id = $segments[1] ?? '';
		$action = strtolower((string)($segments[2] ?? ''));

		switch ($resource) {
			case 'health':
			case 'metadata':
				return $resource;
			case 'product':
				if ($method === 'POST' && $id === '') {
					return 'product_create';
				}

				if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && $id !== '') {
					return 'product_update';
				}

				if ($method === 'DELETE' && $id !== '') {
					return 'product_delete';
				}

				return 'products';
			case 'category':
				if ($method === 'POST' && $id === '') {
					return 'category_create';
				}

				if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && $id !== '') {
					return 'category_update';
				}

				if ($method === 'DELETE' && $id !== '') {
					return 'category_delete';
				}

				return 'categories';
			case 'manufacturer':
				if ($method === 'POST' && $id === '') {
					return 'manufacturer_create';
				}

				if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && $id !== '') {
					return 'manufacturer_update';
				}

				if ($method === 'DELETE' && $id !== '') {
					return 'manufacturer_delete';
				}

				return 'manufacturers';
			case 'customer':
				return 'customers';
			case 'customer_group':
				return 'customer_groups';
			case 'order':
				if ($action === 'histories' && $method === 'GET') {
					return 'order_histories';
				}

				if ($action === 'history' && $method === 'POST') {
					return 'order_history_add';
				}

				return 'orders';
			case 'coupon':
				return 'coupons';
			case 'voucher':
				return 'vouchers';
			case 'return':
				if ($action === 'histories' && $method === 'GET') {
					return 'return_histories';
				}

				if ($action === 'history' && $method === 'POST') {
					return 'return_history_add';
				}

				return 'returns';
			default:
				return $resource;
		}
	}

	public function index(): void {
		if (!(bool)$this->config->get('module_ferrez_admin_rest_api_status')) {
			return;
		}

		$route_path = trim((string)($this->request->get['_route_'] ?? ''), '/');

		if ($route_path === '') {
			return;
		}

		$prefix = 'api/admin/v1';

		if ($route_path !== $prefix && strpos($route_path, $prefix . '/') !== 0) {
			return;
		}

		$parts = explode('/', $route_path);
		$rest_segments = array_slice($parts, 3);
		$resource = $this->normalizeResource((string)($rest_segments[0] ?? ''));
		$method = strtoupper((string)($this->request->server['REQUEST_METHOD'] ?? 'GET'));
		$call = $this->mapRestCall($resource, $method, $rest_segments);

		if ($resource !== '') {
			if (in_array($resource, ['product', 'category', 'manufacturer', 'customer', 'customer_group', 'order', 'return'], true) && isset($rest_segments[1]) && is_numeric($rest_segments[1])) {
				$id_map = [
					'product' => 'product_id',
					'category' => 'category_id',
					'manufacturer' => 'manufacturer_id',
					'customer' => 'customer_id',
					'customer_group' => 'customer_group_id',
					'order' => 'order_id',
					'return' => 'return_id'
				];

				$this->request->get[$id_map[$resource]] = (int)$rest_segments[1];
			}

			$this->request->get['call'] = $call;
		}

		if (!isset($this->request->get['call']) || $this->request->get['call'] === '') {
			$this->request->get['call'] = 'health';
		}

		$this->request->get['route'] = 'extension/ferrez_admin_rest_api/api/admin';
	}
}
