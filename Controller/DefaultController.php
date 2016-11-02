<?php

namespace Parabol\FilesUploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('ParabolFilesUploadBundle:Default:index.html.twig');
    }
}
