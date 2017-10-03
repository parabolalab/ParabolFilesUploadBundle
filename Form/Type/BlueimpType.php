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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
                'labels' => [],
                'description' => 'default',
                'multiple' => false,
                'edditable'=> false,
                'error_bubbling' => false,
                'compound' => false,
                'order' => 'asc',
                'class' => null,
                'cropper' => false, //[aspectRation => Number or NaN, minCropBoxWidth >= 0, minCropBoxHeight >= 0 ]
                'acceptFileTypes' => ['gif','jpg','jpeg','png','pdf','zip','mp4','svg'],
                'allowed_remove_pattern' => null,
                'required' => false,
                'ref' => null,
                'thumb' => [
                    'onclick' => null
                ],
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['description'] = $options['description'];
        $view->vars['attr']['multiple'] = $options['class'] ? call_user_func([$options['class'], 'isMultipleFilesAllowed'], $view->vars['name']) : true;
        if(!isset($view->vars['attr']['data'])) $view->vars['attr']['data'] = [];
        $view->vars['attr']['data']['order'] = $options['order'];
        $view->vars['class'] = $options['class'];
        $view->vars['edditable'] = $options['edditable'];
        $view->vars['cropper'] = $options['cropper'];


        if($options['acceptFileTypes'] && !is_array($options['acceptFileTypes'])) throw new InvalidOptionsException('The options "acceptFileTypes" must by an array.');
        $view->vars['acceptFileTypes'] = $options['acceptFileTypes'];


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
        $view->vars['allowed_remove_pattern'] = $options['allowed_remove_pattern'];
        $view->vars['cropperAssigned'] = self::$cropperAssigned;
        self::$cropperAssigned = true;
    }

    public function getBlockPrefix()
    {
        return 'blueimp_upload';
    }
}