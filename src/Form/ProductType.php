<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints as Assert;




class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('image', FileType::class, [
                'label' => 'image (PNG file) ',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG file'
                    ])
                    ],
            ])
            ->add('prix')
            ->add('category')
            ->add('commandes')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
{
    $resolver->setDefaults([
        'data_class' => Product::class,
        'constraints' => [
            new Assert\NotBlank(['message' => 'titre', 'message' => 'Le champ "titre" ne peut pas être vide']),
            new Assert\NotBlank(['message' => 'description', 'message' => 'Le champ "description" ne peut pas être vide']),
            new Assert\Positive(['message' => 'prix', 'message' => 'Le champ "prix" doit être supérieur à zéro']),
            // new Assert\Image(['message' => 'image', 'message' => 'Le champ "image" doit être une image']),
        ],
    ]);
}
}