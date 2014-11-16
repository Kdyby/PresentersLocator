Quickstart
==========

This addon locates all presenters and registers them as services in `nette/di`.



Installation
-----------

The best way to install Kdyby/PresentersLocator is using  [Composer](http://getcomposer.org/):

```js
"require": {
	"nette/nette": "dev-master#e23de7ab as 2.2.99",
	"nette/di": "dev-master#97994498 as 2.3.99",
	"nette/neon": "~2.3@dev",
	"nette/utils": "~2.3@dev",
	"kdyby/presenters-locator": "@dev"
}
```

First of all, you have to register the new extensions from `nette/di:@dev`

```php
$configurator = new Nette\Configurator();
$configurator->defaultExtensions['decorator'] = Nette\DI\Extensions\DecoratorExtension::class;
$configurator->defaultExtensions['inject'] = Nette\DI\Extensions\InjectExtension::class;

// ...
// the rest of app/bootstrap.php
```

Then you can enable the extension using your neon config.

```yml
extensions:
	presenters: Kdyby\PresentersLocator\DI\PresentersLocatorExtension
```

And then it should start working.
