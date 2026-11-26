<p align="center">
    <a href="https://spiral.dev"><img alt="Spiral Framework" src="https://user-images.githubusercontent.com/773481/228578125-8e0ad713-905f-4450-a473-5839bd650472.png" style="width: 6in; display: block"></a>
</p>

<p align="center">Symfony Validator bridge for Spiral Framework</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://spiral.dev/docs/validation-symfony)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/spiral)

[![Psalm Level](https://shepherd.dev/github/spiral-packages/symfony-validator/level.svg)](https://shepherd.dev/github/spiral-packages/symfony-validator)
[![Type Coverage](https://shepherd.dev/github/spiral-packages/symfony-validator/coverage.svg)](https://shepherd.dev/github/spiral-packages/symfony-validator)
[![Codecov](https://codecov.io/gh/spiral-packages/symfony-validator/branch/1.x/graph/badge.svg)](https://codecov.io/gh/spiral-packages/symfony-validator)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fspiral-packages%2Fsymfony-validator%2F1.x)](https://dashboard.stryker-mutator.io/reports/github.com/spiral-packages/symfony-validator/1.x)

</div>

<br />

This package lets [Spiral Filters](https://spiral.dev/docs/filters-filter) be validated with
[Symfony Validator](https://symfony.com/doc/current/validation.html) constraints, declared as attributes on filter
properties or as an array of rules in a filter definition.

## Get Started

### Installation

```bash
composer require spiral-packages/symfony-validator
```

[![PHP](https://img.shields.io/packagist/php-v/spiral-packages/symfony-validator.svg?style=flat-square&logo=php)](https://packagist.org/packages/spiral-packages/symfony-validator)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral-packages/symfony-validator.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/spiral-packages/symfony-validator)
[![License](https://img.shields.io/packagist/l/spiral-packages/symfony-validator.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral-packages/symfony-validator.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/symfony-validator/stats)

Requirements: PHP 8.2+, Spiral Framework 3.x and Symfony Validator 6.4, 7 or 8.

### Configuration

Register the bootloader in your application kernel:

```php
public function defineBootloaders(): array
{
    return [
        // ...
        \Spiral\Validation\Symfony\Bootloader\ValidatorBootloader::class,
        // ...
    ];
}
```

With [spiral-packages/discoverer](https://github.com/spiral-packages/discoverer) the bootloader is registered
automatically. It makes the Symfony validator the default one for filters and adds the `symfony-file` input source
for uploaded files.

### Usage

Extend `AttributesFilter` and put Symfony constraints next to the input attributes. Uploaded files are read with the
`File` attribute and arrive as Symfony `UploadedFile` objects, so file constraints such as `Image` apply to them:

```php
use Spiral\Filters\Attribute\Input\Post;
use Spiral\Validation\Symfony\Attribute\Input\File;
use Spiral\Validation\Symfony\AttributesFilter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints;

final class CreatePostFilter extends AttributesFilter
{
    #[Post]
    #[Constraints\NotBlank]
    #[Constraints\Length(min: 5)]
    public string $title;

    #[Post]
    #[Constraints\NotBlank]
    #[Constraints\Positive]
    public int $sort;

    #[File]
    #[Constraints\Image]
    public UploadedFile $image;
}
```

The rules and the mapping can also be returned from a filter definition:

```php
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validation\Symfony\FilterDefinition;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CreateUserFilter extends Filter implements HasFilterDefinition
{
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(
            validationRules: [
                'username' => [new NotBlank()],
                'email' => [new NotBlank(), new Email()],
                'image' => [new Image()],
            ],
            mappingSchema: [
                'username' => 'username',
                'email' => 'email',
                'image' => 'symfony-file:image',
            ],
        );
    }
}
```

Inject the filter into a controller action: the request data is mapped and validated before the action runs, and
invalid input is rejected with the constraint violation messages.

See the [documentation](https://spiral.dev/docs/validation-symfony) for more details.
