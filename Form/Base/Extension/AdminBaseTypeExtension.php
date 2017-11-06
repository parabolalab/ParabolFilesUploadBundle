<?php

namespace Parabol\FilesUploadBundle\Form\Base\Extension;

use Symfony\Component\Form\FormBuilderInterface;

class AdminBaseTypeExtension  {

    public function configureOptions($formType, array &$options)
    {
        $options['ckeditor']['filebrowserBrowseUrl'] = '/admin';
        return $options;
    }

    public function preBuild($formType, array $options)
    {

    }

    public function postBuild($formType, array $options)
    {

        if($formType->getDataClass())
        {
            foreach ($formType->getDataClass()::fileContexts() as $key => $value) 
            {
                if($formType->getBuilder()->has($key))
                {
                    $formType->forceAdd($key, \Parabol\FilesUploadBundle\Form\Type\BlueimpType::class, $this->resolveOptions($key, $formType, $options), $options);
                }
            }

            $formType->getBuilder()->add('filesUpdatedAt',  \Symfony\Component\Form\Extension\Core\Type\HiddenType::class);
            $formType->getBuilder()->add('filesOrder', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class);
        }
    }


    protected function resolveOptions($name, $formType, array $builderOptions = array())
    {
        $fieldOptions = $formType->getFieldOptons($name);

        unset($fieldOptions['allow_add'], $fieldOptions['allow_delete'], $fieldOptions['type'], $fieldOptions['options']);

        $class = $formType->getBuilder()->getDataClass();
        if(method_exists($class, 'allowMultipleFiles') && $class::allowMultipleFiles())
        {
            $fieldOptions['multiple'] = true;
        } 

        //if(!isset($fieldOptions['attr']['labels'])) $fieldOptions['attr']['labels'] = [];
        $fieldOptions['class'] = $class;
        $fieldOptions['ref'] = $formType->getBuilder()->getData()->getId();



        if($fieldOptions['label'] == 'Files') $fieldOptions['label'] = ' ';

        $fieldOptions = $formType->optionsFixer($fieldOptions);

        return $fieldOptions;
    }
            
	
}