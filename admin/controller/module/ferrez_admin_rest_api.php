<?php
namespace Opencart\Admin\Controller\Extension\FerrezAdminRestApi\Module;

class FerrezAdminRestApi extends \Opencart\System\Engine\Controller {
	private function syncExtensionInstallRecord(): void {
		$this->load->model('setting/extension');

		$install_info = $this->model_setting_extension->getInstallByCode('ferrez_admin_rest_api');

		if ($install_info) {
			return;
		}

		$extension_info = $this->model_setting_extension->getExtensionByCode('module', 'ferrez_admin_rest_api');

		if (!$extension_info) {
			return;
		}

		$metadata = [
			'name' => 'Ferrez Admin REST API',
			'version' => '1.0.0',
			'author' => 'Ferrez.mx',
			'link' => 'https://ferrez.mx'
		];

		$install_json = DIR_EXTENSION . 'ferrez_admin_rest_api/install.json';

		if (is_file($install_json)) {
			$decoded = json_decode((string)file_get_contents($install_json), true);

			if (is_array($decoded)) {
				$metadata = array_replace($metadata, array_intersect_key($decoded, $metadata));
			}
		}

		$this->model_setting_extension->addInstall([
			'extension_id' => (int)$extension_info['extension_id'],
			'extension_download_id' => 0,
			'name' => (string)$metadata['name'],
			'description' => '',
			'code' => 'ferrez_admin_rest_api',
			'version' => (string)$metadata['version'],
			'author' => (string)$metadata['author'],
			'link' => (string)$metadata['link'],
			'status' => 1
		]);
	}

	private function getEndpointGroups(): array {
		return [
			[
				'title' => $this->language->get('text_group_system'),
				'endpoints' => [
					['call' => 'health', 'methods' => 'GET', 'scope' => 'admin.health.read', 'description' => $this->language->get('text_endpoint_health')],
					['call' => 'metadata', 'methods' => 'GET', 'scope' => 'admin.health.read', 'description' => $this->language->get('text_endpoint_metadata')]
				]
			],
			[
				'title' => $this->language->get('text_group_catalog'),
				'endpoints' => [
					['call' => 'products', 'methods' => 'GET', 'scope' => 'admin.products.read', 'description' => $this->language->get('text_endpoint_products')],
					['call' => 'categories', 'methods' => 'GET', 'scope' => 'admin.categories.read', 'description' => $this->language->get('text_endpoint_categories')],
					['call' => 'manufacturers', 'methods' => 'GET', 'scope' => 'admin.manufacturers.read', 'description' => $this->language->get('text_endpoint_manufacturers')],
					['call' => 'customers', 'methods' => 'GET', 'scope' => 'admin.customers.read', 'description' => $this->language->get('text_endpoint_customers')],
					['call' => 'customer_groups', 'methods' => 'GET', 'scope' => 'admin.customer_groups.read', 'description' => $this->language->get('text_endpoint_customer_groups')]
				]
			],
			[
				'title' => $this->language->get('text_group_sales'),
				'endpoints' => [
					['call' => 'orders', 'methods' => 'GET', 'scope' => 'admin.orders.read', 'description' => $this->language->get('text_endpoint_orders')],
					['call' => 'order_histories', 'methods' => 'GET', 'scope' => 'admin.orders.read', 'description' => $this->language->get('text_endpoint_order_histories')],
					['call' => 'order_history_add', 'methods' => 'POST', 'scope' => 'admin.orders.write', 'description' => $this->language->get('text_endpoint_order_history_add')],
					['call' => 'returns', 'methods' => 'GET', 'scope' => 'admin.returns.read', 'description' => $this->language->get('text_endpoint_returns')],
					['call' => 'return_histories', 'methods' => 'GET', 'scope' => 'admin.returns.read', 'description' => $this->language->get('text_endpoint_return_histories')],
					['call' => 'return_history_add', 'methods' => 'POST', 'scope' => 'admin.returns.write', 'description' => $this->language->get('text_endpoint_return_history_add')],
					['call' => 'coupons', 'methods' => 'GET', 'scope' => 'admin.coupons.read', 'description' => $this->language->get('text_endpoint_coupons')],
					['call' => 'vouchers', 'methods' => 'GET', 'scope' => 'admin.vouchers.read', 'description' => $this->language->get('text_endpoint_vouchers')]
				]
			]
		];
	}

	private function syncModuleRoutePermissions(): void {
		$route = 'extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api';

		$this->load->model('user/user_group');

		foreach ($this->model_user_user_group->getUserGroups() as $user_group) {
			$user_group_info = $this->model_user_user_group->getUserGroup((int)$user_group['user_group_id']);
			$permissions = $user_group_info['permission'] ?? [];
			$access = $permissions['access'] ?? [];
			$modify = $permissions['modify'] ?? [];

			if (in_array('extension/module', $access) && !in_array($route, $access)) {
				$this->model_user_user_group->addPermission((int)$user_group['user_group_id'], 'access', $route);
			}

			if (in_array('extension/module', $modify) && !in_array($route, $modify)) {
				$this->model_user_user_group->addPermission((int)$user_group['user_group_id'], 'modify', $route);
			}
		}
	}

