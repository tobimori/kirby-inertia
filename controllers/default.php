<?php

use Kirby\Cms\Page;
use tobimori\Inertia\Inertia;

return function (Page $page) {
	if ($page->intendedTemplate()->name() === 'route') {
		return null;
	}

	// Get page data with multilingual support
	$data = $page->toArray();
	
	// Add multilingual page-specific data if site is multilingual
	$kirby = kirby();
	if ($kirby->multilang()) {
		// Add translations info for this page
		$translations = [];
		foreach ($kirby->languages() as $language) {
			$translation = $page->translation($language->code());
			$translations[$language->code()] = [
				'code' => $language->code(),
				'exists' => $translation->exists(),
				'slug' => $translation->exists() ? $translation->slug() : null,
				'url' => $translation->exists() ? $page->url($language->code()) : null,
			];
		}
		$data['translations'] = $translations;
		
		// Add current translation info
		$currentTranslation = $page->translation($kirby->language()->code());
		$data['translation'] = [
			'code' => $kirby->language()->code(),
			'exists' => $currentTranslation->exists(),
			'slug' => $currentTranslation->exists() ? $currentTranslation->slug() : null,
		];
	}

	return Inertia::createResponse(
		$page->intendedTemplate(),
		$data
	);
};
