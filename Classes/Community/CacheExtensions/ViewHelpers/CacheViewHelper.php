<?php
namespace Community\CacheExtensions\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "Blog".                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 *
 */
class CacheViewHelper  extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {
	/**
	 * @var TYPO3\Flow\Cache\CacheManager
	 * @Flow\Inject
	 */
	protected $cacheManager;

	/**
	 * @var Community\CacheExtensions\Services\CacheIdentityService
	 * @Flow\Inject
	 */
	protected $cacheIdentityService;

	/**
	 * @param string $identifier
	 * @param string $classes
	 * @param mixed $object
	 * @param string $cacheIdentifier
	 * @param boolean $useRequest
	 * @param boolean $forceRender
	 * @return string Rendered string
	 */
	public function render($identifier = NULL, $classes = NULL, $object = NULL, $cacheIdentifier = 'Community_CacheExtensions_Default', $useRequest = TRUE, $forceRender = FALSE) {
		$cache = $this->cacheManager->getCache($cacheIdentifier);

		if ($identifier === NULL) {
			$identifier = '';
		}

		if ($object !== NULL) {
			$identifier .= $this->cacheIdentityService->getIdentifier($object);
		}

		if ($classes !== NULL) {
			foreach (explode(',', $classes) as $class) {
				$identifier .= $this->cacheIdentityService->getIdentifier($class);
			}
		}

		if ($useRequest === TRUE) {
			$identifier .= sha1($this->cacheIdentityService->convertValue($_REQUEST));
		}

		if (!$cache->has($identifier) || $forceRender === FALSE) {
			$cache->set($identifier, $this->renderChildren());
		}

		return $cache->get($identifier);
	}

}


?>