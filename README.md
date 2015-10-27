# Laravel 5+ View Generator
[![Latest Stable Version](https://poser.pugx.org/maddhatter/laravel-view-generator/v/stable)](https://packagist.org/packages/maddhatter/laravel-view-generator) [![Total Downloads](https://poser.pugx.org/maddhatter/laravel-view-generator/downloads)](https://packagist.org/packages/maddhatter/laravel-view-generator) [![Latest Unstable Version](https://poser.pugx.org/maddhatter/laravel-view-generator/v/unstable)](https://packagist.org/packages/maddhatter/laravel-view-generator) [![License](https://poser.pugx.org/maddhatter/laravel-view-generator/license)](https://packagist.org/packages/maddhatter/laravel-view-generator)

This is a tiny package to add a `php artisan make:view` command to quickly create blade views.

## Installing

Require the package with composer using the following command:

    composer require maddhatter/laravel-view-generator --dev

Or add the following to your composer.json's require section and `composer update`

```json
"require-dev": {
	"maddhatter/laravel-view-generator": "dev-master"
}
```

Then register the service provider in your `app/Providers/AppServiceProvider.php` to only be included for the local environment:

```php
public function register()
{
    if ($this->app->environment() == 'local') {
        $this->app->register(\MaddHatter\ViewGenerator\ServiceProvider::class);
    }
}
```

Or if you always want it included regardless of environment, just add it to the `providers` array in `config/app.php`

## Usage


### Create a New View

```
php artisan make:view path.to.your.view
```

Use the same dotted notation to your view that you would pass to the `view()` command. The directory will be created if it doesn't already exist.

Note: If there are multiple paths defined in your `config/view.php`'s `paths` array, this package will use the first path.

### Extend Another View

```
php artisan make:view path.to.your.view -e path.to.parent.view
```

You can optionally extend another view by adding the `-e` parameter and providing the name of the view you want to extend. It will parse the parent view for `@yield()` directives and create the corresponding `@section` / `@endsection` tags.

#### Example 

Imagine you have the following layout defined:

resources/views/layouts/master.blade.php

```
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body>

  <div id="content">
    @yield('content')
  </div>

  <script src="{{ elixir('js/app.js') }}"></script>
  @yield('scripts')
</body>
</html>

```

And you run:

```
php artisan make:view pages.home -e layouts.master
```

The following will be created:

resources/views/pages/home.blade.php

```
@extends('layouts.master')

@section('content')
@endsection

@section('scripts')
@endsection
```