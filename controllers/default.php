<?php

use Kirby\Cms\Page;
use tobimori\Inertia\Inertia;

return function (Page $page) {
	return Inertia::createResponse(
		$page->intendedTemplate(),
		$page->toArray()
	);
};
