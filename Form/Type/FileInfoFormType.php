<?php

namespace Parabol\FilesUploadBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use A2lix\TranslationFormBundle\Form\Type\TranslationsType;

class FileInfoFormType extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['translation_domain' => 'fileinfoform']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder
    		->add('translations', TranslationsType::class, ['label' => false])
    		->add('extraDate', DateTimeType::class, [
    				'required' => false,
    				'widget' => 'single_text',
					'compound' => false,
					'format' => 'dd.MM.yyyy HH:mm', 
					'attr' => [
						'data-date-format' => 'DD.MM.Y HH:mm',
						'class' => 'datetimepicker pick-date pick-time',
					]
    		])
    	;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {

    }

    public function getBlockPrefix()
    {
        return 'file';
    }
}