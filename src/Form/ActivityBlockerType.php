<?php


namespace App\Form;


use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Repository\LicensePlateRepository;
use App\Service\LicensePlateService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ActivityBlockerType extends AbstractType
{
    private $security;
    private $licensePlateRepository;

    public function __construct(Security $security, LicensePlateRepository $licensePlateRepository)
    {
        $this->security = $security;
        $this->licensePlateRepository = $licensePlateRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($options['oneCar'] == true)
        {
            $builder
                ->add('blocker', EntityType::class, [
                        'class' => LicensePlate::class,
                        'query_builder' => function (EntityRepository $er)
                        {
                            return $er->createQueryBuilder('lp')
                                ->andWhere('lp.user = :val')
                                ->setParameter('val', $this->security->getUser());
                        },
                        'choice_label' => 'license_plate',
                        'disabled' => true]
                );
        }
        elseif ($options['oneCar'] == false)
        {
            $builder
                ->add('blocker', EntityType::class, [
                    'class' => LicensePlate::class,
                    'query_builder' => function (EntityRepository $er)
                    {
                        return $er->createQueryBuilder('lp')
                            ->andWhere('lp.user = :val')
                            ->setParameter('val', $this->security->getUser());
                    },
                    'choice_label' => 'license_plate',]);
        }

        $builder
            ->add('blockee', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'oneCar' => false,
        ]);
    }
}