<?php

namespace Parabol\FilesUploadBundle\Component\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Parabol\FilesUploadBundle\Entity\File;

class BlueimpFile extends UploadedFile
{
    public $env;

    public function __construct($path, $env = 'prod')
    {
        $this->env = $env;

        try
        {
            parent::__construct($path, @basename($path), @mime_content_type($path), @filesize($path));    
        }
        catch(\Exception $e)
        {

        }
        

        return $this;
    }

	public function toArray($thumb, $sort, $id, $width, $height, $allowed_remove_pattern = null)
	{
       return self::__toArray($thumb, $sort, $id, $width, $height, $this, $this->env, $allowed_remove_pattern);
	}

    public static function __toArray($thumb, $id, $sort = 1, $width = null, $height = null, $obj = null, $env = 'prod', $allowed_remove_pattern = null)
    {
         // if($obj === null) $obj = $this;

        $result = array(
                "name" => $obj->getClientOriginalName(),
                "size" => $obj->getClientSize(),
                "sort" => $sort,
                "id" => $id,
                "width" => $width,
                "height" => $height,
                );

        // if($obj->isValid())
        // {

        $url = ($env == 'dev' ? '/app_dev.php' : '');

            $result["url"] = preg_replace('#^.*\/.\.\/[\w_]+#', '', $obj->getPathname());
            $result["thumbnailUrl"] = $thumb;
            $result["deleteUrl"] = !$allowed_remove_pattern || preg_match('#' . $allowed_remove_pattern . '#', $result["url"]) ? $url . "/_uploader/delete/".$id : null;
            $result["editUrl"] = $url . "/admin/dialog-form";
            $result["deleteType"] = "GET";
        // }
        // else
        // {
        //     $result["error"] = $obj->getErrorMessage();
        // }

        return $result;
    }    
}