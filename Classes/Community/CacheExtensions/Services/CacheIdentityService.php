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
class CacheIdentityService {
	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	public function getIdentifier($source) {
		$strategyClasses = $this->reflectionService->getAllImplementationClassNamesForInterface('\Community\CacheExtensions\Strategies\CacheIdentifierStrategyInterface');
		$strategies = array();
		foreach ($strategyClasses as $strategyClass) {
			$strategyClass = new $strategyClass();
			if ($strategyClass->canIdentify($source)) {
				return $strategyClass->getIdentifier($source);
			}
		}
	}

	public function getIdentifierForUserRoles() {
		$roles = $this->securityContext->getRoles();
		foreach ($roles as $key => $role) {
			$roles[$key] = $this->convertValue($role->__toString());
		}
		return implode('-', $roles);
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
}

?>