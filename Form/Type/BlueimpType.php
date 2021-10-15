<?php

namespace Parabol\FilesUploadBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class BlueimpType extends AbstractType
{
    private static $cropperAssigned = false; 
    private $fileInfoFormClass = null;    

    public function __construct(?string $fileInfoFormClass = null)
    {
        $this->fileInfoFormClass = $fileInfoFormClass;
    }
   

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
                'description' => 'default',
                'context' => null,
                'multiple' => null,
                'editable'=> false,
                'editFormType' => $this->fileInfoFormClass,
                'error_bubbling' => false,
                'compound' => false,
                'order' => 'asc',
                'class' => null,
                'cropper' => false, //[aspectRation => Number or NaN, minCropBoxWidth >= 0, minCropBoxHeight >= 0 ]
                'acceptMimeTypes' => ['image/gif','image/jpeg','image/png','application/pdf','application/zip','video/mp4','image/svg+xml'],
                'allowed_remove_pattern' => null,
                'required' => false,
                'ref' => null,
                'mapped' => false,
                'thumb' => [
                    'onclick' => null,
                    'lables' => null
                ],
                'customButtons' => null,
                'append' => null,
                'prepend' => null,
                'withName' => false,
                'uploadTemplate' => 'ParabolFilesUploadBundle:BlueimpTemplates:upload-template.js.tmpl',
                'downloadTemplate' => 'ParabolFilesUploadBundle:BlueimpTemplates:download-template.js.tmpl',
                "allow_add" => null, 
                "entry_options" => null, 
                "entry_type" => null,
                "page" => null
        ));

         $resolver->setAllowedTypes('class', 'string');
         $resolver->setAllowedTypes('withName', 'boolean');
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['description'] = $options['description'];

        // dump($options['class'], method_exists($options['class'], 'isMultipleFilesAllowed')); die();
        $view->vars['attr']['multiple'] = $options['multiple'] !== null ? $options['multiple'] : ($options['class'] && method_exists($options['class'], 'isMultipleFilesAllowed') ? call_user_func([$options['class'], 'isMultipleFilesAllowed'], $view->vars['name']) : false);

        if(!isset($view->vars['attr']['data'])) $view->vars['attr']['data'] = [];
        $view->vars['attr']['data']['order'] = $options['order'];
        $view->vars['class'] = $options['class'];
        $view->vars['editable'] = $options['editable'];
        $view->vars['editFormType'] = $options['editFormType'];
        $view->vars['cropper'] = $options['cropper'];
        $view->vars['customButtons'] = $options['customButtons'];
        $view->vars['append'] = $options['append'];
        $view->vars['prepend'] = $options['prepend'];
        $view->vars['withName'] = $options['withName'];
        $view->vars['context'] = $options['context'];

        if($options['page'] !== null) $view->vars['attr']['data']['page'] = $options['page'];


        if($options['acceptMimeTypes'] && !is_array($options['acceptMimeTypes'])) throw new InvalidOptionsException('The options "acceptMimeTypes" must by an array.');
        $view->vars['acceptMimeTypes'] = $options['acceptMimeTypes'];


        if(isset($options['cropper']['aspectRatio']) && $options['cropper']['aspectRatio'] != 'NaN')
        {
            preg_match('#^\d+:\d+$#', $options['cropper']['aspectRatio'], $match);
            if(isset($match[0]))
            {
                list($w, $h) = explode(':', $match[0]);
                if($h > 0) $view->vars['cropper']['aspectRatio'] = $w/$h;
            }
            else throw new InvalidOptionsException('The options "cropper[aspectRatio]" must contain "NaN" or value in format: \d+:\d+');
        }

        if(isset($options['cropper']['minCropBoxWidth']) && !is_numeric($options['cropper']['minCropBoxWidth']))
        {
            throw new InvalidOptionsException('The options "cropper[minCropBoxWidth]" must contain a number');
        }

        if(isset($options['cropper']['minCropBoxHeight']) && !is_numeric($options['cropper']['minCropBoxHeight']))
        {
            throw new InvalidOptionsException('The options "cropper[minCropBoxWidth]" must contain a number');
        }

        
        $view->vars['attr']['data']['class'] = $options['class'];
        $view->vars['attr']['data']['ref'] = $options['ref'];
        $view->vars['thumb'] = $options['thumb'];
        if(isset($options['thumb']['labels']) && $options['thumb']['labels'] !== null) $view->vars['thumb']['labels'] = (array) $options['thumb'];
        $view->vars['uploadTemplate'] = $options['uploadTemplate'];
        $view->vars['downloadTemplate'] = $options['downloadTemplate'];

        $view->vars['allowed_remove_pattern'] = $options['allowed_remove_pattern'];
        $view->vars['cropperAssigned'] = self::$cropperAssigned;
        self::$cropperAssigned = true;
    }

    public function getBlockPrefix()
    {
        return 'blueimp_upload';
    }
}