<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Api;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Provider\Api\CrudApiResourceCapabilityProvider;
use App\Cruding\Resolver\CrudCapabilityResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CrudApiResourceCapabilityProviderTest extends TestCase
{
    public function testProvidesTypedResourceAndFieldHintsWithoutRenderingAForm(): void
    {
        $resolver = new CrudCapabilityResolver([
            'identifiable' => ['methods_any' => ['getId']],
            'sluggable' => ['methods_any' => ['getSlug']],
        ]);
        $provider = new CrudApiResourceCapabilityProvider(
            $resolver,
            Forms::createFormFactoryBuilder()->getFormFactory(),
        );

        $metadata = $provider->provide(new CrudContextDTO(
            'api',
            'index',
            'product',
            CapabilityProduct::class,
            'id',
            null,
            CapabilityProductType::class,
        ))->toArray();

        self::assertSame(1, $metadata['schemaVersion']);
        self::assertSame('product', $metadata['resource']);
        self::assertSame(['supportsId' => true, 'supportsSlug' => true], $metadata['identity']);
        self::assertSame([
            'index' => true,
            'show' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
        ], $metadata['operations']);

        self::assertSame([
            'name' => 'title',
            'required' => true,
            'readOnly' => false,
            'multiple' => false,
            'minLength' => 2,
            'maxLength' => 80,
        ], $metadata['fields'][0]);
        self::assertSame([
            'name' => 'visibility',
            'required' => true,
            'readOnly' => false,
            'multiple' => false,
            'choices' => [
                ['label' => 'Access › Public', 'value' => 'public'],
                ['label' => 'Access › Private', 'value' => 'private'],
            ],
        ], $metadata['fields'][1]);
        self::assertSame([
            'name' => 'quantity',
            'required' => false,
            'readOnly' => true,
            'multiple' => false,
            'min' => 0,
            'max' => 25,
        ], $metadata['fields'][2]);
    }

    public function testMissingFormDisablesCreateAndUpdateWithoutInventingFields(): void
    {
        $resolver = new CrudCapabilityResolver([
            'identifiable' => ['methods_any' => ['getId']],
            'sluggable' => ['methods_any' => ['getSlug']],
        ]);
        $provider = new CrudApiResourceCapabilityProvider(
            $resolver,
            Forms::createFormFactoryBuilder()->getFormFactory(),
        );

        $metadata = $provider->provide(new CrudContextDTO(
            'api',
            'index',
            'project',
            CapabilityIdOnlyResource::class,
            'id',
            null,
            null,
        ))->toArray();

        self::assertSame(['supportsId' => true, 'supportsSlug' => false], $metadata['identity']);
        self::assertSame([
            'index' => true,
            'show' => true,
            'create' => false,
            'update' => false,
            'delete' => true,
        ], $metadata['operations']);
        self::assertSame([], $metadata['fields']);
    }
}

final class CapabilityProduct
{
    public function getId(): int
    {
        return 1;
    }

    public function getSlug(): string
    {
        return 'demo-product';
    }
}

final class CapabilityIdOnlyResource
{
    public function getId(): int
    {
        return 1;
    }
}

enum CapabilityVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}

final class CapabilityProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 80,
                ],
            ])
            ->add('visibility', ChoiceType::class, [
                'choices' => [
                    'Access' => [
                        'Public' => CapabilityVisibility::Public,
                        'Private' => CapabilityVisibility::Private,
                    ],
                ],
                'choice_value' => static fn (?CapabilityVisibility $visibility): ?string => $visibility?->value,
            ])
            ->add('quantity', IntegerType::class, [
                'required' => false,
                'disabled' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 25,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CapabilityProduct::class,
        ]);
    }
}
