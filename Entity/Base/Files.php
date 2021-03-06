<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

//TODO: check context & check __call method to auto add support for other context than "files"

trait Files {

	public $files;
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

            if($this->isMultipleFilesAllowed($context) && in_array($property[0], ['g', 's'])) $method .= 's';

            if(method_exists($this, $method))
            {
               $arguments[] = $context; 
               return call_user_func_array([$this, $method], $arguments);
            }

        }

        return get_parent_class($this) ? parent::__call($property, $arguments) : null;

    }

    public function __addFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        if($this->files === null) $this->files = new \Doctrine\Common\Collections\ArrayCollection();
        $this->files->add($file);

        return $this;
    }

    public function __removeFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        $this->files->removeElement($file);
    }

    public function __getFile($context = 'files')
    {
        return isset($this->files[0]) ? $this->files[0] : null;
    }

    public function __getFiles($context = 'files')
    {
        
        return $this->files;
    }

    public function __setFile(\Doctrine\Common\Collections\ArrayCollection $files, $context = 'files')
    {
        return $this->__setFiles($files, $context);
    }

    public function __setFiles(\Doctrine\Common\Collections\ArrayCollection $files, $context = 'files')
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