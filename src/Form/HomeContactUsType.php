<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HomeContactUsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('name', TextType::class, array('mapped' => false,
                    'label' => 'Name',
                    'required' => true,
                ))
                ->add('email', EmailType::class, [
                    'label' => 'Email',
                    'required' => true,
                ])
                ->add('subject',TextType::class, array('mapped' => false,
                    'label' => 'Subject',
                    'required' => true,
                ))
                ->add('message',TextType::class, array('mapped' => false,
                    'label' => 'Message',
                    'required' => true,
                ))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