	public function index(): void {
		$this->load->language('extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api');
		$this->load->model('user/api');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['api_user_list_link'] = $this->url->link('user/api', 'user_token=' . $this->session->data['user_token']);

		$data['module_ferrez_admin_rest_api_status'] = (int)$this->config->get('module_ferrez_admin_rest_api_status');
		$data['module_ferrez_admin_rest_api_allowed_origins'] = (string)$this->config->get('module_ferrez_admin_rest_api_allowed_origins');
		$data['module_ferrez_admin_rest_api_max_time_skew'] = (int)($this->config->get('module_ferrez_admin_rest_api_max_time_skew') ?: 450);
		$data['module_ferrez_admin_rest_api_permissions'] = (string)$this->config->get('module_ferrez_admin_rest_api_permissions');
		$data['status_badge_class'] = $data['module_ferrez_admin_rest_api_status'] ? 'bg-success' : 'bg-secondary';
		$data['status_text'] = $data['module_ferrez_admin_rest_api_status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled');

		$allowed_origins = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $data['module_ferrez_admin_rest_api_allowed_origins'])));
		$data['allowed_origins_list'] = array_values($allowed_origins);

		$scope_matrix = [];
		$scope_count = 0;

		if ($data['module_ferrez_admin_rest_api_permissions'] !== '') {
			$decoded_permissions = json_decode($data['module_ferrez_admin_rest_api_permissions'], true);

			if (is_array($decoded_permissions)) {
				foreach ($decoded_permissions as $username => $scopes) {
					if (!is_array($scopes)) {
						continue;
					}

					$unique_scopes = array_values(array_unique(array_map('strval', $scopes)));
					sort($unique_scopes);

					$scope_matrix[] = [
						'username' => (string)$username,
						'scopes' => $unique_scopes,
						'scope_total' => count($unique_scopes)
					];

					$scope_count += count($unique_scopes);
				}
			}
		}

		$data['scope_matrix'] = $scope_matrix;
		$data['scope_user_total'] = count($scope_matrix);
		$data['scope_total'] = $scope_count;

		$api_users = [];
		$active_api_user_total = 0;

		foreach ($this->model_user_api->getApis(['sort' => 'username', 'order' => 'ASC']) as $api_user) {
			$ips = $this->model_user_api->getIps((int)$api_user['api_id']);
			$is_enabled = (bool)$api_user['status'];

			if ($is_enabled) {
				$active_api_user_total++;
			}

			$api_users[] = [
				'api_id' => (int)$api_user['api_id'],
				'username' => (string)$api_user['username'],
				'status_text' => $is_enabled ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'status_badge_class' => $is_enabled ? 'bg-success' : 'bg-secondary',
				'ip_total' => count($ips),
				'date_modified' => (string)$api_user['date_modified']
			];
		}

		$data['api_users'] = $api_users;
		$data['api_user_total'] = count($api_users);
		$data['active_api_user_total'] = $active_api_user_total;

		$catalog_url = '';

		if (defined('HTTP_CATALOG')) {
			$catalog_url = rtrim((string)HTTP_CATALOG, '/');
		} elseif ($this->config->get('config_url')) {
			$catalog_url = rtrim((string)$this->config->get('config_url'), '/');
		}

		$data['api_route_raw'] = 'extension/ferrez_admin_rest_api/api/admin';
		$data['api_endpoint_base'] = $catalog_url ? $catalog_url . '/index.php?route=extension/ferrez_admin_rest_api/api/admin' : 'index.php?route=extension/ferrez_admin_rest_api/api/admin';
		$data['pretty_endpoint_base'] = $catalog_url ? $catalog_url . '/api/admin/v1/' : '/api/admin/v1/';
		$data['endpoint_groups'] = $this->getEndpointGroups();
		$data['endpoint_total'] = array_sum(array_map(static fn(array $group): int => count($group['endpoints']), $data['endpoint_groups']));
		$data['write_endpoint_total'] = 2;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api', $data));
	}

	public function save(): void {
		$this->load->language('extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ferrez_admin_rest_api/module/ferrez_admin_rest_api')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$permissions_json = (string)($this->request->post['module_ferrez_admin_rest_api_permissions'] ?? '');

		if ($permissions_json !== '') {
			$decoded = json_decode($permissions_json, true);

			if ($decoded === null || !is_array($decoded)) {
				$json['error']['permissions'] = $this->language->get('error_permissions');
			}
		}

		$max_time_skew = (int)($this->request->post['module_ferrez_admin_rest_api_max_time_skew'] ?? 450);

		if ($max_time_skew < 60 || $max_time_skew > 3600) {
			$json['error']['max_time_skew'] = $this->language->get('error_time_skew');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ferrez_admin_rest_api', [
				'module_ferrez_admin_rest_api_status' => (int)($this->request->post['module_ferrez_admin_rest_api_status'] ?? 0),
				'module_ferrez_admin_rest_api_allowed_origins' => trim((string)($this->request->post['module_ferrez_admin_rest_api_allowed_origins'] ?? '')),
				'module_ferrez_admin_rest_api_max_time_skew' => $max_time_skew,
				'module_ferrez_admin_rest_api_permissions' => trim($permissions_json)
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->syncModuleRoutePermissions();
		$this->syncExtensionInstallRecord();

		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('ferrez_admin_rest_api');
		$this->model_setting_startup->addStartup([
			'code' => 'ferrez_admin_rest_api',
			'description' => 'Ferrez Admin REST API Startup Router',
			'action' => 'catalog/extension/ferrez_admin_rest_api/startup/admin_api',
			'status' => 1,
			'sort_order' => 998
		]);

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_ferrez_admin_rest_api', [
			'module_ferrez_admin_rest_api_status' => 1,
			'module_ferrez_admin_rest_api_allowed_origins' => '',
			'module_ferrez_admin_rest_api_max_time_skew' => 450,
			'module_ferrez_admin_rest_api_permissions' => ''
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/extension');

		$install_info = $this->model_setting_extension->getInstallByCode('ferrez_admin_rest_api');

		if ($install_info) {
			$this->model_setting_extension->deleteInstall((int)$install_info['extension_install_id']);
		}

		$this->load->model('setting/startup');
		$this->model_setting_startup->deleteStartupByCode('ferrez_admin_rest_api');

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_ferrez_admin_rest_api');
	}
}
