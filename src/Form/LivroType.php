<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\LivroDto;
use App\Entity\Assunto;
use App\Entity\Autor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class LivroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título',
            ])
            ->add('editora', TextType::class, [
                'label' => 'Editora',
            ])
            ->add('edicao', IntegerType::class, [
                'label' => 'Edição',
                'attr' => ['min' => 1],
            ])
            ->add('anoPublicacao', TextType::class, [
                'label' => 'Ano de publicação',
                'attr' => ['maxlength' => 4, 'inputmode' => 'numeric'],
            ])
            ->add('valor', TextType::class, [
                'label' => 'Valor (R$)',
                'attr' => [
                    'data-controller' => 'mascara-moeda',
                    'data-action'     => 'input->mascara-moeda#mascarar',
                    'inputmode'       => 'numeric',
                    'placeholder'     => '0,00',
                ],
            ])
            ->add('autores', EntityType::class, [
                'class' => Autor::class,
                'choice_label' => 'nome',
                'multiple' => true,
                'required' => false,
                'label' => false,
            ])
            ->add('assuntos', EntityType::class, [
                'class' => Assunto::class,
                'choice_label' => 'descricao',
                'multiple' => true,
                'required' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LivroDto::class,
            'constraints' => [new Assert\Valid()],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'livro';
    }
}
