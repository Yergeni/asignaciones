<?php

namespace CURSO\UserBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // $builder
        //     ->add('username')
        //     ->add('firstName')
        //     ->add('lastName')
        //     ->add('email', 'email')//campo tipo email se declara asi para futuras validaciones
        //     ->add('password', 'password')
        //     ->add('role', 'choice', array('choices' => array('ROLE_ADMIN' => 'Administrator', 'ROLE_USER' => 'User', 'palceholder' => 'Select a role')))
        //     ->add('isActive', 'checkbox')
        //     ->add('save', 'submit', array('label' => 'Save'))
            
        //     // ->add('createdAt', 'datetime') campos que se generaran automaticamente
        //     // ->add('updatedAt', 'datetime')
        // ;
        
        $builder
            ->add('username')
            ->add('firstName')
            ->add('lastName')
            ->add('email', EmailType::class)//campo tipo email se declara asi para futuras validaciones
            ->add('password', PasswordType::class)
            ->add('role', ChoiceType::class, array('choices' => array( 'Select a role' => 'placeholder', 
                'Administrator' => 'ROLE_ADMIN', 'User' => 'ROLE_USER'), 'choices_as_values' => true))//el value seleccionado como true
            ->add('isActive', CheckboxType::class)
            ->add('save', SubmitType::class, array('label' => 'Save'))
            //->add('delete', SubmitType::class, array('label' => 'Delete'))
            
            // ->add('createdAt', 'datetime') campos que se generaran automaticamente
            // ->add('updatedAt', 'datetime')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CURSO\UserBundle\Entity\User'
        ));
    }
    
    // /**
    //  * @return string
    //  */ 
    //  public function getName()
    //  {
    //      return 'user_form';
    //  }
     
}
