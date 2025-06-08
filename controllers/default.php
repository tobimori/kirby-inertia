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
		$currentLangCode = $kirby->language()->code();
		
		// Cache translations at request level to avoid recomputing for multiple pages
		$cacheKey = 'page_translations_' . $page->id();
		$request = $kirby->request();
		
		if (!$request->data($cacheKey)) {
			$languages = $kirby->languages();
			$translations = [];
			
			foreach ($languages as $language) {
				$langCode = $language->code();
				$translation = $page->translation($langCode);
				$exists = $translation->exists();
				
				$translations[$langCode] = [
					'code' => $langCode,
					'exists' => $exists,
					'slug' => $exists ? $translation->slug() : null,
					'url' => $exists ? $page->url($langCode) : null,
				];
			}
			
			// Cache the computed translations for this request
			$request->data($cacheKey, $translations);
		}
		
		$translations = $request->data($cacheKey);
		$data['translations'] = $translations;
		
		// Add current translation info (reuse from translations if available)
		if (isset($translations[$currentLangCode])) {
			$data['translation'] = $translations[$currentLangCode];
		} else {
			$currentTranslation = $page->translation($currentLangCode);
			$exists = $currentTranslation->exists();
			$data['translation'] = [
				'code' => $currentLangCode,
				'exists' => $exists,
				'slug' => $exists ? $currentTranslation->slug() : null,
			];
		}
	}

	return Inertia::createResponse(
		$page->intendedTemplate(),
		$data
	);
};
