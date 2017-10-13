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

class FileRelationSubscriber implements EventSubscriber
{

    private $container, $analizer, $files = [], $values = [];

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
            Events::prePersist,
            Events::loadClassMetadata,
            Events::postFlush,
            Events::onFlush,
            Events::postRemove,
        );
    }

    public function prePersist(LifecycleEventArgs $arg)
    {
        $this->removeOldFileIfSingleFileInput($arg);
        // $this->addFileToRefObject($arg);
    }

    private function removeOldFileIfSingleFileInput(LifecycleEventArgs $arg)
    {
        //  $entity = $arg->getEntity();
        //  if($entity instanceof File)
        //  {
        //     $class = $entity->getClass();
        //     $em = $arg->getEntityManager();
        //     if($class && !$class::isMultipleFilesAllowed($entity->getContext()) && $entity->getRef()
        //        && $oldFile = $em->getRepository(File::class)->findOneBy(['ref' => $entity->getRef(), 'class' => $class, 'context' => $entity->getContext()])) {
        //             $em->remove($oldFile);
        //     }
        // }

    }

    // private function addFileToRefEntity(LifecycleEventArgs $arg)
    // {
    //      $entity = $arg->getEntity()
    //      if($entity instanceof File)
    //      {
    //         $class = $entity->getClass();
    //         $em = $arg->getEntityManager();
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

        $contexts = array_keys($class::fileContexts());

        foreach($contexts as $context)
        {

            $metadata->mapManyToMany(array(
                'targetEntity'  => File::class,
                'fieldName'     => $context,
                'cascade'       => array('persist', 'remove'),
                'orderBy'       => array('sort' => 'DESC'),
                'joinTable'     => array(
                    'name'        => 'parabol_' . strtolower($namingStrategy->classToTableName($metadata->getName())) . '_'.$context,
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
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {

        // throw new \Exception("Error Processing Request", 1);
        
        if(!empty($this->files))
        {
            $em  = $args->getEntityManager();

            // $dir = $this->container->get('parabol.utils.path')->getWebDir();

            
            foreach ($this->files as $context => $files) {

                // if(!isset($this->values[$context])) $this->values[$context] = '';

                // var_/dump($files);

                foreach ($files as $file) {

                    // $newDir =  dirname(strtr(preg_replace('#\/'.$this->object->getId().'#', '', $file->getPath()), ['/' . strtolower($context) . '/' => '/' . strtolower($context) . '/' . $this->object->getId() . '/']));
                    
                    // if(!file_exists($dir . $newDir)) mkdir($dir . $newDir, 0777, true);

                    // if($newDir !== dirname($file->getPath()))
                    // {
                    //         $orgName = $slugizedName = basename($file->getPath());

                    //         $i = 1;
                    //         while(file_exists($dir . $newDir . DIRECTORY_SEPARATOR . $slugizedName))
                    //         {
                    //             $slugizedName = preg_replace('#(\.[a-z]+)$#', '-'.$i.'$1', $orgName);
                    //             $i++;
                    //         }

                    //         $file->setPath($newDir . DIRECTORY_SEPARATOR . $slugizedName);
                    // }
                   
                    $file
                        ->setRef($this->object->getId())
                        ->setIsNew(false)
                        ;
                

                    // $this->values[$context] .= ($this->values[$context] ? "," : "") . "({$file->getId()}, {$this->object->getId()})";
                }




            }

            $this->files = [];      
            $em->flush();


            // $this->values = [];


    
        }
           
            
    }
    public function onFlush(OnFlushEventArgs $args)
    {

        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        

        foreach ($uow->getScheduledEntityInsertions() as $inserted) {
            $refClass = new \ReflectionClass($inserted);

            if($this->analizer->hasTrait($refClass, Parabol\FilesUploadBundle\Entity\Base\Files::class) || $this->analizer->hasTrait($refClass, Parabol\FilesUploadBundle\Entity\Base\File::class))
            {

                $class = $refClass->name;
                $sessionId = $this->container->get('session')->getId();
                $this->object = $inserted;

                foreach($this->object->getFilesContexts() as $context)
                {
                    $this->files[$context] = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $this->container->get('parabol.helper.blueimp_file')->generateRef($sessionId, $class), 'class' => $class, 'context' => $context, 'isNew' => true));  
                        $this->object->{'set' . ucfirst($context)}(new \Doctrine\Common\Collections\ArrayCollection($this->files[$context]));

                    // // $uow->recomputeSingleEntityChangeSet( $em->getClassMetadata( $class ), $this->object);
       
                    
                }

                

            } 
            
        }

        // var_dump($this->files);
// die();
        


        foreach ($uow->getScheduledEntityUpdates() as $updated) {
            var_dump($updated);
            die();
            $refClass = new \ReflectionClass($updated);
            if($this->analizer->hasTrait($refClass, Parabol\FilesUploadBundle\Entity\Base\Files::class) || $this->analizer->hasTrait($refClass, Parabol\FilesUploadBundle\Entity\Base\File::class))
            {
                $class = $refClass->name;
                $this->object = $updated; die();
                foreach($this->object->getFilesContexts() as $context)
                {
                    $this->files[$context] = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $this->container->get('parabol.helper.blueimp_file')->generateRef($sessionId, $class), 'class' => $class, 'context' => $context, 'isNew' => true));

                    // $this->files[$context] = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $updated->getId(), 'class' => get_class($updated), 'context' => $context));
                    // if($this->object->{'get' . ucfirst($context)}() == null) $this->object->{'set' . ucfirst($context)}(new \Doctrine\Common\Collections\ArrayCollection($this->files[$context]));
                }

               //  var_dump($_POST, $this->object->getFiles());
               // die();

            }
        //     elseif($updated instanceof File)
        //     {
        //         $changeSet = $uow->getEntityChangeSet($updated);
        //         if(isset($changeSet['path']))
        //         {
        //             $web_dir = $this->container->get('parabol.utils.path')->getWebDir();    
                    
        //             if(file_exists($web_dir . $changeSet['path'][0]))
        //             {
        //                 $dir = $web_dir . dirname($changeSet['path'][1]);
        //                 if(!file_exists($dir)) mkdir($dir, 0777, true);

        //                 $oldCropped = preg_replace('/(\.[\w\d]{3})$/', '-cropped$1', $changeSet['path'][0]);
        //                 if(file_exists($web_dir . $oldCropped)) rename($web_dir . $oldCropped, $web_dir .  preg_replace('/(\.[\w\d]{3})$/', '-cropped$1', $changeSet['path'][1]));
        //                 rename($web_dir . $changeSet['path'][0], $web_dir . $changeSet['path'][1]);

        //             }

        //         }
                
        //     }
        }


        foreach ($uow->getScheduledEntityDeletions() as $deleted) {
            $refClass = new \ReflectionClass($deleted);
            if(empty($this->files) && $this->analizer->hasTrait($refClass, 'Parabol\FilesUploadBundle\Entity\Base\Files') || $this->analizer->hasTrait($refClass, 'Parabol\FilesUploadBundle\Entity\Base\File'))
            {

                $files = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $deleted->getId(), 'class' => get_class($deleted)));
                foreach($files as $file)
                {
                    $em->remove($file);
                } 
          
            } 
        }


    }


    public function postRemove(LifecycleEventArgs $args) { 
        
        if($args->getObject() instanceof File)
        {
            $this->container->get('liip_imagine.cache.manager')->remove($args->getObject()->getPath());
            if($args->getObject()->getCropBoxData()) $this->container->get('liip_imagine.cache.manager')->remove($args->getObject()->getCroppedPath());
            $path = $this->container->get('parabol.utils.path')->getWebDir().$args->getObject()->getPath();
            if(file_exists($path)) unlink($path);
            @rmdir(dirname($path));
        }

    }
}