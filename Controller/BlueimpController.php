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

        $uploadedfiles = (array)$request->files->get($context);

        $data = array();

        foreach($uploadedfiles as $uploadedfile)
        {
               
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
                        ->setClass($class ? $class : null);
                        ->setRef( $request->get('ref') ?  $request->get('ref') : '_'.hash('sha256', $request->getSession()->getId() . '|' . $class));

                $errors = $this->validate($file, $request);

                if($errors->has(0))
                {
                    $data[] = [
                        'name' => $uploadedfile->getClientOriginalName(),
                        'error' => htmlspecialchars($errors->get(0)->getMessage())
                    ];
                }
                else
                {

                    $uploadedfile->move($dir, $slugizedName);
                    $em->persist($file);

                    // if($class)
                    // {
                    //     $obj = $this->getDoctrine()->getRepository($class)->find($request->get('ref'));
                    //     if($obj) $obj->__addFile($file, $context);
                    // }

                    // if($class && !$class::isMultipleFilesAllowed($context) && $request->get('ref') 
                    //    && $oldFile = $em->getRepository('ParabolFilesUploadBundle:File')->findOneBy(['ref' => $request->get('ref'), 'class' => $class, 'context' => $context])) {
                    //         $em->remove($oldFile);
                    // }

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
                $uploadedfile->move($dir, $slugizedName);
                $path = $this->get('parabol.utils.path')->getUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') .  $path, DIRECTORY_SEPARATOR) . $slugizedName;

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

    private function validate(File $file, Request $request)
    {
        $acceptedMimeTypes = explode('|', $request->get('acceptedMimeTypes'));

        if($file->isImage())
        {
            list($width, $height) = getimagesize($uploadedfile->getPathname());
            $file->setWidth($width);
            $file->setHeight($height);

            $fileConstraint = new \Symfony\Component\Validator\Constraints\Image([
                    'mimeTypes' => $acceptedMimeTypes, 
                    'maxSize' => '5m', 
                    'maxWidth' => 3840, 
                    'maxHeight' => 3840
            ]);
        }
        else 
        {
            $fileConstraint = new \Symfony\Component\Validator\Constraints\File([
                    'mimeTypes' => $acceptedMimeTypes, 
                    'maxSize' => '95m'
            ]);
        }

        return $this->get('validator')->validate(
            $uploadedfile,
            $fileConstraint 
        );
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
