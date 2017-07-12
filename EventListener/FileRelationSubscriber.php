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

class FileRelationSubscriber implements EventSubscriber
{

	private $container, $analizer, $files = [], $values = [], $object;

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
            Events::loadClassMetadata,
            Events::postFlush,
            Events::onFlush,
            Events::postRemove,
        );
    }

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
                'targetEntity'  => 'Parabol\FilesUploadBundle\Entity\File',
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

    	    $dir = $this->container->get('parabol.utils.path')->getWebDir();

            
            foreach ($this->files as $context => $files) {

                if(!isset($this->values[$context])) $this->values[$context] = '';

        		foreach ($files as $file) {


                    $newDir = dirname(preg_replace('#([^/]+)$#', $this->object->getId().DIRECTORY_SEPARATOR.'$1',$file->getPath()));
                    $orgName = $slugizedName = basename($file->getPath());

                    $i = 1;
                    while(file_exists($dir . $newDir . DIRECTORY_SEPARATOR . $slugizedName))
                    {
                        $slugizedName = preg_replace('#(\.[a-z]+)$#', '-'.$i.'$1', $orgName);
                        $i++;
                    }
                    $file->setRef($this->object->getId());
    				$file->setPath($newDir . DIRECTORY_SEPARATOR . $slugizedName);
    				$file->setIsNew(false);
                

                    $this->values[$context] .= ($this->values[$context] ? "," : "") . "({$file->getId()}, {$this->object->getId()})";
    			}




            }

		    $this->files = []; 		


		    $em->flush();


            foreach($this->values as $context => $v)
            {
                if($v)
                {

                     $namingStrategy = $em
                        ->getConfiguration()
                        ->getNamingStrategy()
                    ;
 

                    $metadata = $em->getClassMetadata( get_class($this->object) );

                
                    $table = 'parabol_' . strtolower($namingStrategy->classToTableName($metadata->getName())) . '_'.$context;

                    $conn = $this->container->get('doctrine')->getConnection();
                    $conn->executeUpdate("INSERT IGNORE INTO {$table} (file_id, {$namingStrategy->joinKeyColumnName($metadata->getName())}) VALUES {$v}");

       
                    
                }
            }

            $this->values = [];


	
		}
		   
	    	
    }
    public function onFlush(OnFlushEventArgs $args)
    {

    	$em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        

        foreach ($uow->getScheduledEntityInsertions() as $inserted) {
            $refClass = new \ReflectionClass($inserted);

            // var_dump($refClass);



            if($this->analizer->hasTrait($refClass, 'Parabol\FilesUploadBundle\Entity\Base\Files') || $this->analizer->hasTrait($refClass, 'Parabol\FilesUploadBundle\Entity\Base\File'))
            {

                $class = $refClass->name;
                $this->object = $inserted;


                foreach($this->object->getFilesContexts() as $context)
                {
                    $this->files[$context] = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => '_'.hash('sha256', $this->container->get('session')->getId().'|'.$class), 'class' => $class, 'context' => $context, 'isNew' => true));  



                    $this->object->{'set' . ucfirst($context)}(new \Doctrine\Common\Collections\ArrayCollection($this->files[$context]));

                    // $uow->recomputeSingleEntityChangeSet( $em->getClassMetadata( $class ), $this->object);
       
                    
                }

            } 
            
        }



        foreach ($uow->getScheduledEntityUpdates() as $updated) {
            $refClass = new \ReflectionClass($updated);
            if($this->analizer->hasTrait($refClass, 'Parabol\FilesUploadBundle\Entity\Base\Files') || $this->analizer->hasTrait($refClass, 'Parabol\FilesUploadBundle\Entity\Base\File'))
	    	{
                $class = $refClass->name;
                $this->object = $updated;
                foreach($this->object->getFilesContexts() as $context)
                {
                    $this->files[$context] = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $updated->getId(), 'class' => get_class($updated), 'context' => $context, 'isNew' => true));
                }

	    	}
	    	elseif(get_class($updated) == 'Parabol\FilesUploadBundle\Entity\File')
	    	{
	    		$changeSet = $uow->getEntityChangeSet($updated);
            	if(isset($changeSet['path']))
            	{
            		$web_dir = $this->container->get('parabol.utils.path')->getWebDir();	
                    
                    if(file_exists($web_dir . $changeSet['path'][0]))
                    {
                		$dir = $web_dir . dirname($changeSet['path'][1]);
                		if(!file_exists($dir)) mkdir($dir, 0777, true);

                        $oldCropped = preg_replace('/(\.[\w\d]{3})$/', '-cropped$1', $changeSet['path'][0]);
                        if(file_exists($web_dir . $oldCropped)) rename($web_dir . $oldCropped, $web_dir .  preg_replace('/(\.[\w\d]{3})$/', '-cropped$1', $changeSet['path'][1]));
                		rename($web_dir . $changeSet['path'][0], $web_dir . $changeSet['path'][1]);

                    }

            	}
            	
	    	}
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
        
        if(get_class($args->getObject()) == 'Parabol\FilesUploadBundle\Entity\File')
        {
            $this->container->get('liip_imagine.cache.manager')->remove($args->getObject()->getPath());
            if($args->getObject()->getCropBoxData()) $this->container->get('liip_imagine.cache.manager')->remove($args->getObject()->getCroppedPath());
            $path = $this->container->get('parabol.utils.path')->getWebDir().$args->getObject()->getPath();
            if(file_exists($path)) unlink($path);
            @rmdir(dirname($path));
        }

    }
}