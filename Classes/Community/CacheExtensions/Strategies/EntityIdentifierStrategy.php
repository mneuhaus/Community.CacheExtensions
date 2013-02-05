<?php
namespace Community\CacheExtensions\Strategies;

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
 * @Flow\Aspect
 */
class EntityIdentifierStrategy implements CacheIdentifierStrategyInterface {
	/**
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @var \TYPO3\Flow\Cache\CacheManager
	 * @Flow\Inject
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * Only convert non-persistent types
	 *
	 * @param mixed $source
	 * @return boolean
	 */
	public function canIdentify($source) {
		if (is_object($source)) {
			$source = $this->reflectionService->getClassNameByObject($source);
		}

		if (!is_string($source)) {
			return FALSE;
		}

		return $this->isEntityClass($source);
	}

	public function isEntityClass($class) {
		return (
			$this->reflectionService->isClassAnnotatedWith($class, 'TYPO3\Flow\Annotations\Entity') ||
			$this->reflectionService->isClassAnnotatedWith($class, 'TYPO3\Flow\Annotations\ValueObject') ||
			$this->reflectionService->isClassAnnotatedWith($class, 'Doctrine\ORM\Mapping\Entity')
		);
	}

	/**
	 * @Flow\Around("method(TYPO3\Flow\Persistence\Doctrine\EntityManagerFactory->create())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function registerOnFlush(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$entityManager = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$eventManager = $entityManager->getEventManager();
		$entityManager->getEventManager()->addEventListener(array(\Doctrine\ORM\Events::onFlush), $this);
		return $entityManager;
	}

	public function getIdentifier($source) {
		$cache = $this->cacheManager->getCache('Community_CacheExtensions_EntityModificationTimestamps');

		if (is_object($source)) {
			$identifier = $this->persistenceManager->getIdentifierByObject($source);
		} else {
			$identifier = str_replace('\\', '_', $source);
		}
		$timestamp = $cache->get($identifier);

		return sha1($identifier . '-' . $timestamp);
	}

	/**
	 * An onFlush event listener
	 *
	 * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
	 * @return void
	 */
	public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $eventArgs) {
		$unitOfWork = $eventArgs->getEntityManager()->getUnitOfWork();
		$cache = $this->cacheManager->getCache('Community_CacheExtensions_EntityModificationTimestamps');

		foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
			$identifier = $this->persistenceManager->getIdentifierByObject($entity);
			$cache->set($identifier, time());


			$class = $this->reflectionService->getClassNameByObject($entity);
			foreach ($this->getAffectedClasses($class) as $class) {
				$class = str_replace('\\', '_', $class);
				$cache->set($class, time());
			}
		}

		foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
			$identifier = $this->persistenceManager->getIdentifierByObject($entity);
			$cache->set($identifier, time());

			$class = $this->reflectionService->getClassNameByObject($entity);
			foreach ($this->getAffectedClasses($class) as $class) {
				$class = str_replace('\\', '_', $class);
				$cache->set($class, time());
			}
		}

		foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
			$identifier = $this->persistenceManager->getIdentifierByObject($entity);
			if ($identifier !== NULL) {
				$cache->set($identifier, time());
			}

			$class = $this->reflectionService->getClassNameByObject($entity);
			foreach ($this->getAffectedClasses($class) as $class) {
				$class = str_replace('\\', '_', $class);
				$cache->set($class, time());
			}
		}
	}

	public function getAffectedClasses($baseClass, $level = 0, $classes = array()) {
		if ($level >= 2) {
			return $classes;
		}
		$schema = $this->reflectionService->getClassSchema($baseClass);
		if (is_object($schema)) {
			foreach ($schema->getProperties() as $propertyName => $propertySchema) {
				$subClass = NULL;
				if ($this->isEntityClass($propertySchema['type'])) {
					$subClass = $propertySchema['type'];
				}
				if ($this->isEntityClass($propertySchema['elementType'])) {
					$subClass = $propertySchema['elementType'];
				}

				if ($subClass !== NULL) {
					$classes = $this->getAffectedClasses($subClass, $level + 1, $classes);
					$classes[] = $subClass;
				}
			}
		}
		return array_unique($classes);
	}
}

?>