<?php

namespace Parabol\FilesUploadBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class BlueimpType extends AbstractType
{
   

    public function configureOptions(OptionsResolver $resolver)
    {
         $resolver->setDefaults(array(
                'labels' => [],
                'description' => 'default',
                'multiple' => false,
                'order' => 'asc',
                'class' => null,
                'allowed_remove_pattern' => null,
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
        $view->vars['attr']['multiple'] = $options['multiple'];
        if(!isset($view->vars['attr']['data'])) $view->vars['attr']['data'] = [];
        $view->vars['attr']['data']['order'] = $options['order'];
        $view->vars['class'] = $options['class'];
        $view->vars['attr']['data']['class'] = $options['class'];
        $view->vars['attr']['data']['ref'] = $options['ref'];
        $view->vars['thumb'] = $options['thumb'];
        $view->vars['allowed_remove_pattern'] = $options['allowed_remove_pattern'];
        
    }

    public function getBlockPrefix()
    {
        return 'blueimp_upload';
    }
}