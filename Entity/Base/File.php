<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

trait File {

    use Files
    ;

    private static $allowMultipleFiles = false;

    public function getFile()
    {
        return isset($this->files[0]) ? $this->files[0] : new \Parabol\FilesUploadBundle\Entity\File();
    }

    public static function multipleFileAllow()
    {
        return ['files' => false];
    }

}

?>