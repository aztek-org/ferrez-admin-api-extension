<?php
namespace Opencart\Catalog\Controller\Extension\FerrezAdminRestApi\Api;

class Admin extends Base {
	public function index(): void {
		$this->load->language('extension/ferrez_admin_rest_api/api/admin');

		$call = (string)($this->request->get['call'] ?? '');
		$method = strtoupper((string)($this->request->server['REQUEST_METHOD'] ?? 'GET'));

		$scope_map = [
			'health'        => ['admin.health.read'],
			'metadata'      => ['admin.health.read'],
			'products'      => ['admin.products.read'],
			'product_create' => ['admin.products.write'],
			'product_update' => ['admin.products.write'],
			'product_delete' => ['admin.products.write'],
			'categories'    => ['admin.categories.read'],
			'category_create' => ['admin.categories.write'],
			'category_update' => ['admin.categories.write'],
			'category_delete' => ['admin.categories.write'],
			'manufacturers' => ['admin.manufacturers.read'],
			'manufacturer_create' => ['admin.manufacturers.write'],
			'manufacturer_update' => ['admin.manufacturers.write'],
			'manufacturer_delete' => ['admin.manufacturers.write'],
			'customers'     => ['admin.customers.read'],
			'customer_groups' => ['admin.customer_groups.read'],
			'orders'        => ['admin.orders.read'],
			'order_histories' => ['admin.orders.read'],
			'order_history_add' => ['admin.orders.write'],
			'coupons' => ['admin.coupons.read'],
			'vouchers' => ['admin.vouchers.read'],
			'returns' => ['admin.returns.read'],
			'return_histories' => ['admin.returns.read'],
			'return_history_add' => ['admin.returns.write']
		];

		if (!$this->initialize($scope_map[$call] ?? [])) {
			return;
		}

		switch ($call) {
			case 'health':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('health_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->respondSuccess([
					'service'   => 'ferrez-admin-rest-api',
					'version'   => 'v1',
					'status'    => 'ok',
					'timestamp' => date('c')
				], ['call' => $call]);
				$this->audit('health_ok', 200);

				return;
			case 'metadata':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('metadata_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/metadata');

				$metadata = [
					'stores' => $this->model_extension_ferrez_admin_rest_api_api_metadata->getStores(),
					'languages' => $this->model_extension_ferrez_admin_rest_api_api_metadata->getLanguages(),
					'currencies' => $this->model_extension_ferrez_admin_rest_api_api_metadata->getCurrencies(),
					'order_statuses' => $this->model_extension_ferrez_admin_rest_api_api_metadata->getOrderStatuses(),
					'return_statuses' => $this->model_extension_ferrez_admin_rest_api_api_metadata->getReturnStatuses()
				];

				$this->respondSuccess($metadata, ['call' => $call]);
				$this->audit('metadata_ok', 200, [
					'stores' => count($metadata['stores']),
					'languages' => count($metadata['languages']),
					'currencies' => count($metadata['currencies']),
					'order_statuses' => count($metadata['order_statuses']),
					'return_statuses' => count($metadata['return_statuses'])
				]);

				return;
			case 'products':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('products_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/product');

				if (isset($this->request->get['product_id'])) {
					$product_id = (int)$this->request->get['product_id'];

					if ($product_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'product_id']);
						$this->audit('product_get_invalid_id', 400, ['product_id' => $product_id]);

						return;
					}

					$product_info = $this->model_extension_ferrez_admin_rest_api_api_product->getProduct($product_id, (int)$this->request->get['store_id']);

					if (!$product_info) {
						$this->respondError(404, 'error_product');
						$this->audit('product_not_found', 404, ['product_id' => $product_id]);

						return;
					}

