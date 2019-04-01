<?php

namespace Parabol\FilesUploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $fileHelper = $this->get('parabol.helper.blueimp_file');
        $ref = $request->get('hash');

        //$fileHelper->generateRef($request->getSession()->getId(), $request->get('hash'));

        $basepath = trim($request->get('path') ? $request->get('path') : $context . '/' . $ref , '/');
        $dir = $this->get('parabol.utils.path')->getAbsoluteUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') . $basepath);
        if(!file_exists($dir)) mkdir($dir, 0777, true);

        $uploadedfiles = (array)$request->files->get($context);

        $data = array();


        foreach($uploadedfiles as $uploadedfile)
        {
               
            $filename = PathUtil::slugize( preg_replace('#(\.[a-z]+)$#', '', $uploadedfile->getClientOriginalName())) . '.'. strtolower($uploadedfile->getClientOriginalExtension());

            $em = $this->getDoctrine()->getManager();

            if($context !== 'cropper')
            {
                  
                $filename = $fileHelper->getUniqueFilename($dir, $filename);
                $path = $this->get('parabol.utils.path')->getUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') . $basepath, DIRECTORY_SEPARATOR) . $filename;

                $file = (new File())
                        ->setPath($path)
                        ->setContext($context)
                        ->setMimeType($uploadedfile->getMimeType() == 'text/plain' && strtolower($uploadedfile->getClientOriginalExtension()) == 'svg' ? 'image/svg+xml' : $uploadedfile->getMimeType())
                        ->setSize($uploadedfile->getSize())
                        ->setName($uploadedfile->getClientOriginalName())
                        ->setClass($class ? $class : null)
                        ->setRef( $ref )
                        ->setInitRef( $request->get('ref') );

                // dump($uploadedfile, $file);

                $errors = $this->validate($uploadedfile, $file, $request);

                if($errors->has(0))
                {

                    $data[] = [
                        'name' => $uploadedfile->getClientOriginalName(),
                        'error' => htmlspecialchars($errors->get(0)->getMessage())
                    ];
                }
                else
                {

                    $uploadedfile->move($dir, $filename);
                    $em->persist($file);
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
                    
                    $data[] = $fileHelper->toArray($file);
                } 

            }
            else
            {
                $uploadedfile->move($dir, $filename);
                $path = $this->get('parabol.utils.path')->getUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') .  $basepath, DIRECTORY_SEPARATOR) . $filename;

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

    private function validate(UploadedFile $uploadedfile, File $file, Request $request)
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
        // $params['hash'] = $params['ref'];
        //$this->get('parabol.helper.blueimp_file')->generateRef($this->get('session')->getId(), $params['class']);
        $toRemove = $this->get('parabol.helper.blueimp_file')->generateRef($this->get('session')->getId(), $params['ref']);

        $em = $this->getDoctrine()->getManager();

        if(in_array($request->query->get('type'), ['new','edit']))
        {
            $oldFiles = $em->getRepository('ParabolFilesUploadBundle:File')->findBy(array('ref' => $params['hash'], 'class' => $params['class']));
            foreach($oldFiles as $oldFile)
            {
                $em->remove($oldFile);
            }
            $em->flush();

            $em ->getRepository('ParabolFilesUploadBundle:File')
                ->createQueryBuilder('f')
                ->update()
                ->set('f.toRemove', 'NULL')
                ->where('f.toRemove = :toRemove')
                ->setParameter('toRemove', $toRemove)
                ->getQuery()
                ->getResult();

        }
        
        $qb = $em
                ->getRepository('ParabolFilesUploadBundle:File')
                ->createQueryBuilder('f')
                ->where('f.class = :class')
                ->andWhere('f.context = :context')
                ->andWhere('f.ref = :ref OR f.ref = :hash')
                ->orderBy('f.sort', 'DESC')
                ->addOrderBy('f.id', 'DESC')
                
        ;

        if(in_array($request->query->get('type'), ['create','update']))
        {
            $params['toRemove'] = $toRemove;
            $qb
                ->andWhere('f.toRemove IS NULL or f.toRemove != :toRemove')
            ;
        }
        
        $files = $qb->setParameters($params)->getQuery()->execute();

        foreach($files as $file)
        {
            $result[] = $this->get('parabol.helper.blueimp_file')->toArray($file);
            if(!$params['class']::isMultipleFilesAllowed($params['context'])) break;

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
                ->getOneOrNullResult();

        if($file)
        {
            $em = $this->getDoctrine()->getManager();        

            if($request->get('immediately') || $file->isNew()) $em->remove($file);
            else $file->setToRemove($this->get('parabol.helper.blueimp_file')->generateRef( $this->get('session')->getId(), $file->getRef()) );
            
            $em->flush($file);

            $response = new JsonResponse();
            $response->setData(true);
            
            return $response;   
            
        }
        else
        {
            return $this->createNotFoundException();
        }

    }

    // public function updatePositionAction(Request $request)
    // {
    //     // $this->

    //     //$this->get('session')->set('parabol_file_upload_bundle');
    //     // $em = $this->getDoctrine()->getManager();
    //     // $file = $em
    //     //         ->getRepository('ParabolFilesUploadBundle:File')
    //     //         ->find($request->get('id'));
        
    //     // if (!$file) {
    //     //     throw $this->createNotFoundException(
    //     //         'No file found for id '.$id
    //     //     );
    //     // }        

    //     // $file->setSort($request->get('sort'));
    //     // $em->flush();

    //     return new JsonResponse(array('result' => 'success'));
    // }

}
