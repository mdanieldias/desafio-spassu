<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\AssuntoDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AssuntoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('descricao', TextType::class, [
            'label' => 'Descrição',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssuntoDto::class,
            'constraints' => [new Assert\Valid()],
        ]);
    }
}
