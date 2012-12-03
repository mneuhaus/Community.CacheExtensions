<?php
namespace Community\CacheExtensions\Services;

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
 *
 * @Flow\Scope("singleton")
 */
class CachingService {

	/**
	 * The cache
	 *
	 * @var \TYPO3\Flow\Cache\Frontend\FrontendInterface
	 */
	protected $cache;

	/**
	 * @var \TYPO3\Flow\Cache\CacheManager
	 * @Flow\Inject
	 */
	protected $cacheManager;

	public function getEntry($cacheIdentifier, $arguments) {
		$cache = $this->cacheManager->getCache($cacheIdentifier);
		$cache = new \Community\CacheExtensions\Core\CacheEntry();
	}

	/**
	 * Around advice
	 *
	 * @Flow\Around("methodAnnotatedWith(TYPO3\Expose\Annotations\Cache)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 */
	public function cacheMethodsAnnotatedWithCacheAnnotation(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$cache = $this->cacheManager->getCache($this->createCacheName($joinPoint));
		$cacheIdentifier = $this->createCacheIdentifier($joinPoint);
		if ($cache->has($cacheIdentifier)) {
			return $cache->get($cacheIdentifier);
		}
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$cache->set($cacheIdentifier, $result);
		return $result;
	}

	/**
	* TODO: Document this Method! ( convertValue )
	*/
	public function convertValue($value) {
		switch (TRUE) {
			case is_array($value):
				foreach ($value as $k => $v) {
					$value[$k] = $this->convertValue($v);
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

	/**
	* TODO: Document this Method! ( createCacheIdentifier )
	*/
	public function createCacheIdentifier($joinPoint) {
		$parts = array(
			$this->convertValue($joinPoint->getClassName()),
			$this->convertValue($joinPoint->getMethodName()),
			$this->convertValue($joinPoint->getMethodArguments())
		);

		$annotation = $this->reflectionService->getMethodAnnotation($joinPoint->getClassName(), $joinPoint->getMethodName(), 'TYPO3\Expose\Annotations\Cache');

		if (stristr($annotation->context, 'role')) {
			$securityContext = $this->objectManager->get('\TYPO3\Flow\Security\Context');
			$roles = $securityContext->getRoles();
			foreach ($roles as $key => $role) {
				$roles[$key] = $role->__toString();
			}
			$parts[] = $this->convertValue($roles);
		}

		return sha1(implode('', $parts));
	}

	/**
	* TODO: Document this Method! ( createCacheName )
	*/
	public function createCacheName($joinPoint) {
		return sprintf('%s-%s', $this->convertValue($joinPoint->getClassName()), $this->convertValue($joinPoint->getMethodName()));
	}

}

?>