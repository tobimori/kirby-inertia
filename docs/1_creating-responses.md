---
title: Creating Responses
---

We're creating Inertia responses inside Kirby controllers. You can use the `Inertia::createResponse()` method to correctly format the response.
The default controller simply runs the `toArray()` methods on the Pages. You can override this by creating a custom controller for your page templates.

```php
<?php

use Kirby\Cms\Page;
use tobimori\Inertia\Inertia;

return function (Page $page) {
	return Inertia::createResponse(
		$page->intendedTemplate(), // the component name passed to inertia
		$page->toArray() // the props passed to the component
	);
};
```

If you need to access any additional data in the Kirby template, you can pass it on by returning an array as the third argument.

### Lazy Evaluation

Inertia allows partial requesting of data from the server. In that case, if you're running resource-heavy operations in your controller, you might want to delay them until they're actually needed. You can do this by utilizing Kirby's [`LazyValue`](https://getkirby.com/docs/reference/objects/toolkit/lazy-value) class as props.

```php
Inertia::createResponse(
	'page',
	[
		'title' => $page->title(), // will be evaluated immediately
		'children' => new LazyValue(function () use ($page) { // will be evaluated when requested
			return $page->children()->listed();
		})
	]
);
```

### Shared data

Shared data can be defined anywhere in your application before the first `inertia()` or `inertiaHead()` call in the template.
You can use the `Inertia::share()` method to define shared data.

```php
Inertia::share('user', kirby()->user()->id());
```

You can also use the config option `tobimori.inertia.shared` to define shared data in your `site/config/config.php` file.

```php
// site/config/config.php
return [
	'tobimori.inertia.shared' => [
		'user' => new LazyValue(function () { // lazy evaluation works here as well
			return kirby()->user()->id();
		})
	]
];
```

Keep in mind that you can't access the Kirby instance in the config file, so we're using the `LazyValue` class here.

### Server-side rendering

Inertia allows you to render your application on the server-side. This is especially useful for SEO and performance reasons.
To enable server-side rendering, you need to set the `tobimori.inertia.ssr.enabled` config option to `true`.

```php
// site/config/config.php
return [
	'tobimori.inertia.ssr' => [
		'enabled' => true,
		'server' => 'http://127.0.0.1:13714'
	],
];
```

You'll also have to specify the URL of the server-side renderer.

Kirby Inertia doesn't provide a command to start the server-side renderer like the Laravel package, you'll have to implement that yourself using a process manager like [PM2](https://pm2.keymetrics.io/).

### Custom routes

If you want to use custom routes for your Inertia application (e.g. when data is defined in the component), you can use the `Inertia::route()` helper to define a custom route in your `site/config/config.php` file.

```php
// site/config/config.php
return [
	'routes' => [
		Inertia::route('about', 'about') // renders the about component on /about
	]
];
```

### Caching

Support for Kirby caching is currently WIP, stuff might not work with the pages cache enabled.

### Auto Templates

This plugin automatically assigns the default template to all pages with a controller that don't have a template assigned. This is necessary so Kirby picks up the correct controller.
