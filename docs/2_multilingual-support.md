# Multilingual Support

This plugin provides comprehensive support for Kirby's multilingual features, automatically handling language context and translations when working with Inertia.js.

## Features

- **Automatic Language Detection**: Current language context is automatically included in all Inertia responses
- **Translation Data**: Page-specific translation information including URLs and availability
- **Language Switching**: Easy access to all available languages and their URLs
- **Backward Compatible**: Only activates for multilingual sites, no impact on single-language sites

## Response Structure

When your site has multiple languages configured, Inertia responses automatically include:

### Global Language Data

```json
{
  "language": {
    "code": "fr",
    "direction": "ltr", 
    "locale": ["fr_FR"],
    "name": "Français",
    "url": "/fr"
  },
  "languages": [
    {
      "code": "fr",
      "direction": "ltr",
      "locale": ["fr_FR"],
      "name": "Français", 
      "url": "/fr",
      "isDefault": true
    },
    {
      "code": "en",
      "direction": "ltr",
      "locale": ["en_US"],
      "name": "English",
      "url": "/en", 
      "isDefault": false
    }
  ]
}
```

### Page-Specific Translation Data

Each page includes translation information:

```json
{
  "translations": {
    "fr": {
      "code": "fr",
      "exists": true,
      "slug": "services",
      "url": "/fr/services"
    },
    "en": {
      "code": "en", 
      "exists": true,
      "slug": "services",
      "url": "/en/services"
    }
  },
  "translation": {
    "code": "fr",
    "exists": true,
    "slug": "services"
  }
}
```

## Configuration

### Shared Multilingual Data

To include language context in shared data across all pages, add this to your `site/config/config.php`:

```php
use tobimori\Inertia\Inertia;

return [
    'tobimori.inertia' => [
        'shared' => array_merge([
            // Your other shared data  
            'site' => [
                'title' => site()->title()->value(),
                'url' => site()->url(),
            ],
            'navigation' => [
                // your navigation data
            ],
        ], Inertia::multilingualSharedData())
    ]
];
```

### Custom Multilingual Controllers

When creating custom controllers, the default multilingual data is automatically included. You can access language information like this:

```php
return function (Page $page) {
    $kirby = kirby();
    
    $data = [
        'title' => $page->title()->value(),
        // ... your page data
    ];
    
    // Multilingual data is automatically added by the plugin
    // But you can access current language if needed:
    if ($kirby->multilang()) {
        $currentLang = $kirby->language()->code();
        $data['customContent'] = $page->customField()->value($currentLang);
    }
    
    return Inertia::createResponse('YourComponent', $data);
};
```

## Frontend Usage

### Language Switching

Access language data in your Svelte components:

```svelte
<script>
    let { language, languages, translations } = $props();
    
    // Current language (available as 'language' in page props, 'currentLanguage' in shared data)
    console.log(language.code); // "fr"
    
    // All available languages
    languages.forEach(lang => {
        console.log(`${lang.name}: ${lang.url}`);
    });
    
    // Translation URLs for current page (only available in page-specific data)
    if (translations) {
        Object.values(translations).forEach(translation => {
            if (translation.exists) {
                console.log(`${translation.code}: ${translation.url}`);
            }
        });
    }
</script>

<!-- Language switcher -->
<nav class="language-switcher">
    {#each languages as lang}
        <a 
            href={translations?.[lang.code]?.url || lang.url}
            class:active={lang.code === language.code}
        >
            {lang.name}
        </a>
    {/each}
</nav>
```

### Direction Support

Handle RTL languages:

```svelte
<script>
    let { language } = $props();
</script>

<html dir={language.direction}>
    <!-- Your content -->
</html>
```

### Conditional Content

Show content based on language:

```svelte
<script>
    let { language } = $props();
</script>

{#if language.code === 'fr'}
    <p>Contenu en français</p>
{:else if language.code === 'en'}
    <p>English content</p>
{/if}
```

## Migration from Single Language

If you're migrating from a single-language site:

1. **No Breaking Changes**: Existing code continues to work unchanged
2. **Gradual Enhancement**: Add multilingual features incrementally
3. **Optional Usage**: Language data is available but not required

## Troubleshooting

### Empty Responses

If you're getting empty responses from Inertia on a multilingual site, ensure:

1. Your default language is properly configured in Kirby
2. Pages have content in the current language
3. The `default.php` template exists with `inertia()` helper

### Missing Translations

- Check that page content files exist for each language (e.g., `page.fr.txt`)
- Verify language configuration in `site/languages/`
- Use `translations` data to conditionally show language switcher links

### Language Switching

- Use translation URLs from the `translations` object for accurate page switching
- Fallback to language home URL if page doesn't exist in target language
- Consider implementing language detection and redirect logic