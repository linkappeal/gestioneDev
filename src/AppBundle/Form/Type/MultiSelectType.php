<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Bundle\DoctrineBundle\Registry;

use AppBundle\Builder\ClienteListBuilder;
/**
form Type
 */
class MultiSelectType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add('settore', ChoiceType::class, 
                      [
                        'multiple' => true, 
                        'required' => false,
                        'empty_data'  => null,
                        //'placeholder' => '-- Scegli un valore --',
                        'attr' => [ 'style' => 'width: 400px;' ]
                      ])
               
                ->add('tipo_campagna', ChoiceType::class, 
                      [ 
                        'multiple' => true, 
                        'required' => false,
                        'attr' => [ 'style' => 'width: 400px;' ]
                      ])
                ->add('brand', ChoiceType::class, 
                      [ 
                        'multiple' => true, 
                        'required' => false,
                        'attr' => [ 'style' => 'width: 400px;' ]
                      ])
                ->add('B2b_B2c', ChoiceType::class, 
                      [ 
                        'multiple' => true, 
                        'required' => false,
                        'attr' => [ 'style' => 'width: 400px;' ]
                      ])
                ->add('nome_offerta',ChoiceType::class, 
                      [ 
                        'multiple' => true, 
                        'required' => false,
                        'attr' => [ 'style' => 'width: 400px;' ]
                      ]);
        
        $builder->get('settore')->resetViewTransformers();
        $builder->get('tipo_campagna')->resetViewTransformers();
        $builder->get('brand')->resetViewTransformers();
        $builder->get('B2b_B2c')->resetViewTransformers();
        $builder->get('nome_offerta')->resetViewTransformers();
        
        /*
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
        $parentChoice = $event->getData();
        $subChoices = $this->getValidChoicesFor($parentChoice);

        $event->getForm()->add('sub_choice', 'choice', [
            'label'   => 'Sub Choice',
            'choices' => $subChoices,
        ]);
         }); */
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'field_options'    => array(),
            'field_type'       => 'text'
            ));
    }
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'app_admin_type_multiselect';
    }
}

