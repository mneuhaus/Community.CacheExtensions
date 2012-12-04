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
	 * @param string $targetType
	 * @return boolean
	 */
	public function canIdentify($object) {
		return (
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\Entity') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'TYPO3\Flow\Annotations\ValueObject') ||
			$this->reflectionService->isClassAnnotatedWith($targetType, 'Doctrine\ORM\Mapping\Entity')
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

	public function getIdentifier($object) {
		$identifier = $this->persistenceManager->getIdentifierByObject($object);
		$cache = $this->cacheManager->getCache('Community_CacheExtensions_EntityModificationTimestamps');
		$timestamp = $cache->get($identifier);
		return $identifier . '-' . $timestamp;
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
			$class = str_replace("\\", "_", $class);
			$cache->set($class, time());
		}

		foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
			$identifier = $this->persistenceManager->getIdentifierByObject($entity);
			$cache->set($identifier, time());

			$class = $this->reflectionService->getClassNameByObject($entity);
			$class = str_replace("\\", "_", $class);
			$cache->set($class, time());
		}

		foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
			$identifier = $this->persistenceManager->getIdentifierByObject($entity);
			$cache->set($identifier, time());

			$class = $this->reflectionService->getClassNameByObject($entity);
			$class = str_replace("\\", "_", $class);
			$cache->set($class, time());
		}
	}
}

?>