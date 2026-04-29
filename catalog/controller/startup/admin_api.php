<?php
namespace Opencart\Catalog\Controller\Extension\FerrezAdminRestApi\Startup;

class AdminApi extends \Opencart\System\Engine\Controller {
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
		$call = $parts[3] ?? '';

		if ($call !== '') {
			$this->request->get['call'] = $call;
		}

		if (!isset($this->request->get['call']) || $this->request->get['call'] === '') {
			$this->request->get['call'] = 'health';
		}

		$this->request->get['route'] = 'extension/ferrez_admin_rest_api/api/admin';
	}
}
