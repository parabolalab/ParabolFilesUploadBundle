<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

//TODO: check context & check __call method to auto add support for other context than "files"

trait Files {

	public $files;
    protected $filesUpdatedAt;
    protected $filesOrder;

    public function __call($property, $arguments)
    {
        $context = lcfirst(preg_replace('#^(get|set|remove)#', '', $property));
        

        // var_dump($property, $context, method_exists($this, 'fileContexts') && in_array($context, $this->getFilesContexts()));

        if(method_exists($this, 'fileContexts') && in_array($context, $this->getFilesContexts()))
        {

 

                $action = substr($property, 0, strlen($property) - strlen($context));
                $method = '__' . ($action ? $action : 'get') . 'File';

                if($this->isMultipleFilesAllowed($context) && in_array($property[0], ['g', 's'])) $method .= 's';

                // var_dump($method);

                if(method_exists($this, $method))
                {
                   $arguments[] = $context; 
                   return call_user_func_array([$this, $method], $arguments);
                } 

        }

          // die();

        return parent::__call($property, $arguments);

    }

    public function __addFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        if($this->{$context} === null) $this->{$context} =  new \Doctrine\Common\Collections\ArrayCollection();
        $this->{$context}->add($file);

        return $this;
    }

    public function __removeFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        $this->{$context}->removeElement($file);
    }

    public function __getFile($context = 'files')
    {
        return isset($this->{$context}[0]) ? $this->{$context}[0] : null;
    }

    public function __getFiles($context = 'files')
    {
        
        return $this->{$context};
    }

    public function __setFile(\Doctrine\Common\Collections\ArrayCollection $files, $context = 'files')
    {
        return $this->__setFiles($files, $context);
    }

    public function __setFiles(\Doctrine\Common\Collections\ArrayCollection $files, $context = 'files')
    {
        $this->{$context} = $files;

        return $this;
    }

    public static function allowMultipleFiles()
    {
        return isset(self::$allowMultipleFiles) ? self::$allowMultipleFiles : true;
    }

    public static function fileContexts()
    {
    	return ['files' => true];
    }

    public function getFilesContexts()
    {
        return array_keys(self::fileContexts());
    }

    public static function isMultipleFilesAllowed($context)
    {
        return self::fileContexts()[$context];
    }

     /**
     * Set filesUpdatedAt
     *
     * @param \DateTime $filesUpdatedAt
     *
     * @return Page
     */
    public function setFilesUpdatedAt($filesUpdatedAt)
    {
        $this->filesUpdatedAt = $filesUpdatedAt;

        return $this;
    }

    /**
     * Get filesUpdatedAt
     *
     * @return \DateTime
     */
    public function getFilesUpdatedAt()
    {
        return $this->filesUpdatedAt;
    }

    public function getFilesOrder()
    {
        return $this->filesOrder;
    }

    public function setFilesOrder($value)
    {
        $this->filesOrder = json_decode($value, true);
        return $this;
    }
}

?>