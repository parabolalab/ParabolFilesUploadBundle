<?php
namespace Parabol\FilesUploadBundle\Form\Base\Extension;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Form\FormMapper;
use Parabol\FilesUploadBundle\Form\Type\BlueimpType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\CallbackTransformer;
use Doctrine\Common\Collections\ArrayCollection;

final class SonataAdminBluimpTypeExtension extends AbstractAdminExtension
{
    public function configureFormFields(FormMapper $formMapper)
    {        
        $this->configureFiles($formMapper);
    }

    public function configureFiles($formMapper)
    {
        
        $dataClass = $formMapper->getFormBuilder()->getDataClass();

        $group = null;

        if($dataClass && method_exists($dataClass, 'fileContexts'))
        {
            foreach ($dataClass::fileContexts() as $key => $value) 
            {
                if($formMapper->has($key))
                {
                    $group = method_exists($formMapper, 'getFieldGroup') ? $formMapper->getFieldGroup($key) : null;

                    if($group) $formMapper->with($group);

                    $formMapper->add($key, \Parabol\FilesUploadBundle\Form\Type\BlueimpType::class, $this->resolveOptions($dataClass, $formMapper->getFormBuilder()->getData(), $formMapper->get($key)->getOptions()));
                    
                    if($group) $formMapper->end();


                    
                    $formMapper->get($key)->addModelTransformer(new CallbackTransformer (
                        function ($tagsAsArray) use ($key) {
                            // dump([1, $key, $tagsAsArray]);
                            // transform the array to a string
                            return new ArrayCollection();
                        },
                        function ($tagsAsString) use ($key) {
                            // transform the string back to an array
                          // dump([2, $key, $tagsAsString]);
                          
                          
                            return new ArrayCollection();
                        }
                    ));

                    
                }
            }

            if($group) $formMapper->with($group);

            $formMapper->add('filesUpdatedAt',  HiddenType::class);
            $formMapper->add('filesOrder', HiddenType::class);
            $formMapper->add('filesColor', HiddenType::class);
            $formMapper->add('filesHash', HiddenType::class);

            if($group) $formMapper->end();

        }
        

        

    }

    protected function resolveOptions($dataClass, $data, array $options)
    {
        // unset($fieldOptions['allow_add'], $fieldOptions['allow_delete'], $fieldOptions['type'], $fieldOptions['options']);

      unset(
          $options['choice_attr'], 
          $options['choice_label'], 
          $options['choice_loader'], 
          $options['choice_name'],
          $options["choice_translation_domain"],
          $options["choice_value"],
          $options["choices"],
          $options["em"],
          $options["expanded"],
          $options["group_by"],
          $options["id_reader"],
          $options["placeholder"],
          $options["preferred_choices"],
          $options["query_builder"]
      );

        
        if(method_exists($dataClass, 'allowMultipleFiles') && $dataClass::allowMultipleFiles())
        {
            $options['multiple'] = true;
        } 


        //if(!isset($options['attr']['labels'])) $options['attr']['labels'] = [];
        $options['class'] = $dataClass;
        $options['ref'] = $data ? $data->getId() : null;
        $options['required'] = false;
        $options['mapped'] = false;



        if($options['label'] == 'Files') $options['label'] = ' ';

        // $options = $formType->optionsFixer($options);

        return $options;
    }
}