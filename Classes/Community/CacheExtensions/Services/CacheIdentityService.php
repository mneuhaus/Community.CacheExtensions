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

	public function getIdentifierByObject($object) {
		$strategyClasses = $this->reflectionService->getAllImplementationClassNamesForInterface('\Community\CacheExtensions\Strategies\CacheIdentifierStrategyInterface');
		$strategies = array();
		foreach ($strategyClasses as $strategyClass) {
			$strategyClass = new $strategyClass();
			return $strategyClass->getIdentifier($object);
		}
	}

	public function getIdentifierForUserRoles() {
		$roles = $this->securityContext->getRoles();
		foreach ($roles as $key => $role) {
			$roles[$key] = $role->__toString();
		}
		return implode('-', $roles);
	}
}

?>