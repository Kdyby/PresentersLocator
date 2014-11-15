<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\PresentersLocator\DI;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PresentersLocatorExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = array(
		'scanAppDir' => TRUE,
		'scanComposerMap' => TRUE,
		'whitelist' => array(
			'^(?!KdybyModule\\\\)(.+Module)?.+Presenter\\z',
		)
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$presenterFactory = $builder->getDefinition('nette.presenterFactory');
		$presenterFactory->setFactory('Kdyby\PresentersLocator\PresenterFactory', $presenterFactory->getFactory()->arguments);
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		if (isset($builder->parameters['presentersLocator']) && $builder->parameters['presentersLocator'] === FALSE) {
			return; // production only
		}

		$config = $this->getConfig($this->defaults);
		$presentersSetup = $this->getPresentersConfig();

		$counter = 0;
		foreach ($this->getIndexedClasses() as $class) {
			if (!$class = $this->resolveRealClassName($class, $config)) {
				continue;
			}

			$def = $builder->addDefinition($this->prefix('presenter.' . (++$counter)))
				->setClass($class)
				->setInject(TRUE);

			if (!isset($presentersSetup[$lName = strtolower($class)])) {
				continue;
			}

			foreach ($presentersSetup[$lName] as $setup) {
				$def->addSetup($setup);
			}
		}

		if (!class_exists('Nette\DI\Extensions\InjectExtension')) {
			return; // the inject extension is not yet separated from Compiler in this version of Nette
		}

		$injectsExtension = FALSE;
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof Nette\DI\Extensions\InjectExtension) {
				$injectsExtension = $extension;
				continue;
			}

			if ($injectsExtension && $extension instanceof self) {
				// the InjectExtension is sadly registered before this one, and this one needs injects
				// because the InjectExtension::updateDefinition() is private and cannot be used explicitly
				$injectsExtension->beforeCompile();
				break;
			}
		}
	}



	/**
	 * @param string $class
	 * @param array $config
	 * @return NULL|string
	 */
	protected function resolveRealClassName($class, array $config)
	{
		$allowed = FALSE;
		foreach ($config['whitelist'] as $mask) {
			if (preg_match('~' . $mask . '~iu', $class)) {
				$allowed = TRUE;
				break;
			}
		}

		if (!$allowed) {
			return NULL; // no whitelist allowed the class to be registered
		}

		if (!class_exists($class)) {
			return NULL; // prevent meaningless exceptions
		}

		try {
			$refl = Nette\Reflection\ClassType::from($class);

		} catch (\ReflectionException $e) {
			return NULL;
		}

		if (!$refl->isInstantiable() || !$refl->implementsInterface(Nette\Application\IPresenter::class)) {
			return NULL; // class is not a presenter
		}

		if ($this->getContainerBuilder()->findByType($class, FALSE)) {
			return NULL; // presenter is already registered
		}

		return $refl->getName();
	}



	/**
	 * @return array
	 */
	private function getPresentersConfig()
	{
		$config = array_diff_key($this->getConfig(), $this->defaults);
		return array_change_key_case($config, CASE_LOWER);
	}



	/**
	 * @return string[]
	 */
	private function getIndexedClasses()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$presenterDirs = $config['scanAppDir'] ? array($builder->expand('%appDir%')) : array();

		/** @var IPresenterProvider $provider */
		foreach ($this->compiler->getExtensions('Kdyby\PresentersLocator\DI\IPresenterProvider') as $provider) {
			foreach ((array)$provider->getPresentersDirectory() as $dir) {
				if (!is_dir($dir)) {
					throw new \LogicException(sprintf('Return value "%s" of %s::getPresentersDirectory() is not a directory', $dir, get_class($provider)));
				}

				$presenterDirs[] = $dir;
			}
		}

		$indexed = array();
		if ($presenterDirs) {
			$robot = new Nette\Loaders\RobotLoader();
			$robot->addDirectory($presenterDirs);
			$robot->setCacheStorage(new Nette\Caching\Storages\MemoryStorage());
			$robot->rebuild();

			$indexed = array_merge($indexed, array_keys($robot->getIndexedClasses()));
		}

		if ($config['scanComposerMap'] && file_exists($composerClassmapFile = $builder->expand('%appDir%/../vendor/composer/autoload_classmap.php'))) {
			$indexed = array_merge($indexed, array_keys(self::loadFile($composerClassmapFile)));
		}

		return array_unique($indexed);
	}



	/**
	 * @param string $file
	 * @return array
	 */
	private static function loadFile($file)
	{
		return call_user_func(function () use ($file) {
			return require $file;
		});
	}

}
