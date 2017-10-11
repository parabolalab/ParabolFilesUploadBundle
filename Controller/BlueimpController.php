<?php

namespace Parabol\FilesUploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Parabol\FilesUploadBundle\Entity\File;
use Parabol\BaseBundle\Util\PathUtil;

class BlueimpController extends Controller
{
   
    public function uploadAction(Request $request)
    {

        $request->getSession()->start();

        $response = new JsonResponse();

        $class = $request->get('class');
        $context = $request->get('context');
        $path = trim($request->get('path') ? $request->get('path') : $context . '/' . ($request->get('ref') ?  $request->get('ref') : '') , '/');
        $dir = $this->get('parabol.utils.path')->getAbsoluteUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') . $path);
        
        if(!file_exists($dir)) mkdir($dir, 0777, true);


        $filesExtensions = explode('|', $request->get('acceptedFileTypes'));

        $imagesMimeTypes = [];
        $filesMimeTypes = [];
        
        foreach ($filesExtensions as $ext) {
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
            {
                $imagesMimeTypes[] = 'image/' . $ext;
            }
            elseif(in_array($ext, ['svg', 'pdf', 'zip', 'mp4']))
            {
                switch($ext)
                {
                    //vector images
                    case 'svg': $filesMimeTypes[] = 'image/svg+xml'; break;
                    
                    //videos
                    case 'mp4': 
                        $filesMimeTypes[] = 'video/' . $ext; 
                    break;
                    
                    //applications
                    case 'zip':
                        $filesMimeTypes[] = 'application/octet-stream';  
                    case 'pdf': 
                        $filesMimeTypes[] = 'application/' . $ext; 
                    break;
                }
            }
        }
        

        $uploadedfiles = (array)$request->files->get($context);
        $dev = [];

        $data = array();

        $obj = null;
        if($class)
        {
            $obj = $this->getDoctrine()
                ->getRepository($class)
                ->find($request->get('ref'))
                ;
        }

        foreach($uploadedfiles as $uploadedfile)
        {
            
                // var_dump($file);
                // die();
               
            $orgName = $slugizedName  = PathUtil::slugize( preg_replace('#(\.[a-z]+)$#', '', $uploadedfile->getClientOriginalName())) . '.'. strtolower($uploadedfile->getClientOriginalExtension());
            
            

            $em = $this->getDoctrine()->getManager();

            if($context !== 'cropper')
            {
                $i = 1;
           
                while(file_exists($dir . DIRECTORY_SEPARATOR . $slugizedName))
                {
                    $slugizedName = preg_replace('#(\.[a-z]+)$#', '-'.$i.'$1', $orgName);
                    $i++;
                }


                $path = $this->get('parabol.utils.path')->getUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') .  $path, DIRECTORY_SEPARATOR).$slugizedName;

                $file = (new File())
                        ->setPath($path)
                        ->setContext($context)
                        ->setMimeType($uploadedfile->getMimeType() == 'text/plain' && strtolower($uploadedfile->getClientOriginalExtension()) == 'svg' ? 'image/svg+xml' : $uploadedfile->getMimeType());

                if($file->isImage())
                {
                    list($width, $height) = getimagesize($uploadedfile->getPathname());
                    $file->setWidth($width);
                    $file->setHeight($height);

                    $fileConstraint = new \Symfony\Component\Validator\Constraints\Image([
                            'mimeTypes' => $imagesMimeTypes, 
                            'maxSize' => '5m', 
                            'maxWidth' => 3840, 
                            'maxHeight' => 3840
                    ]);
                }
                else 
                {
                    $fileConstraint = new \Symfony\Component\Validator\Constraints\File([
                            'mimeTypes' => $filesMimeTypes, 
                            'maxSize' => '95m'
                    ]);
                }

                $errors = $this->get('validator')->validate(
                    $uploadedfile,
                    $fileConstraint 
                );
                

                if($errors->has(0))
                {
                    $data[] = [
                        'name' => $uploadedfile->getClientOriginalName(),
                        'error' => htmlspecialchars($errors->get(0)->getMessage())
                    ];
                }
                else
                {

                    if($class) $file->setClass($class);
                    if($request->get('refAdminUrl')) $file->setRef($request->get('refAdminUrl'));

                    $file->setRef( $request->get('ref') ?  $request->get('ref') : '_'.hash('sha256', $request->getSession()->getId() . '|' . $class));

                    $uploadedfile->move($dir, $slugizedName);

                    $em->persist($file);

                    if($obj) $obj->__addFile($file, $context);

                    if($class && !$class::isMultipleFilesAllowed($context) && $request->get('ref'))
                    {
                        $oldFile = $em->getRepository('ParabolFilesUploadBundle:File')->findOneBy(array('ref' => $request->get('ref'), 'class' => $class, 'context' => $context));
                        if($oldFile)
                        {
                            $em->remove($oldFile);
                        }
                    }

                    $em->flush();

                    if($file->isImage())
                    {
                        try {
                            $imagemanagerResponse = $this->container
                            ->get('liip_imagine.controller')
                                ->filterAction(
                                    $request,
                                    $path,
                                    'admin_thumb'
                            );    
                        }
                        catch(\Exception $e)
                        {
                            
                        }
                    }
                    
                    $data[] = $this->get('parabol.helper.blueimp_file')->toArray($file);
                } 

            }
            else
            {
                $dev['file'] = $request->get('orginalFilePath');

                $uploadedfile->move($dir, $slugizedName);

                $path = $this->get('parabol.utils.path')->getUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') .  $path, DIRECTORY_SEPARATOR).$slugizedName;

                $dev['file_moved'] = true;

                if($request->get('orginalFilePath') && $request->get('cropperBoxData') !== null)
                {

                    $this
                    ->container
                    ->get('liip_imagine.cache.manager')
                    ->remove($path);

                    $params = [
                        'cropData' => $request->get('cropperBoxData'),
                        'path' => $request->get('orginalFilePath'),
                    ];

                    $em
                    ->createQueryBuilder()
                    ->update('ParabolFilesUploadBundle:File', 'f')
                    ->set('f.cropBoxData', ':cropData')
                    ->where('f.path = :path')
                    ->setParameters($params)
                    ->getQuery()
                    ->execute();

                }
            }
           
           

                   
            
            

            
        }
        
        
        $response->setData(array('files' => $data));
        
        return $response;   
    }

