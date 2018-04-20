<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

trait File {

    use Files
    ;

    private static $allowMultipleFiles = false;

    public function getFile()
    {
        return isset($this->files[0]) ? $this->files[0] : null;
    }

    public static function fileContexts()
    {
        return ['files' => false];
    }

}

?>