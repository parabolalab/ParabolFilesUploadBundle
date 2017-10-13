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
            "deleteUrl" => !$allowDeletePattern || preg_match('#' . $allowDeletePattern . '#', $file->getPath()) ? $this->router->generate('parabol_uploader_delete', [ 'id' => $file->getId() ]) : null,
            "deleteType" => "GET",
            "cropper" => $file->isImage(),
            "cropBoxData" => (array)$file->getCropBoxData(),

		];

		return $result;
	}

	public function generateRef($sessionId, $class)
	{
		return '_'.hash('sha256', $sessionId . '|' . $class);
	}


}