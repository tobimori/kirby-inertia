<?php

namespace tobimori\Inertia;

use Exception;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Json;
use Kirby\Filesystem\F;
use Kirby\Http\Remote;
use Kirby\Http\Request;
use Kirby\Http\Response;
use Kirby\Toolkit\A;
use Kirby\Toolkit\LazyValue;
use Kirby\Toolkit\Str;

class Inertia
{
	protected string $component = 'default';
	protected array $props = [];
	protected array $sharedProps = [];
	protected array $ssrResponse = [];

	/**
	 * Get or set the current response props
	 */
	public function props(array $value = null): array
	{
		if ($value !== null) {
			$this->props = $value;
		}

		return $this->props;
	}

	/**
	 * Get the current instance shared props &
	 * merge them with the config shared props
	 */
	public function sharedProps(): array
	{
		return A::merge(App::instance()->option('tobimori.inertia.shared', []), $this->sharedProps);
	}

	/**
	 * Get or set the current response props
	 */
	public function component(string $value = null): string
	{
		if ($value !== null) {
			$this->component = $value;
		}

		return $this->component;
	}

	/**
	 * Get the Inertia version for comparison
	 */
	public function version(): string|null
	{
		$version = kirby()->option('tobimori.inertia.version', null);
		if (is_callable($version)) {
			return $version();
		}

		return $version;
	}

	/**
	 * Get the current component and props
	 */
	public function data(): array
	{
		// merge props
		$props = A::merge($this->props(), $this->sharedProps());

		// partial request
		$data = Str::split($this->request()->header('X-Inertia-Partial-Data'));
		if (!empty($data) && $this->request()->header('X-Inertia-Partial-Component') === $this->component) {
			$props = $this->filterProps($props, $data);
		}

		// unwrap lazy values
		$props = LazyValue::unwrap($props);

		// create data object
		$data = [
			'component' => $this->component(),
			'props' => $props,
			'url' => $this->request()->url()->toString(),
		];

		// add version if set
		if (($version = $this->version()) !== null) {
			$data['version'] = $version;
		}

		return $data;
	}

	/**
	 * Filter the props by the given keys
	 */
	protected function filterProps(array $props, array $only)
	{
		if (empty($only)) {
			return $props;
		}

		return A::filter($props, fn ($key) => A::has($only, $key), ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Returns the Inertia.js shell or the rendered component
	 */
	public function render(): string
	{
		// if the request is an client-side inertia request, only return the data
		if (
			$this->request()->method() === 'GET' &&
			$this->request()->header('X-Inertia')
		) {
			// TODO: verify version

			ob_clean(); // remove previous output, because render() is usually called in body
			die(Response::json($this->data(), null, null, [
				'Vary' => 'Accept',
				'X-Inertia' => 'true'
			]));
		}

		$ssr = App::instance()->option('tobimori.inertia.ssr.enabled', true);
		if (is_callable($ssr)) {
			$ssr = $ssr();
		}

		if ($ssr) {
			$response = $this->handleSsrRequest('body');
			if ($response !== null) {
				return $response;
			}
		}

		// generate the app container element
		$doc = new \DOMDocument();
		$root = $doc->createElement('div');
		$root->setAttribute('id', App::instance()->option('tobimori.inertia.id', 'app'));
		$root->setAttribute('data-page', Json::encode($this->data()));
		$doc->appendChild($root);
		return $doc->saveHTML($root);
	}

	/**
	 * Returns server-side rendered head tags from Inertia frontend
	 */
	public function head(): string
	{
		return $this->handleSsrRequest('head') ?? '';
	}

	/**
	 * Handles the SSR server request
	 */
	protected function handleSsrRequest(string $component = 'body'): string|null
	{
		if (!isset($this->ssrResponse[$component])) {
			try {
				// create a new request
				$url = Str::replace(App::instance()->option('tobimori.inertia.ssr.server', 'http://127.0.0.1:13714'), '/render', '') . '/render';
				$request = Remote::request($url, [
					'method' => 'POST',
					'data' => Json::encode($this->data()),
				]);
				$this->ssrResponse = $request->json();
			} catch (Exception $e) {
				return null;
			}
		}

		$response = $this->ssrResponse[$component];
		if (is_array($response)) { // head is an array of tags
			$response = implode("\n", $response);
		}

		return $response;
	}

	/**
	 * Create an Inertia response from a controller
	 */
	public static function createResponse(string $component, array $props = [], array $viewData = []): array
	{
		static::instance()->component($component);
		static::instance()->props($props);
		return $viewData;
	}

	/**
	 * Share data with the Inertia response, can be called anywhere
	 * before the render() method is called
	 */
	public static function share(string $key, mixed $value): void
	{
		static::instance()->sharedProps[$key] = $value;
	}

	/**
	 * Create a Kirby route that returns an Inertia response
	 * Works similar to https://inertiajs.com/routing#shorthand-routes
	 */
	public static function route(string $path, string $component = 'default', array $props = [], array $viewData = [])
	{
		return [
			'pattern' => $path,
			'language' => '*',
			'action' => function () use ($path, $component, $props, $viewData) {
				$viewData = Inertia::createResponse($component, $props);

				return Page::factory([
					'slug' => Str::slug($path),
					'template' => 'route',
					'model' => $component,
					'content' => []
				])->render($viewData);
			}
		];
	}

	/**
	 * Assigns the default template to each controller without a template,
	 * so that Kirby picks up the template controller correctly
	 */
	public static function controllerTemplates(): array
	{
		$templates = A::map(glob(kirby()->root('templates') . '/*.php'), [F::class, 'name']);

		return A::reduce(
			A::map(glob(kirby()->root('controllers') . '/*.php'), [F::class, 'name']),
			function ($array, $controller) use ($templates) {
				if (!A::has($templates, $controller)) {
					$array[$controller] = kirby()->root('templates') . '/default.php';
				}

				return $array;
			},
			[]
		);
	}

	/**
	 * Get the current request
	 */
	protected static function request(): Request
	{
		return App::instance()->request();
	}

	/**
	 * Singleton handling
	 */
	private static array $instances = [];

	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	public function __wakeup()
	{
		throw new Exception("Cannot unserialize a singleton.");
	}

	public static function instance(): static
	{
		$cls = static::class;
		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls] = new static();
		}

		return self::$instances[$cls];
	}
}
