<?php

namespace Parabol\FilesUploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Parabol\FilesUploadBundle\Component\File\BlueimpFile;
use Parabol\FilesUploadBundle\Entity\File;
use Parabol\BaseBundle\Util\PathUtil;

class BlueimpController extends Controller
{
   
    public function uploadAction(Request $request)
    {

        $class = $request->get('class');
        $context = $request->get('context');
        $dir = $this->get('parabol.utils.path')->getAbsoluteUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') . $context);
        
        if(!file_exists($dir)) mkdir($dir, 0777, true);

        $files = (array)$request->files->get($context);
        $data = array();

        $obj = null;
        if($class)
        {
            $obj = $this->getDoctrine()
                ->getRepository($class)
                ->find($request->get('ref'))
                ;
        }

        foreach($files as $file)
        {
            
            $orgName = $slugizedName  = PathUtil::slugize( preg_replace('#(\.[a-z]+)$#', '', $file->getClientOriginalName())) . '.'. strtolower($file->getClientOriginalExtension());
            
            $i = 1;
           
            while(file_exists($dir . DIRECTORY_SEPARATOR . $slugizedName))
            {
                $slugizedName = preg_replace('#(\.[a-z]+)$#', '-'.$i.'$1', $orgName);
                $i++;
            }

            $path = $this->get('parabol.utils.path')->getUploadDir(($class ? $class . DIRECTORY_SEPARATOR : '') . $context, DIRECTORY_SEPARATOR).$slugizedName;    


            $f = new File();
            $f->setPath($path); 
            $f->setContext($context); 
            $f->setMimeType($file->getMimeType() == 'text/plain' && strtolower($file->getClientOriginalExtension()) == 'svg' ? 'image/svg+xml' : $file->getMimeType());
            if($f->isImage())
            {
                list($width, $height) = getimagesize($file->getPathname());
                $f->setWidth($width);
                $f->setHeight($height);
            }
            
            if($class) $f->setClass($class);
            if($request->get('refAdminUrl')) $f->setRef($request->get('refAdminUrl'));

            $f->setRef( $request->get('ref') ?  $request->get('ref') : '_'.hash('sha256', $this->get('session')->getId() . '|' . $class));

            $file->move($dir, $slugizedName);

            $em = $this->getDoctrine()->getManager();
            $em->persist($f);

            if($obj) $obj->addFile($f, $context);

            if($class && !$class::allowMultipleFiles() && $request->get('ref'))
            {
                $oldFile = $em->getRepository('ParabolAdminCoreBundle:File')->findOneBy(array('ref' => $request->get('ref'), 'class' => $class));
                if($oldFile)
                {
                    $em->remove($oldFile);
                }
            }

            $em->flush();
           
           

            if($f->isImage())
            {
                $imagemanagerResponse = $this->container
                ->get('liip_imagine.controller')
                    ->filterAction(
                        $request,
                        $path,
                        'admin_thumb'
                );    

                
            }
            
            $data[] = BlueimpFile::__toArray($f->getMimeType() == 'image/svg+xml' ? $f->getPathForThumb() : $this->get('liip_imagine.cache.manager')->getBrowserPath($f->getPathForThumb(), 'admin_thumb'), $f->getId(), $f->getSort(), $f->getWidth(), $f->getHeight(), $file, $this->get('kernel')->getEnvironment());         
            
            

            
        }
        
        $response = new JsonResponse();
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
            // var_dump('edit', $params['hash']);
            $oldFiles = $em->getRepository('ParabolAdminCoreBundle:File')->findBy(array('ref' => $params['hash'], 'class' => $params['class']));
            foreach($oldFiles as $oldFile)
            {
                $em->remove($oldFile);
            }

            $em->flush();


        }


        // throw new \Exception("Error Processing Request", 1);
        

        $files = $em
                ->getRepository('ParabolAdminCoreBundle:File')
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

            $bluimpFile = new BlueimpFile($this->get('parabol.utils.path')->getWebDir().$file->getPath(), $this->container->get('kernel')->getEnvironment());
            $result[] = $bluimpFile->toArray($file->getMimeType() == 'image/svg+xml' ? $file->getPathForThumb() : $this->get('liip_imagine.cache.manager')->getBrowserPath($file->getPathForThumb(), 'admin_thumb'), $file->getId(), $file->getSort(), $file->getWidth(), $file->getHeight());            
            
        }

        $response = new JsonResponse();
        $response->setData($result);
        
        return $response;   
    }

    public function deleteAction(Request $request)
    {
        $file = $this->getDoctrine()
                ->getRepository('ParabolAdminCoreBundle:File')
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
                ->getRepository('ParabolAdminCoreBundle:File')
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

    private function uploadedFileToJSON()
    {

    }

}
