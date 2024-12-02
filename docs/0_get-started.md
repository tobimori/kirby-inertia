---
title: Get Started
---

## What is Inertia.js?

Inertia is a new approach to building classic server-driven web apps. It works similar to the backend architecture for [Kirby's Panel](https://getkirby.com/releases/3.6/fiber) - but built with an ecosystem around it that works with Vue, React and Svelte.

Raw JSON data is returned from the server and rendered on the client-side. This allows you to build SPAs without having to build a separate API.
Utilizing the server-side renderer, this makes perfect for websites that are heavily interactive but shouldn't suffer from SEO or performance issues.

This plugin is used in production at some of our agencies' projects, but you might stumble upon some issues! Please [open an issue](https://github.com/tobimori/kirby-inertia/issues) if you encounter any.

## Usage

### Setup

I recommend using [Vite](https://vitejs.dev/) & [kirby-laravel-vite](https://github.com/lukaskleinschmidt/kirby-laravel-vite) for compiling your frontend assets. It's the best way to get started with a powerful build setup without having to worry too much about the configuration.

To get started with Inertia, simply install the plugin using composer (`composer require tobimori/kirby-inertia`) and setup your `default.php` template to include the Inertia response:

```php
<!DOCTYPE html>
<html>

<head>
	<?php /* other meta tags */ ?>

	<?php /* kirby-laravel-vite */ ?>
	<?= vite()->reactRefresh() ?>
	<?= vite(['src/index.tsx', 'src/styles/index.css']) ?>

	<?= inertiaHead() ?> <?php /* for server-side-rendered head */ ?>
</head>

<body>
	<?= inertia() ?>  <?php /* renders the application shell or server-side-rendered content */ ?>
</body>

</html>
```

You can then get started building your [Inertia frontend](https://inertiajs.com/client-side-setup).