					$this->respondSuccess($product_info, ['call' => $call]);
					$this->audit('product_get_ok', 200, ['product_id' => $product_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'p.product_id');
				$order = strtoupper((string)($this->request->get['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

				$filter_data = [
					'filter_name'   => (string)($this->request->get['filter_name'] ?? ''),
					'filter_model'  => (string)($this->request->get['filter_model'] ?? ''),
					'filter_status' => isset($this->request->get['filter_status']) ? (int)$this->request->get['filter_status'] : null,
					'store_id'      => (int)$this->request->get['store_id'],
					'page'          => $page,
					'limit'         => $limit,
					'sort'          => $sort,
					'order'         => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_product->getTotalProducts($filter_data);
				$products = $this->model_extension_ferrez_admin_rest_api_api_product->getProducts($filter_data);

				$this->respondSuccess($products, [
					'call'  => $call,
					'total' => $total,
					'page'  => $page,
					'limit' => $limit
				]);
				$this->audit('products_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'product_create':
				if ($method !== 'POST') {
					$this->respondError(405, 'error_method');
					$this->audit('product_create_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/product');

				$input = $this->getRequestBodyData();

				if (!$this->attachProductImageFromMultipart($input)) {
					$this->audit('product_create_invalid_image', 400);

					return;
				}

				if (trim((string)($input['name'] ?? '')) === '' || trim((string)($input['model'] ?? '')) === '') {
					$this->respondError(400, 'error_required', ['required' => ['name', 'model']]);
					$this->audit('product_create_invalid_payload', 400);

					return;
				}

				$store_id = (int)($input['store_id'] ?? 0);
				$product_id = $this->model_extension_ferrez_admin_rest_api_api_product->createProduct($input);
				$product_info = $this->model_extension_ferrez_admin_rest_api_api_product->getProduct($product_id, $store_id);

				$this->respondSuccess([
					'product_id' => $product_id,
					'product' => $product_info
				], ['call' => $call], 201);
				$this->audit('product_create_ok', 201, ['product_id' => $product_id]);

				return;
			case 'product_update':
				if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
					$this->respondError(405, 'error_method');
					$this->audit('product_update_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/product');

				$input = $this->getRequestBodyData();

				if (!$this->attachProductImageFromMultipart($input)) {
					$this->audit('product_update_invalid_image', 400);

					return;
				}

				$product_id = (int)($input['product_id'] ?? ($this->request->get['product_id'] ?? 0));

				if ($product_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['product_id']]);
					$this->audit('product_update_missing_id', 400);

					return;
				}

				if (!$this->model_extension_ferrez_admin_rest_api_api_product->existsProduct($product_id)) {
					$this->respondError(404, 'error_product');
					$this->audit('product_update_not_found', 404, ['product_id' => $product_id]);

					return;
				}

				if (trim((string)($input['name'] ?? '')) === '' || trim((string)($input['model'] ?? '')) === '') {
					$this->respondError(400, 'error_required', ['required' => ['name', 'model']]);
					$this->audit('product_update_invalid_payload', 400, ['product_id' => $product_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_product->updateProduct($product_id, $input);

				$this->respondSuccess([
					'product_id' => $product_id
				], ['call' => $call]);
				$this->audit('product_update_ok', 200, ['product_id' => $product_id]);

				return;
			case 'product_delete':
				if (!in_array($method, ['POST', 'DELETE'], true)) {
					$this->respondError(405, 'error_method');
					$this->audit('product_delete_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/product');

				$input = $this->getRequestBodyData();
				$product_id = (int)($input['product_id'] ?? ($this->request->get['product_id'] ?? 0));

				if ($product_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['product_id']]);
					$this->audit('product_delete_missing_id', 400);

					return;
				}

				if (!$this->model_extension_ferrez_admin_rest_api_api_product->existsProduct($product_id)) {
					$this->respondError(404, 'error_product');
					$this->audit('product_delete_not_found', 404, ['product_id' => $product_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_product->deleteProduct($product_id);

				$this->respondSuccess([
					'product_id' => $product_id,
					'deleted' => true
				], ['call' => $call]);
				$this->audit('product_delete_ok', 200, ['product_id' => $product_id]);

				return;
			case 'categories':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('categories_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/category');

				if (isset($this->request->get['category_id'])) {
					$category_id = (int)$this->request->get['category_id'];

					if ($category_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'category_id']);
						$this->audit('category_get_invalid_id', 400, ['category_id' => $category_id]);

						return;
					}

					$category_info = $this->model_extension_ferrez_admin_rest_api_api_category->getCategory($category_id, (int)($this->request->get['store_id'] ?? 0));

					if (!$category_info) {
						$this->respondError(404, 'error_category');
						$this->audit('category_not_found', 404, ['category_id' => $category_id]);

						return;
					}

					$this->respondSuccess($category_info, ['call' => $call]);
					$this->audit('category_get_ok', 200, ['category_id' => $category_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'c.category_id');
				$order = strtoupper((string)($this->request->get['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

				$filter_data = [
					'filter_name'   => (string)($this->request->get['filter_name'] ?? ''),
					'filter_status' => isset($this->request->get['filter_status']) ? (int)$this->request->get['filter_status'] : null,
					'store_id'      => (int)($this->request->get['store_id'] ?? 0),
					'page'          => $page,
					'limit'         => $limit,
					'sort'          => $sort,
					'order'         => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_category->getTotalCategories($filter_data);
				$categories = $this->model_extension_ferrez_admin_rest_api_api_category->getCategories($filter_data);

				$this->respondSuccess($categories, [
					'call'  => $call,
					'total' => $total,
					'page'  => $page,
					'limit' => $limit
				]);
				$this->audit('categories_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'category_create':
				if ($method !== 'POST') {
					$this->respondError(405, 'error_method');
					$this->audit('category_create_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/category');

				$input = $this->getRequestBodyData();

				if (trim((string)($input['name'] ?? '')) === '') {
					$this->respondError(400, 'error_required', ['required' => ['name']]);
					$this->audit('category_create_invalid_payload', 400);

					return;
				}

				$store_id = (int)($input['store_id'] ?? 0);
				$category_id = $this->model_extension_ferrez_admin_rest_api_api_category->createCategory($input);
				$category_info = $this->model_extension_ferrez_admin_rest_api_api_category->getCategory($category_id, $store_id);

				$this->respondSuccess([
					'category_id' => $category_id,
					'category' => $category_info
				], ['call' => $call], 201);
				$this->audit('category_create_ok', 201, ['category_id' => $category_id]);

				return;
			case 'category_update':
				if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
					$this->respondError(405, 'error_method');
					$this->audit('category_update_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/category');

				$input = $this->getRequestBodyData();
				$category_id = (int)($input['category_id'] ?? ($this->request->get['category_id'] ?? 0));

				if ($category_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['category_id']]);
					$this->audit('category_update_missing_id', 400);

					return;
				}

				if (!$this->model_extension_ferrez_admin_rest_api_api_category->existsCategory($category_id)) {
					$this->respondError(404, 'error_category');
					$this->audit('category_update_not_found', 404, ['category_id' => $category_id]);

					return;
				}

				if (trim((string)($input['name'] ?? '')) === '') {
					$this->respondError(400, 'error_required', ['required' => ['name']]);
					$this->audit('category_update_invalid_payload', 400, ['category_id' => $category_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_category->updateCategory($category_id, $input);

				$this->respondSuccess([
					'category_id' => $category_id
				], ['call' => $call]);
				$this->audit('category_update_ok', 200, ['category_id' => $category_id]);

				return;
			case 'category_delete':
				if (!in_array($method, ['POST', 'DELETE'], true)) {
					$this->respondError(405, 'error_method');
					$this->audit('category_delete_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/category');

				$input = $this->getRequestBodyData();
				$category_id = (int)($input['category_id'] ?? ($this->request->get['category_id'] ?? 0));

				if ($category_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['category_id']]);
					$this->audit('category_delete_missing_id', 400);

					return;
				}

				if (!$this->model_extension_ferrez_admin_rest_api_api_category->existsCategory($category_id)) {
					$this->respondError(404, 'error_category');
					$this->audit('category_delete_not_found', 404, ['category_id' => $category_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_category->deleteCategory($category_id);

				$this->respondSuccess([
					'category_id' => $category_id,
					'deleted' => true
				], ['call' => $call]);
				$this->audit('category_delete_ok', 200, ['category_id' => $category_id]);

				return;
			case 'manufacturers':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('manufacturers_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/manufacturer');

				if (isset($this->request->get['manufacturer_id'])) {
					$manufacturer_id = (int)$this->request->get['manufacturer_id'];

					if ($manufacturer_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'manufacturer_id']);
						$this->audit('manufacturer_get_invalid_id', 400, ['manufacturer_id' => $manufacturer_id]);

						return;
					}

					$manufacturer_info = $this->model_extension_ferrez_admin_rest_api_api_manufacturer->getManufacturer($manufacturer_id, (int)($this->request->get['store_id'] ?? 0));

					if (!$manufacturer_info) {
						$this->respondError(404, 'error_manufacturer');
						$this->audit('manufacturer_not_found', 404, ['manufacturer_id' => $manufacturer_id]);

						return;
					}

					$this->respondSuccess($manufacturer_info, ['call' => $call]);
					$this->audit('manufacturer_get_ok', 200, ['manufacturer_id' => $manufacturer_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'm.manufacturer_id');
				$order = strtoupper((string)($this->request->get['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

				$filter_data = [
					'filter_name' => (string)($this->request->get['filter_name'] ?? ''),
					'store_id'    => (int)($this->request->get['store_id'] ?? 0),
					'page'        => $page,
					'limit'       => $limit,
					'sort'        => $sort,
					'order'       => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_manufacturer->getTotalManufacturers($filter_data);
				$manufacturers = $this->model_extension_ferrez_admin_rest_api_api_manufacturer->getManufacturers($filter_data);

				$this->respondSuccess($manufacturers, [
					'call'  => $call,
					'total' => $total,
					'page'  => $page,
					'limit' => $limit
				]);
				$this->audit('manufacturers_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'manufacturer_create':
				if ($method !== 'POST') {
					$this->respondError(405, 'error_method');
					$this->audit('manufacturer_create_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/manufacturer');

				$input = $this->getRequestBodyData();

				if (trim((string)($input['name'] ?? '')) === '') {
					$this->respondError(400, 'error_required', ['required' => ['name']]);
					$this->audit('manufacturer_create_invalid_payload', 400);

					return;
				}

				$store_id = (int)($input['store_id'] ?? 0);
				$manufacturer_id = $this->model_extension_ferrez_admin_rest_api_api_manufacturer->createManufacturer($input);
				$manufacturer_info = $this->model_extension_ferrez_admin_rest_api_api_manufacturer->getManufacturer($manufacturer_id, $store_id);

				$this->respondSuccess([
					'manufacturer_id' => $manufacturer_id,
					'manufacturer' => $manufacturer_info
				], ['call' => $call], 201);
				$this->audit('manufacturer_create_ok', 201, ['manufacturer_id' => $manufacturer_id]);

				return;
			case 'manufacturer_update':
				if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
					$this->respondError(405, 'error_method');
					$this->audit('manufacturer_update_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/manufacturer');

				$input = $this->getRequestBodyData();
				$manufacturer_id = (int)($input['manufacturer_id'] ?? ($this->request->get['manufacturer_id'] ?? 0));

				if ($manufacturer_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['manufacturer_id']]);
					$this->audit('manufacturer_update_missing_id', 400);

					return;
				}

				if (!$this->model_extension_ferrez_admin_rest_api_api_manufacturer->existsManufacturer($manufacturer_id)) {
					$this->respondError(404, 'error_manufacturer');
					$this->audit('manufacturer_update_not_found', 404, ['manufacturer_id' => $manufacturer_id]);

					return;
				}

				if (trim((string)($input['name'] ?? '')) === '') {
					$this->respondError(400, 'error_required', ['required' => ['name']]);
					$this->audit('manufacturer_update_invalid_payload', 400, ['manufacturer_id' => $manufacturer_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_manufacturer->updateManufacturer($manufacturer_id, $input);

				$this->respondSuccess([
					'manufacturer_id' => $manufacturer_id
				], ['call' => $call]);
				$this->audit('manufacturer_update_ok', 200, ['manufacturer_id' => $manufacturer_id]);

				return;
			case 'manufacturer_delete':
				if (!in_array($method, ['POST', 'DELETE'], true)) {
					$this->respondError(405, 'error_method');
					$this->audit('manufacturer_delete_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/manufacturer');

				$input = $this->getRequestBodyData();
				$manufacturer_id = (int)($input['manufacturer_id'] ?? ($this->request->get['manufacturer_id'] ?? 0));

				if ($manufacturer_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['manufacturer_id']]);
					$this->audit('manufacturer_delete_missing_id', 400);

					return;
				}

				if (!$this->model_extension_ferrez_admin_rest_api_api_manufacturer->existsManufacturer($manufacturer_id)) {
					$this->respondError(404, 'error_manufacturer');
					$this->audit('manufacturer_delete_not_found', 404, ['manufacturer_id' => $manufacturer_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_manufacturer->deleteManufacturer($manufacturer_id);

				$this->respondSuccess([
					'manufacturer_id' => $manufacturer_id,
					'deleted' => true
				], ['call' => $call]);
				$this->audit('manufacturer_delete_ok', 200, ['manufacturer_id' => $manufacturer_id]);

				return;
			case 'customers':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('customers_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/customer');

				if (isset($this->request->get['customer_id'])) {
					$customer_id = (int)$this->request->get['customer_id'];

					if ($customer_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'customer_id']);
						$this->audit('customer_get_invalid_id', 400, ['customer_id' => $customer_id]);

						return;
					}

					$customer_info = $this->model_extension_ferrez_admin_rest_api_api_customer->getCustomer($customer_id);

					if (!$customer_info) {
						$this->respondError(404, 'error_customer');
						$this->audit('customer_not_found', 404, ['customer_id' => $customer_id]);

						return;
					}

					$this->respondSuccess($customer_info, ['call' => $call]);
					$this->audit('customer_get_ok', 200, ['customer_id' => $customer_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'c.customer_id');
				$order = strtoupper((string)($this->request->get['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

				$filter_data = [
					'filter_firstname' => (string)($this->request->get['filter_firstname'] ?? ''),
					'filter_lastname'  => (string)($this->request->get['filter_lastname'] ?? ''),
					'filter_email'     => (string)($this->request->get['filter_email'] ?? ''),
					'filter_status'    => isset($this->request->get['filter_status']) ? (int)$this->request->get['filter_status'] : null,
					'page'             => $page,
					'limit'            => $limit,
					'sort'             => $sort,
					'order'            => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_customer->getTotalCustomers($filter_data);
				$customers = $this->model_extension_ferrez_admin_rest_api_api_customer->getCustomers($filter_data);

				$this->respondSuccess($customers, [
					'call'  => $call,
					'total' => $total,
					'page'  => $page,
					'limit' => $limit
				]);
				$this->audit('customers_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'customer_groups':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('customer_groups_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/customer_group');

				if (isset($this->request->get['customer_group_id'])) {
					$customer_group_id = (int)$this->request->get['customer_group_id'];

					if ($customer_group_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'customer_group_id']);
						$this->audit('customer_group_get_invalid_id', 400, ['customer_group_id' => $customer_group_id]);

						return;
					}

					$customer_group_info = $this->model_extension_ferrez_admin_rest_api_api_customer_group->getCustomerGroup($customer_group_id);

					if (!$customer_group_info) {
						$this->respondError(404, 'error_customer_group');
						$this->audit('customer_group_not_found', 404, ['customer_group_id' => $customer_group_id]);

						return;
					}

					$this->respondSuccess($customer_group_info, ['call' => $call]);
					$this->audit('customer_group_get_ok', 200, ['customer_group_id' => $customer_group_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'cgd.name');
				$order = strtoupper((string)($this->request->get['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

				$filter_data = [
					'filter_name' => (string)($this->request->get['filter_name'] ?? ''),
					'language_id' => (int)($this->request->get['language_id'] ?? 0),
					'page'        => $page,
					'limit'       => $limit,
					'sort'        => $sort,
					'order'       => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_customer_group->getTotalCustomerGroups($filter_data);
				$customer_groups = $this->model_extension_ferrez_admin_rest_api_api_customer_group->getCustomerGroups($filter_data);

				$this->respondSuccess($customer_groups, [
					'call'  => $call,
					'total' => $total,
					'page'  => $page,
					'limit' => $limit
				]);
				$this->audit('customer_groups_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'orders':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('orders_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/order');

				if (isset($this->request->get['order_id'])) {
					$order_id = (int)$this->request->get['order_id'];

					if ($order_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'order_id']);
						$this->audit('order_get_invalid_id', 400, ['order_id' => $order_id]);

						return;
					}

					$order_info = $this->model_extension_ferrez_admin_rest_api_api_order->getOrder($order_id);

					if (!$order_info) {
						$this->respondError(404, 'error_order');
						$this->audit('order_not_found', 404, ['order_id' => $order_id]);

						return;
					}

					$this->respondSuccess($order_info, ['call' => $call]);
					$this->audit('order_get_ok', 200, ['order_id' => $order_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'o.order_id');
				$order = strtoupper((string)($this->request->get['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

				$filter_data = [
					'filter_store_id'        => (int)($this->request->get['store_id'] ?? 0),
					'filter_order_status_id' => isset($this->request->get['filter_order_status_id']) ? (int)$this->request->get['filter_order_status_id'] : null,
					'filter_customer'        => (string)($this->request->get['filter_customer'] ?? ''),
					'filter_email'           => (string)($this->request->get['filter_email'] ?? ''),
					'filter_date_from'       => (string)($this->request->get['filter_date_from'] ?? ''),
					'filter_date_to'         => (string)($this->request->get['filter_date_to'] ?? ''),
					'page'                   => $page,
					'limit'                  => $limit,
					'sort'                   => $sort,
					'order'                  => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_order->getTotalOrders($filter_data);
				$orders = $this->model_extension_ferrez_admin_rest_api_api_order->getOrders($filter_data);

				$this->respondSuccess($orders, [
					'call'  => $call,
					'total' => $total,
					'page'  => $page,
					'limit' => $limit
				]);
				$this->audit('orders_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'order_histories':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('order_histories_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/order');

				$order_id = (int)($this->request->get['order_id'] ?? 0);
				if ($order_id < 1) {
					$this->respondError(400, 'error_required', ['missing' => 'order_id']);
					$this->audit('order_histories_missing_order_id', 400);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));

				$total = $this->model_extension_ferrez_admin_rest_api_api_order->getTotalHistories($order_id);
				$histories = $this->model_extension_ferrez_admin_rest_api_api_order->getHistories($order_id, $page, $limit);

				$this->respondSuccess($histories, [
					'call'     => $call,
					'order_id' => $order_id,
					'total'    => $total,
					'page'     => $page,
					'limit'    => $limit
				]);
				$this->audit('order_histories_ok', 200, ['order_id' => $order_id, 'total' => $total]);

				return;
			case 'order_history_add':
				if ($method !== 'POST') {
					$this->respondError(405, 'error_method');
					$this->audit('order_history_add_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/order');

				$input = $this->getRequestBodyData();
				$order_id = (int)($input['order_id'] ?? ($this->request->get['order_id'] ?? 0));
				$order_status_id = (int)($input['order_status_id'] ?? 0);
				$comment = (string)($input['comment'] ?? '');
				$notify = !empty($input['notify']);
				$override = !empty($input['override']);

				if ($order_id < 1 || $order_status_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['order_id', 'order_status_id']]);
					$this->audit('order_history_add_invalid_payload', 400, ['order_id' => $order_id, 'order_status_id' => $order_status_id]);

					return;
				}

				$order_info = $this->model_extension_ferrez_admin_rest_api_api_order->getOrder($order_id);
				if (!$order_info) {
					$this->respondError(404, 'error_order');
					$this->audit('order_history_add_order_not_found', 404, ['order_id' => $order_id]);

					return;
				}

				$this->load->model('checkout/order');
				$this->model_checkout_order->addHistory($order_id, $order_status_id, $comment, $notify, $override);

				$this->respondSuccess([
					'order_id' => $order_id,
					'order_status_id' => $order_status_id,
					'comment' => $comment,
					'notify' => $notify,
					'override' => $override
				], ['call' => $call]);
				$this->audit('order_history_add_ok', 200, ['order_id' => $order_id, 'order_status_id' => $order_status_id]);

				return;
			case 'coupons':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('coupons_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/coupon');

				if (isset($this->request->get['coupon_id'])) {
					$coupon_id = (int)$this->request->get['coupon_id'];

					if ($coupon_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'coupon_id']);
						$this->audit('coupon_get_invalid_id', 400, ['coupon_id' => $coupon_id]);

						return;
					}

					$coupon_info = $this->model_extension_ferrez_admin_rest_api_api_coupon->getCoupon($coupon_id);

					if (!$coupon_info) {
						$this->respondError(404, 'error_call');
						$this->audit('coupon_not_found', 404, ['coupon_id' => $coupon_id]);

						return;
					}

					$this->respondSuccess($coupon_info, ['call' => $call]);
					$this->audit('coupon_get_ok', 200, ['coupon_id' => $coupon_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'name');
				$order = strtoupper((string)($this->request->get['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

				$filter_data = [
					'filter_name' => (string)($this->request->get['filter_name'] ?? ''),
					'filter_code' => (string)($this->request->get['filter_code'] ?? ''),
					'filter_status' => isset($this->request->get['filter_status']) ? (int)$this->request->get['filter_status'] : null,
					'page' => $page,
					'limit' => $limit,
					'sort' => $sort,
					'order' => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_coupon->getTotalCoupons($filter_data);
				$coupons = $this->model_extension_ferrez_admin_rest_api_api_coupon->getCoupons($filter_data);

				$this->respondSuccess($coupons, [
					'call' => $call,
					'total' => $total,
					'page' => $page,
					'limit' => $limit
				]);
				$this->audit('coupons_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'vouchers':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('vouchers_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/voucher');

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));

				$filter_data = [
					'filter_order_id' => isset($this->request->get['filter_order_id']) ? (int)$this->request->get['filter_order_id'] : null,
					'filter_status_id' => isset($this->request->get['filter_order_status_id']) ? (int)$this->request->get['filter_order_status_id'] : null,
					'page' => $page,
					'limit' => $limit
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_voucher->getTotalVouchers($filter_data);
				$vouchers = $this->model_extension_ferrez_admin_rest_api_api_voucher->getVouchers($filter_data);

				$this->respondSuccess($vouchers, [
					'call' => $call,
					'total' => $total,
					'page' => $page,
					'limit' => $limit
				]);
				$this->audit('vouchers_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'returns':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('returns_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/returns');

				if (isset($this->request->get['return_id'])) {
					$return_id = (int)$this->request->get['return_id'];

					if ($return_id < 1) {
						$this->respondError(400, 'error_required', ['invalid' => 'return_id']);
						$this->audit('return_get_invalid_id', 400, ['return_id' => $return_id]);

						return;
					}

					$return_info = $this->model_extension_ferrez_admin_rest_api_api_returns->getReturn($return_id);

					if (!$return_info) {
						$this->respondError(404, 'error_return');
						$this->audit('return_not_found', 404, ['return_id' => $return_id]);

						return;
					}

					$this->respondSuccess($return_info, ['call' => $call]);
					$this->audit('return_get_ok', 200, ['return_id' => $return_id]);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));
				$sort = (string)($this->request->get['sort'] ?? 'r.return_id');
				$order = strtoupper((string)($this->request->get['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

				$filter_data = [
					'filter_order_id' => isset($this->request->get['filter_order_id']) ? (int)$this->request->get['filter_order_id'] : null,
					'filter_customer' => (string)($this->request->get['filter_customer'] ?? ''),
					'filter_product' => (string)($this->request->get['filter_product'] ?? ''),
					'filter_return_status_id' => isset($this->request->get['filter_return_status_id']) ? (int)$this->request->get['filter_return_status_id'] : null,
					'page' => $page,
					'limit' => $limit,
					'sort' => $sort,
					'order' => $order
				];

				$total = $this->model_extension_ferrez_admin_rest_api_api_returns->getTotalReturns($filter_data);
				$returns = $this->model_extension_ferrez_admin_rest_api_api_returns->getReturns($filter_data);

				$this->respondSuccess($returns, [
					'call' => $call,
					'total' => $total,
					'page' => $page,
					'limit' => $limit
				]);
				$this->audit('returns_list_ok', 200, ['total' => $total, 'page' => $page, 'limit' => $limit]);

				return;
			case 'return_histories':
				if ($method !== 'GET') {
					$this->respondError(405, 'error_method');
					$this->audit('return_histories_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/returns');

				$return_id = (int)($this->request->get['return_id'] ?? 0);
				if ($return_id < 1) {
					$this->respondError(400, 'error_required', ['missing' => 'return_id']);
					$this->audit('return_histories_missing_return_id', 400);

					return;
				}

				$page = max(1, (int)($this->request->get['page'] ?? 1));
				$limit = max(1, min(200, (int)($this->request->get['limit'] ?? 20)));

				$total = $this->model_extension_ferrez_admin_rest_api_api_returns->getTotalHistories($return_id);
				$histories = $this->model_extension_ferrez_admin_rest_api_api_returns->getHistories($return_id, $page, $limit);

				$this->respondSuccess($histories, [
					'call' => $call,
					'return_id' => $return_id,
					'total' => $total,
					'page' => $page,
					'limit' => $limit
				]);
				$this->audit('return_histories_ok', 200, ['return_id' => $return_id, 'total' => $total]);

				return;
			case 'return_history_add':
				if ($method !== 'POST') {
					$this->respondError(405, 'error_method');
					$this->audit('return_history_add_method_rejected', 405, ['method' => $method]);

					return;
				}

				$this->load->model('extension/ferrez_admin_rest_api/api/returns');

				$input = $this->getRequestBodyData();
				$return_id = (int)($input['return_id'] ?? ($this->request->get['return_id'] ?? 0));
				$return_status_id = (int)($input['return_status_id'] ?? 0);
				$comment = (string)($input['comment'] ?? '');
				$notify = !empty($input['notify']);

				if ($return_id < 1 || $return_status_id < 1) {
					$this->respondError(400, 'error_required', ['required' => ['return_id', 'return_status_id']]);
					$this->audit('return_history_add_invalid_payload', 400, ['return_id' => $return_id, 'return_status_id' => $return_status_id]);

					return;
				}

				$return_info = $this->model_extension_ferrez_admin_rest_api_api_returns->getReturn($return_id);
				if (!$return_info) {
					$this->respondError(404, 'error_return');
					$this->audit('return_history_add_return_not_found', 404, ['return_id' => $return_id]);

					return;
				}

				$this->model_extension_ferrez_admin_rest_api_api_returns->addHistory($return_id, $return_status_id, $comment, $notify);

				$this->respondSuccess([
					'return_id' => $return_id,
					'return_status_id' => $return_status_id,
					'comment' => $comment,
					'notify' => $notify
				], ['call' => $call]);
				$this->audit('return_history_add_ok', 200, ['return_id' => $return_id, 'return_status_id' => $return_status_id]);

				return;
			default:
				$this->respondError(404, 'error_call');
				$this->audit('call_not_found', 404, ['call' => $call]);

				return;
		}
	}

	private function attachProductImageFromMultipart(array &$input): bool {
		$file = $this->request->files['image_file'] ?? null;

		if (!is_array($file) || empty($file['tmp_name'])) {
			return true;
		}

		$upload_error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

		if ($upload_error !== UPLOAD_ERR_OK) {
			$this->respondError(400, 'error_image_upload', ['reason' => 'upload_error', 'code' => $upload_error]);

			return false;
		}

		$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
		$max_bytes = (int)($this->config->get('module_ferrez_admin_rest_api_max_upload_size') ?: 5242880);
		$size = (int)($file['size'] ?? 0);

		if ($size < 1 || $size > $max_bytes) {
			$this->respondError(400, 'error_image_invalid', ['reason' => 'size', 'max_bytes' => $max_bytes]);

			return false;
		}

		$original_name = (string)($file['name'] ?? 'upload.bin');
		$extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

		if (!in_array($extension, $allowed_extensions, true)) {
			$this->respondError(400, 'error_image_invalid', ['reason' => 'extension', 'allowed' => $allowed_extensions]);

			return false;
		}

		if (!defined('DIR_IMAGE') || !DIR_IMAGE) {
			$this->respondError(500, 'error_image_upload', ['reason' => 'image_path_unavailable']);

			return false;
		}

		$target_dir = rtrim(DIR_IMAGE, '/\\') . '/catalog/ferrez_admin_api/';

		if (!is_dir($target_dir) && !mkdir($target_dir, 0775, true) && !is_dir($target_dir)) {
			$this->respondError(500, 'error_image_upload', ['reason' => 'cannot_create_directory']);

			return false;
		}

		$filename = 'product_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
		$target_path = $target_dir . $filename;

		if (!move_uploaded_file((string)$file['tmp_name'], $target_path)) {
			$this->respondError(500, 'error_image_upload', ['reason' => 'cannot_move_file']);

			return false;
		}

		$input['image'] = 'catalog/ferrez_admin_api/' . $filename;

		return true;
	}

}
