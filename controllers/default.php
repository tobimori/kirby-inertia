<?php

use Kirby\Cms\Page;
use tobimori\Inertia\Inertia;

return function (Page $page) {
	return $page->intendedTemplate()->name() !== 'route' ? Inertia::createResponse(
		$page->intendedTemplate(),
		$page->toArray()
	) : null;
};
