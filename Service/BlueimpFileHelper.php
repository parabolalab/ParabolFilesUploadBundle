<?php

namespace Parabol\FilesUploadBundle\Service;

/**
* BlueimpFile Service to convert File Entity to array for  bluimp jquery file upload  
*/
class BlueimpFileHelper
{
	private $router;
	private $liipCacheManager;
	
	function __construct($router, $liipCacheManager)
	{
		$this->router = $router;
		$this->liipCacheManager = $liipCacheManager;	
	}

	public function toArray(\Parabol\FilesUploadBundle\Entity\File $file, $allowDeletePattern = null, $thumbName = 'admin_thumb')
	{
		$result = [
			"name" => basename($file->getPath()),
			"url" => $file->getPath(), //$path ? $path : preg_replace('#^.*\/.\.\/[\w_]+#', '', $obj->getPathname());
            "size" => $file->getSize(),
            "sort" => $file->getSort(),
            "id" => $file->getId(),
            "width" => $file->getWidth(),
            "height" => $file->getHeight(),
            "thumbnailUrl" => !$file->isImage() ? $file->getPathForThumb() : $this->liipCacheManager->getBrowserPath($file->getPathForThumb(), $thumbName),
            "editUrl" => $this->router->generate('parabol_admin_core_dialog_form', [ 'id' => $file->getId(), 'entity' => \Parabol\FilesUploadBundle\Entity\File::class ]),
            "deleteUrl" => !$allowDeletePattern || preg_match('#' . $allowDeletePattern . '#', $file->getPath()) ? $this->router->generate('parabol_uploader_delete', [ 'id' => $file->getId() ]) : null,
            "deleteType" => "GET",
            "cropper" => $file->isImage(),
            "cropBoxData" => (array)$file->getCropBoxData(),
            "color" => $file->getColor(),
		];

		return $result;
	}

	public function generateRef($sessionId, $class)
	{
		return '_'.hash('sha256', $sessionId . '|' . $class);
	}

	public function getUniqueFilename($dir, $filename)
  {
        $i = 1;
        $orgname = $filename;
        while(file_exists($dir . DIRECTORY_SEPARATOR . $filename))
        {
            $filename = preg_replace('#(\.[a-z]+)$#', '-'.$i.'$1', $orgname);
            $i++;
        }

        return $filename;
  }


}