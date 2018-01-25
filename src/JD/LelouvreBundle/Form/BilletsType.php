<?php

namespace JD\LelouvreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class BilletsType extends AbstractType
{
    /**
     * @param FormBuilderInterface  $builder
     * @param mixed $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('nom', TextType::class)
        ->add('prenom', TextType::class)
        ->add('pays', CountryType::class, [
            'preferred_choices'     => ['FR']
        ])
        ->add('dateNaissance', DateType::class, [
            'days'      => range(1, 31),
            'months'    => range(1, 12),
            'years'     => range(1902, date('Y')),
            'format'    => 'dd-MM-yyyy'
        ])
        ->add('tarifReduit', CheckboxType::class, [
            'label'     => 'Tarif réduit ?',
            'required'  => false
        ])
        ->add('Suivant', SubmitType::class);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JD\LelouvreBundle\Entity\Billets'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'jd_lelouvrebundle_billets';
    }


}
