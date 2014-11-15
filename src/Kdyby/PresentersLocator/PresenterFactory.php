<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\PresentersLocator;

use Kdyby;
use Nette;
use Nette\Application\IPresenter;
use Nette\Application\UI;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PresenterFactory extends Nette\Application\PresenterFactory
{

	/**
	 * @var Nette\DI\Container
	 */
	private $container;



	public function __construct($baseDir, Nette\DI\Container $container)
	{
		parent::__construct($baseDir, $container);
		$this->container = $container;
	}



	/**
	 * Creates new presenter instance.
	 *
	 * @param  string  presenter name
	 * @return IPresenter
	 */
	public function createPresenter($name)
	{
		$class = $this->getPresenterClass($name);
		if (count($services = $this->container->findByType($class)) === 1) {
			$presenter = $this->container->createService($services[0]);
			$tags = $this->container->findByTag(Nette\DI\Extensions\InjectExtension::TAG_INJECT);
			if (empty($tags[$services[0]])) {
				$this->container->callInjects($presenter);
			}

		} else {
			$presenter = $this->container->createInstance($class);
			$this->container->callInjects($presenter);
		}

		if ($presenter instanceof UI\Presenter && $presenter->invalidLinkMode === NULL) {
			$presenter->invalidLinkMode = $this->container->parameters['debugMode'] ? UI\Presenter::INVALID_LINK_WARNING : UI\Presenter::INVALID_LINK_SILENT;
		}

		return $presenter;
	}

}
