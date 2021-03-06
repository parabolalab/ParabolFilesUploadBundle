<?php

namespace Parabol\FilesUploadBundle\Entity\Base;

trait File {

    use Files
    ;

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