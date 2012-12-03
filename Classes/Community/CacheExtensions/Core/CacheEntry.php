<?php
namespace Community\CacheExtensions\Core;

/*                                                                        *
 * This script belongs to the Community.CacheExtensions package.          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Scope("singleton")
 */
class CacheEntry {
	/**
	 * @var \TYPO3\Flow\Object\ObjectManager
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * The runtimeCache
	 *
	 * @var mixed
	 */
	protected $runtimeCache = NULL;

	/**
	 * The tags
	 *
	 * @var array
	 */
	protected $tags = array();

	/**
	 * The cache
	 *
	 * @var \TYPO3\Flow\Cache\Frontend\FrontendInterface
	 */
	protected $cache;

	/**
	 * @param string $cacheIdentifier
	 * @param \TYPO3\Flow\Cache\CacheManager $cacheManager
	 */
	public function __construct($cacheIdentifier, \TYPO3\Flow\Cache\CacheManager $cacheManager) {
		$this->cache = $cacheManager->getCache($cacheIdentifier);
	}

	public function setArguments($arguments) {
		$tags = array_merge($this->tags, $arguments);
	}

	public function set($value) {
		$this->runtimeCache = $value;
		$this->cache->set($this->getEntryIdentifier(), $value);
	}

	public function get() {
		if ($this->runtimeCache === NULL) {
			$this->runtimeCache = $this->cache->get($this->getEntryIdentifier());
		}
		return $this->runtimeCache;
	}

	public function exists() {
		return FALSE;
	}

	public function addRoleConditions() {
		$securityContext = $this->objectManager->get('\TYPO3\Flow\Security\Context');
		$roles = $securityContext->getRoles();
		foreach ($roles as $key => $role) {
			$this->tags[] = $role->__toString();
		}
	}

	public function getEntryIdentifier() {
		return $this->sanitizeTags($this->tags);
	}

	/**
	*/
	public function sanitizeTags($value) {
		switch (TRUE) {
			case is_array($value):
				foreach ($value as $k => $v) {
					$value[$k] = $this->sanitizeTags($v);
				}
				return implode('_', $value);
			case is_object($value):
				return spl_object_hash($value);
			case is_int($value):

			case is_float($value):

			case is_string($value):

			case is_bool($value):
				return preg_replace('/[\\/\\/:\\.\\\\\\?%=]+/', '_', strval($value));
			default:
				return '';
		}
	}
}

?>