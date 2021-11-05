<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

use \Doctrine\Common\Collections\ArrayCollection;

//TODO: check context & check __call method to auto add support for other context than "files"

trait Files {

	protected $files;
    protected $filesUpdatedAt;
    protected $filesOrder;
    protected $filesOrderHelper;
    protected $filesColorHelper;
    protected $filesColorHelperHelper;
    protected $filesHash;

    public function __call($property, $arguments)
    {
        $context = lcfirst(preg_replace('#^(get|set|remove)#', '', $property));
        

        // var_dump($property, $context, method_exists($this, 'fileContexts') && in_array($context, $this->getFilesContexts()));

        if(method_exists($this, 'fileContexts') && in_array($context, $this->getFilesContexts()))
        {

            $action = substr($property, 0, strlen($property) - strlen($context));
            $method = '__' . ($action ? $action : 'get') . 'File';

            if($this->isMultipleFilesAllowed($context) && !in_array(substr($property, 0,3), ['add', 'rem'])) $method .= 's';

            if(method_exists($this, $method))
            {
               $arguments[] = $context; 
               return call_user_func_array([$this, $method], $arguments);
            }

        }

        return get_parent_class($this) ? parent::__call($property, $arguments) : null;

    }

    private function createFilesArray()
    {
        if($this->files === null) $this->files = new ArrayCollection();
    }

    public function __addFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        $this->createFilesArray();
        $this->files->add($file);

        return $this;
    }

    public function __removeFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        $this->files->removeElement($file);
    }

    public function __getFile($context = 'files')
    {   
        $this->createFilesArray();
        foreach($this->files as $file)
        {
            if($file->getContext() === $context) return $file;
        }
        return null;
    }

    public function __getFiles($context = 'files')
    {
        $this->createFilesArray();
        $files = [];
        foreach($this->files as $file)
        {
            if($file->getContext() === $context) $files[] = $file;
        }
        return new ArrayCollection($files);
    }

    public function __setFile(ArrayCollection $files, $context = 'files')
    {
        return $this->__setFiles($files, $context);
    }

    public function __setFiles(ArrayCollection $files, $context = 'files')
    {
        $this->files = $files;

        return $this;
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
        return $this->filesOrderHelper;
    }

    public function setFilesOrder($value)
    {
        $this->filesOrderHelper = json_decode($value, true);
        return $this;
    }

    public function getFilesColor()
    {
        return $this->filesColorHelper;
    }

    public function setFilesColor($value)
    {
        $this->filesColorHelper = json_decode($value, true);
        return $this;
    }

    public function getFilesHash()
    {
        return $this->filesHash;
    }

    public function setFilesHash($value)
    {
        $this->filesHash = $value;
        return $this;
    }
}

?>