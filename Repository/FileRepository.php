<?php

namespace Parabol\FilesUploadBundle\Repository;

/**
 * FileRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FileRepository extends \Parabol\BaseBundle\Entity\Base\BaseRepository
{
	
	use \Parabol\DoctrineBehaviorsBundle\Sortable\Entity\SortableRepository;

	protected function addSortingScope(\Doctrine\ORM\QueryBuilder $qb, $entity)
    {
    	$qb
    		->andWhere('e.class = :class')
    		->andWhere('e.ref = :ref OR e.initRef = :initRef')
        ->andWhere('e.context = :context')
    		->setParameter('class', $entity->getClass())
    		->setParameter('ref', $entity->getRef())
            ->setParameter('initRef', $entity->getInitRef())
            ->setParameter('context', $entity->getContext())
    		;
    }
}