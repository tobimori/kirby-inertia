<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use tobimori\Inertia\Inertia;

App::plugin('tobimori/inertia', [
	'options' => [
		'cache' => [
			'ssr' => true
		],
		'ssr' => [
			'enabled' => true,
			'server' => 'http://127.0.0.1:13714'
		],
		'id' => 'app',
		'version' => fn () => '1.0', // git hash
		'shared' => []
	],
	'controllers' => [
		'default' => require __DIR__ . '/controllers/default.php'
	],
	'templates' => Inertia::controllerTemplates()
]);

if (!function_exists('inertia')) {
	function inertia()
	{
		echo Inertia::instance()->render();
	}
}

if (!function_exists('inertiaHead')) {
	function inertiaHead()
	{
		echo Inertia::instance()->head();
	}
}
