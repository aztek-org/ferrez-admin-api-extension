<?php
namespace Opencart\Catalog\Controller\Extension\FerrezAdminRestApi\Api;

class Base extends \Opencart\System\Engine\Controller {
	protected array $api_info = [];
	protected \Opencart\System\Library\Log $api_log;
	private string $auth_error_key = 'error_auth';
	private array $auth_error_details = [];
	private ?string $raw_request_body = null;

	public function __construct($registry) {
		parent::__construct($registry);

		$this->api_log = new \Opencart\System\Library\Log('admin_rest_api.log');
	}

	protected function initialize(array $required_scopes = []): bool {
		$this->setCorsHeaders();

		if (!(bool)$this->config->get('module_ferrez_admin_rest_api_status')) {
			$this->respondError(403, 'error_disabled');
			$this->audit('module_disabled', 403);

			return false;
		}

		if (($this->request->server['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
			$this->response->addHeader(($this->request->server['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 204 No Content');
			$this->response->setOutput('');

			return false;
		}

		if (!$this->validateOrigin()) {
			$this->respondError(403, 'error_origin');
			$this->audit('origin_rejected', 403);

			return false;
		}

		if (!$this->validateAuth()) {
			$status_code = ($this->auth_error_key === 'error_required') ? 400 : 403;
			$this->respondError($status_code, $this->auth_error_key, $this->auth_error_details);
			$this->audit('auth_failed', $status_code);

			return false;
		}

		if (!$this->validateScopes($required_scopes)) {
			$this->respondError(403, 'error_permission');
			$this->audit('permission_denied', 403);

			return false;
		}

		return true;
	}

	protected function respondSuccess(array $data, array $meta = [], int $status_code = 200): void {
		$this->respond($status_code, [
			'success' => 1,
			'error'   => [],
			'data'    => $data,
			'meta'    => $meta
		]);
	}

	protected function respondError(int $status_code, string $language_key, array $details = []): void {
		$this->respond($status_code, [
			'success' => 0,
			'error'   => [
				'message' => $this->language->get($language_key),
				'details' => $details
			],
			'data'    => []
		]);
	}

	protected function audit(string $event, int $status_code, array $context = []): void {
		$record = [
			'event'      => $event,
			'status'     => $status_code,
			'ip'         => oc_get_ip(),
			'route'      => (string)($this->request->get['route'] ?? ''),
			'call'       => (string)($this->request->get['call'] ?? ''),
			'method'     => (string)($this->request->server['REQUEST_METHOD'] ?? ''),
			'username'   => (string)($this->api_info['username'] ?? ''),
			'timestamp'  => date('c'),
			'context'    => $context
		];

		$this->api_log->write(json_encode($record, JSON_UNESCAPED_SLASHES));
	}

	private function respond(int $status_code, array $payload): void {
		$this->response->addHeader(($this->request->server['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' ' . $status_code . ' ' . $this->statusText($status_code));
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($payload));
	}

	private function statusText(int $status_code): string {
		$map = [
			200 => 'OK',
			201 => 'Created',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			422 => 'Unprocessable Entity',
			500 => 'Internal Server Error'
		];

		return $map[$status_code] ?? 'OK';
	}

	private function setCorsHeaders(): void {
		$origin = (string)($this->request->server['HTTP_ORIGIN'] ?? '');
		$allowed_origins = $this->getAllowedOrigins();

		if ($origin && in_array($origin, $allowed_origins)) {
			$this->response->addHeader('Access-Control-Allow-Origin: ' . $origin);
			$this->response->addHeader('Vary: Origin');
		} elseif (!$origin) {
			$this->response->addHeader('Access-Control-Allow-Origin: *');
		}

		$this->response->addHeader('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		$this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Oc-Merchant-Language, X-Oc-Store-Id, X-Oc-Currency');
		$this->response->addHeader('Access-Control-Allow-Credentials: true');
	}

	private function validateOrigin(): bool {
		$origin = (string)($this->request->server['HTTP_ORIGIN'] ?? '');

		if (!$origin) {
			return true;
		}

		return in_array($origin, $this->getAllowedOrigins());
	}

	private function getAllowedOrigins(): array {
		$configured = $this->config->get('module_ferrez_admin_rest_api_allowed_origins');

		if (is_string($configured) && $configured !== '') {
			$origins = array_filter(array_map('trim', explode(',', $configured)));

			if ($origins) {
				return $origins;
			}
		}

		$default = [];

		if ($this->config->get('config_url')) {
			$default[] = rtrim($this->config->get('config_url'), '/');
		}

		if ($this->config->get('config_ssl')) {
			$default[] = rtrim($this->config->get('config_ssl'), '/');
		}

		return array_unique($default);
	}

	private function validateAuth(): bool {
		$this->auth_error_key = 'error_auth';
		$this->auth_error_details = [];

		$required = [
			'route',
			'call',
			'username',
			'store_id',
			'language',
			'currency',
			'time',
			'signature'
		];

		foreach ($required as $key) {
			if (!isset($this->request->get[$key])) {
				$this->auth_error_key = 'error_required';
				$this->auth_error_details = ['missing' => $key];

				return false;
			}
		}

		$this->load->model('user/api');

		$requested_username = (string)$this->request->get['username'];

		$this->api_info = $this->model_user_api->getApiByUsername($requested_username);

		if (!$this->api_info) {
			$this->auth_error_key = 'error_auth';
			$this->auth_error_details = ['reason' => 'username_not_found'];

			return false;
		}

		$ip_data = [];
		$results = $this->model_user_api->getIps((int)$this->api_info['api_id']);

		foreach ($results as $result) {
			$ip_data[] = trim((string)$result['ip']);
		}

		if ($ip_data && !in_array(oc_get_ip(), $ip_data)) {
			$this->auth_error_key = 'error_auth';
			$this->auth_error_details = ['reason' => 'ip_not_allowed'];

			return false;
		}

		$time = (int)$this->request->get['time'];
		$window = (int)($this->config->get('module_ferrez_admin_rest_api_max_time_skew') ?: 450);

		if ($time < (time() - $window) || $time > (time() + $window)) {
			$this->auth_error_key = 'error_auth';
			$this->auth_error_details = ['reason' => 'timestamp_out_of_window'];

			return false;
		}

		$string  = (string)$this->request->get['route'] . "\n";
		$string .= (string)$this->request->get['call'] . "\n";
		$string .= $requested_username . "\n";
		$http_host = (string)($this->request->server['HTTP_HOST'] ?? '');
		$path = !empty($this->request->server['PHP_SELF']) ? rtrim(dirname($this->request->server['PHP_SELF']), '/') . '/' : '/';
		$post_md5 = $this->getRequestPayloadHash();

		$string .= $http_host . "\n";
		$string .= $path . "\n";
		$string .= (int)$this->request->get['store_id'] . "\n";
		$string .= (string)$this->request->get['language'] . "\n";
		$string .= (string)$this->request->get['currency'] . "\n";
		$string .= $post_md5 . "\n";
		$string .= $time . "\n";

		$expected = base64_encode(hash_hmac('sha1', $string, (string)$this->api_info['key'], true));
		$signature = rawurldecode((string)$this->request->get['signature']);

		if (!hash_equals($expected, $signature)) {
			$this->auth_error_key = 'error_auth';
			$this->auth_error_details = ['reason' => 'invalid_signature'];

			return false;
		}

		$this->model_user_api->addHistory((int)$this->api_info['api_id'], (string)$this->request->get['call'], oc_get_ip());

		return true;
	}

	protected function getRequestBodyData(): array {
		if (!empty($this->request->post)) {
			return $this->request->post;
		}

		if ($this->isJsonRequest()) {
			$decoded = json_decode($this->getRawRequestBody(), true);

			return is_array($decoded) ? $decoded : [];
		}

		return [];
	}

	private function getRequestPayloadHash(): string {
		if ($this->isJsonRequest()) {
			return md5($this->getRawRequestBody());
		}

		return md5(http_build_query($this->request->post));
	}

	private function getRawRequestBody(): string {
		if ($this->raw_request_body === null) {
			$raw_body = file_get_contents('php://input');
			$this->raw_request_body = is_string($raw_body) ? $raw_body : '';
		}

		return $this->raw_request_body;
	}

	private function isJsonRequest(): bool {
		$content_type = (string)($this->request->server['CONTENT_TYPE'] ?? $this->request->server['HTTP_CONTENT_TYPE'] ?? '');

		return stripos($content_type, 'application/json') !== false;
	}

	private function validateScopes(array $required_scopes): bool {
		if (!$required_scopes) {
			return true;
		}

		$configured = $this->config->get('module_ferrez_admin_rest_api_permissions');

		if (!$configured) {
			return false;
		}

		$permissions = [];

		if (is_string($configured)) {
			$decoded = json_decode($configured, true);

			if (is_array($decoded)) {
				$permissions = $decoded;
			}
		} elseif (is_array($configured)) {
			$permissions = $configured;
		}

		$username = (string)$this->api_info['username'];
		$user_scopes = $permissions[$username] ?? [];

		if (!is_array($user_scopes)) {
			return false;
		}

		foreach ($required_scopes as $scope) {
			if (!in_array($scope, $user_scopes)) {
				return false;
			}
		}

		return true;
	}
}
