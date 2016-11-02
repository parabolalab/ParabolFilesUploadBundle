<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

//TODO: check context & check __call method to auto add support for other context than "files"

trait Files {

	private $files;
    private $filesUpdatedAt;

    public function addFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        $this->{$context}->add($file);

        return $this;
    }

    public function removeFile(\Parabol\FilesUploadBundle\Entity\File $file, $context = 'files')
    {
        $this->{$context}->removeElement($file);
    }

    public function getFiles($context = 'files')
    {
        return $this->{$context};
    }

    public function setFiles(\Doctrine\Common\Collections\ArrayCollection $files, $context = 'files')
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
    	return ['files'];
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
}

?>