    public function getAction(Request $request)
    {
        $uploadPath = $this->get('parabol.utils.path')->getAbsoluteUploadDir($request->get('class'));
        $result = array();

        $params = $request->query->get('params');
        $params['hash'] = '_'.hash('sha256', $this->get('session')->getId().'|'.$params['class']);

        $em = $this->getDoctrine()->getManager();

        if($request->query->get('type') == 'edit')
        {
            $oldFiles = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $params['hash'], 'class' => $params['class']));
            foreach($oldFiles as $oldFile)
            {
                $em->remove($oldFile);
            }

            $em->flush();


        }
        
        $files = $em
                ->getRepository('ParabolFilesUploadBundle:File')
                ->createQueryBuilder('f')
                ->where('f.class = :class')
                ->andWhere('f.context = :context')
                ->andWhere('f.ref = :ref OR f.ref = :hash')
                ->orderBy('f.sort', 'DESC')
                ->setParameters($params)
                ->getQuery()
                ->execute();


        foreach($files as $file)
        {
            $result[] = $this->get('parabol.helper.blueimp_file')->toArray($file);
        }

        $response = new JsonResponse();
        $response->setData($result);
        
        return $response;   
    }

    public function deleteAction(Request $request)
    {
        $file = $this->getDoctrine()
                ->getRepository('ParabolFilesUploadBundle:File')
                ->createQueryBuilder('f')
                ->where('f.id = :id')
                ->setParameter(':id', $request->get('id'))
                ->getQuery()
                ->getSingleResult();

        $em = $this->getDoctrine()->getManager();        

        $em->remove($file);
        $em->flush();
    
        $response = new JsonResponse();
        $response->setData(true);
        
        return $response;   
    }

    public function updatePositionAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $file = $em
                ->getRepository('ParabolFilesUploadBundle:File')
                ->find($request->get('id'));
        
        if (!$file) {
            throw $this->createNotFoundException(
                'No file found for id '.$id
            );
        }        

        $file->setSort($request->get('sort'));
        $em->flush();

        return new JsonResponse(array('result' => 'success'));
    }

}
