<?php

namespace Parabol\FilesUploadBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Knp\DoctrineBehaviors\ORM\AbstractSubscriber;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;
use Parabol\FilesUploadBundle\Entity\File;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Inflector\Inflector;

class FileRelationSubscriber implements EventSubscriber
{

    

    private $container, $analizer, $files = [], $ids = [], $managed = false;

    public function __construct($container, $analizer)
    {

        $this->analizer = $analizer;
        $this->container = $container;

    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            // Events::prePersist,
            // Events::preUpdate,
            Events::loadClassMetadata,
            // Events::postFlush,
            Events::onFlush,
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        );
    }

    // public function prePersist(LifecycleEventArgs $args)
    // {
        
        
    // }

    // public function preUpdate(LifecycleEventArgs $args)
    // {


    //     // $entity = $args->getEntity();
    //     // if($entity instanceof \Parabol\AdminCoreBundle\Entity\Page)
    //     // {
    //     //     var_dump($entity->getFilesOrder());
    //     // }
    //     // die();
    //     // $this->removeOldFileIfSingleFileInput($args);
    //     // $this->addFileToRefObject($args);
    // }

    private function removeOldFileIfSingleFileInput(EntityManager $em, File $file)
    {
          $class = $file->getClass();
          
            

            if($class && !$file->isMultiple())
            {
                $oldfiles  =  $em->getRepository('ParabolFilesUploadBundle:File')->createQueryBuilder('f')
                        ->select('f')
                        ->where('f.ref = :ref')
                        ->andWhere('f.class = :class')
                        ->andWhere('f.context = :context')
                        ->andWhere('f.id != :id')
                        ->setParameters(['ref' => $file->getRef(), 'class' => $class, 'context' => $file->getContext(), 'id' => $file->getId()])
                        ->getQuery()
                        ->getResult()
                    ;


                foreach ($oldfiles as $oldfile) {
                    $em->remove($oldfile);
                }
                    //$oldFile = $em->getRepository(File::class)->findOneBy(['ref' => $entity->getRef(), 'class' => $class, 'context' => $entity->getContext()])
            }
        

    }

    // private function addFileToRefEntity(LifecycleEventArgs $args)
    // {
    //      $entity = $args->getEntity()
    //      if($entity instanceof File)
    //      {
    //         $class = $entity->getClass();
    //         $em = $args->getEntityManager();
    //         if($class)
    //         {
    //             $refEntity = $this->getDoctrine()->getRepository( $class )->find( $entity->getRef() );
    //             if($refEntity) $refEntity->__addFile($file, $file->getContext());
    //         }
    //     }

    // }

    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        // the $metadata is the whole mapping info for this class
        $metadata = $args->getClassMetadata();

        if (!method_exists($metadata->getName(), 'fileContexts')) {
            return;
        }

        $class = $metadata->getName();
       
        $namingStrategy = $args
            ->getEntityManager()
            ->getConfiguration()
            ->getNamingStrategy()
        ;

        $metadata->mapField(array(
            'fieldName' => 'filesUpdatedAt',
            'type' => 'string',
            'length' => 19,
            'nullable' => true
        ));

        $metadata->mapField(array(
              'fieldName' => 'filesHash',
              'type' => 'string',
              'length' => 64,
              'nullable' => true
        ));



       

       

        preg_match('/[^\\\]+$/',$class, $match);
        
        $metadata->mapManyToMany(array(
            'targetEntity'  => File::class,
            'fieldName'     => File::FIELD_NAME,
            // 'mappedBy'    => Inflector::camelize($match[0]),
            'cascade'       => array('persist', 'remove'),
            'orderBy'       => array('sort' => $this->container->getParameter('parabol_files_upload.order')),
            'joinTable'     => array(
                'name'        => 'parabol_' . strtolower($namingStrategy->classToTableName($metadata->getName())) . '_'.File::FIELD_NAME,
                'joinColumns' => array(
                    array(
                        'name'                  => $namingStrategy->joinKeyColumnName($metadata->getName()),
                        'referencedColumnName'  => $namingStrategy->referenceColumnName(),
                        'onDelete'  => 'CASCADE',
                        'onUpdate'  => 'CASCADE',
                    ),
                ),
                'inverseJoinColumns'    => array(
                    array(
                        'name'                  => 'file_id',
                        'referencedColumnName'  => $namingStrategy->referenceColumnName(),
                        'onDelete'  => 'CASCADE',
                        'onUpdate'  => 'CASCADE',
                    ),
                )
            )
        ));
        


        //additional field map for a2lix 3.x

        $metadata->mapField(array(
              'fieldName' => 'filesOrder',
              'type' => 'text',
              'nullable' => true
        ));
    }


    public function postPersist(LifecycleEventArgs $args)
    {

        $this->manageFiles($args->getEntityManager(), $args->getEntity(), 'new');
        $this->updateColors($args->getEntityManager(), $args->getEntity());
        $this->updateFilesOrder($args->getEntityManager(), $args->getEntity());
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->updateColors($args->getEntityManager(), $args->getEntity());
        $this->updateFilesOrder($args->getEntityManager(), $args->getEntity());
        
    }


    private function hasFilesTrait($entity)
    {
        $refClass = new \ReflectionClass($entity);
        return $this->analizer->hasTrait($refClass, \Parabol\FilesUploadBundle\Entity\Base\Files::class) 
                || $this->analizer->hasTrait($refClass, \Parabol\FilesUploadBundle\Entity\Base\File::class);
        
    }

    private function updateColors(EntityManager $em, $entity)
    {
        if( $this->hasFilesTrait($entity) )
        {
            foreach((array)$entity->getFilesColor() as $context => $colors)
            {
                if($colors)
                {

                    $q = ''; $params = [];
                    
                    foreach ($colors as $id => $color) {
                       $q .= ",(?, ?)";
                       $params[] = $id;                        
                       $params[] = $color;
                    }


                    $stmt = $em->getConnection()
                        ->prepare("INSERT IGNORE INTO parabol_file (id, color) VALUES " . trim($q, ',') . " ON DUPLICATE KEY UPDATE color=VALUES(color) ")
                        ->execute($params);
                } 
            }

        }
           
    }

    private function updateFilesOrder(EntityManager $em, $entity)
    {

        if( $this->hasFilesTrait($entity) )
        {
            foreach((array)$entity->getFilesOrder() as $context => $order)
            {
                if($order['values'])
                {

                    $q = ''; $params = [];
                    
                    foreach ($order['values'] as $id => $sort) {
                       $q .= ",(?, ?)";
                       $params[] = $id;                        
                       $params[] = $sort;
                    }


                    $stmt = $em->getConnection()
                        ->prepare("INSERT IGNORE INTO parabol_file (id, sort) VALUES " . trim($q, ',') . " ON DUPLICATE KEY UPDATE sort=VALUES(sort) ")
                        ->execute($params);
                } 
            }

        }
            
        

        // // throw new \Exception("Error Processing Request", 1);
        
        // if(!empty($this->files))
        // {
        //     $em  = $args->getEntityManager();

        //     foreach ($this->files as $context => $files) {

        //         // if(!isset($this->values[$context])) $this->values[$context] = '';

        //         // var_/dump($files);

        //         // $num = count($files);

        //         foreach ($files as $file) {

        //             $newDir = strtr( dirname($file->getPath()), [ $file->getRef() => $this->object->getId() ] );
        //             $filename = $this->container->get('parabol.helper.blueimp_file')->getUniqueFilename( $this->container->get('parabol.utils.path')->getWebDir() . $newDir, $file->getFilename());
                    

        //             $file
        //                 ->setPath($newDir . DIRECTORY_SEPARATOR . $filename)
        //                 ->setRef($this->object->getId())
        //                 ->setIsNew(false)
        //                 ;
                
        //         }




        //     }

        //     $this->files = [];      
        //     $em->flush();


        //     // $this->values = [];


    
        // }
           
            
    }
    public function onFlush(OnFlushEventArgs $args)
    {


        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        // $sessionId = $this->container->get('session')->getId();
        // $fs = new Filesystem();
        

        // foreach ($uow->getScheduledEntityInsertions() as $inserted)
        // {
        //     if($this->hasFilesTrait($inserted))
        //     {
        //         $this->object = $inserted;
        //     }
            
        // } 
        foreach ($uow->getScheduledEntityUpdates() as $updated)
        {            
            $this->manageFiles($em, $updated);            
        }

        foreach ($uow->getScheduledEntityDeletions() as $deleted) 
        {
            if($this->hasFilesTrait($deleted))
            {
                $files = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $deleted->getId(), 'class' => get_class($deleted)));
                foreach($files as $file)
                {
                    $em->remove($file);
                } 
          
            } 
        }

    }


    private function manageFiles(EntityManager $em, $entity, $action = 'edit')
    {
        if($this->hasFilesTrait($entity))
        {
            $recompute = false;
            $uow = $em->getUnitOfWork();
            $sessionId = $this->container->get('session')->getId();
            $class = get_class($entity);

            $qb = $em->getRepository('ParabolFilesUploadBundle:File')
                  ->createQueryBuilder('f')
                  ->where('f.class = :class')
                  ->andWhere('f.ref = :ref')
                  ->andWhere('f.isNew = :isNew')
                  ->setParameters([
                    'ref' => $entity->getFilesHash(), 
                    'class' => $class, 
                    'isNew' => true,
                  ])
            ;

            if(isset($this->ids[0]))
            {
              $qb
                ->andWhere('f.id NOT IN (:ids)')
                ->setParameter('ids', $this->ids)
              ;
            }

            $files = $qb->getQuery()->getResult();

            if(count($files))
            {
       
                foreach ($files as $i => $file) {

                    $this->ids[] = $file->getId();

                    $file
                        ->setPath($this->getNewPathAndMove($entity, $file))
                        ->setRef($entity->getId())
                        ->setIsNew(false)
                    ;

                    $entity->__addFile($file, $file->getContext());
                    $this->removeOldFileIfSingleFileInput($em, $file);

                    unset($files[$i]);

                }
                

                if($action === 'edit')
                {
                    $recompute = true;
                } 

            }
            
            if($action === 'new')
            {
                $em->flush();
            }
            else
            {
                $filesToRemove = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('toRemove' => $this->container->get('parabol.helper.blueimp_file')->generateRef($sessionId, $entity->getId()), 'class' => $class, 'isNew' => false));

                foreach($filesToRemove as $file)
                {
                    $uow->scheduleForDelete($file);    
                    $recompute = true;
                } 
            }

            if($recompute)
            {
                $uow->computeChangeSets();
            }
        }

    }

    private function getNewPathAndMove($entity, File $file)
    {
            $fs = new Filesystem();

            $newDir = strtr( dirname($file->getPath()), [ $file->getRef() => $entity->getId() ] );
            $filename = $this->container->get('parabol.helper.blueimp_file')->getUniqueFilename( $this->container->get('parabol.utils.path')->getWebDir() . $newDir, $file->getFilename());
        
            $oldPath = $file->getPath();
            $newPath = $newDir . DIRECTORY_SEPARATOR . $filename;

            $webdir = $this->container->get('parabol.utils.path')->getWebDir();    

            if($fs->exists($webdir . $oldPath))
            {
                $dir = $webdir . dirname($newPath);
                if(!$fs->exists($dir)) $fs->mkdir($dir);

                // $oldCropped = preg_replace('/(\.[\w\d]{3})$/', '-cropped$1', $oldPath);
                // if($fs->exists($webdir . $oldCropped)) $fs->rename($webdir . $oldCropped, $webdir .  preg_replace('/(\.[\w\d]{3})$/', '-cropped$1', $newPath));
                $fs->rename($webdir . $oldPath, $webdir . $newPath);

                if($file->isImage())
                { 



                    $oldThumbPath = $this->container->get('parabol.utils.path')->trimScriptName($this->container->get('parabol.utils.path')->trimHost(
                                            $this->container->get('liip_imagine.cache.manager')->resolve($oldPath, 'admin_thumb')
                                    ));
                    $newThumbPath = $this->container->get('parabol.utils.path')->trimScriptName($this->container->get('parabol.utils.path')->trimHost(
                                            $this->container->get('liip_imagine.cache.manager')->resolve($newPath, 'admin_thumb')
                                    ));

                    if(!$fs->exists(dirname($webdir . $newThumbPath))) $fs->mkdir(dirname($webdir . $newThumbPath));


                    $fs->rename(
                        $webdir . $oldThumbPath, 
                        $webdir . $newThumbPath, 
                        true
                    );

                    if( count(glob($webdir . dirname($oldThumbPath) . '/*' )) === 0 ) $fs->remove($webdir . dirname($oldThumbPath));
                }
                
                if( count(glob($webdir . dirname($oldPath) . '/*' )) === 0 ) $fs->remove($webdir . dirname($oldPath));
            }

            return $newPath;
    }


    public function postRemove(LifecycleEventArgs $args) { 
        
        $entity = $args->getEntity();
        if($entity instanceof File)
        {
            $this->container->get('liip_imagine.cache.manager')->remove($entity->getPath());
            if($entity->getCropBoxData()) $this->container->get('liip_imagine.cache.manager')->remove($entity->getCroppedPath());
            $path = $this->container->get('parabol.utils.path')->getWebDir().$entity->getPath();
            if(file_exists($path)) unlink($path);
            @rmdir(dirname($path));
        }

    }
}