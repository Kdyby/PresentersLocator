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
interface IPresenterProvider
{

	/**
	 * Return directory, or list of directories, that contains presenters.
	 *
	 * @return string|string[]
	 */
	public function getPresentersDirectory();

}
