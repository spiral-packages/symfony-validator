# Symfony validator bridge for Spiral Framework

[![PHP](https://img.shields.io/packagist/php-v/spiral-packages/symfony-validator.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/symfony-validator)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral-packages/symfony-validator.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/symfony-validator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spiral-packages/symfony-validator/run-tests?label=tests&style=flat-square)](https://github.com/spiral-packages/symfony-validator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral-packages/symfony-validator.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/symfony-validator)

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+
- Spiral framework 3.0+

## Installation

You can install the package via composer:

```bash
composer require spiral-packages/validator
```

After package install you need to register bootloader from the package.

```php
protected const LOAD = [
    // ...
    \Spiral\Validation\Symfony\Bootloader\ValidationBootloader::class,
];
```

> Note: if you are using [`spiral-packages/discoverer`](https://github.com/spiral-packages/discoverer),
> you don't need to register bootloader by yourself.

## Usage

First of all, need to create a filter that will receive incoming data that will be validated by the validator.

### Filter with Attributes

Create a filter class and extend it from the base filter class with
attributes `Spiral\Validation\Symfony\AttributesFilter`.
Define the required properties, and add attributes to them indicating the data source and validation rules.
All available validation rules can be found here:
https://symfony.com/doc/current/validation.html#constraints

Example:

```php
<?php

declare(strict_types=1);

namespace App\Filters;

use Psr\Http\Message\UploadedFileInterface;
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
    #[Constraints\Length(min: 5)]
    public string $slug;

    #[Post]
    #[Constraints\NotBlank]
    #[Constraints\Positive]
    public int $sort;
    
    #[File]
    #[Constraints\Image]
    public UploadedFile $image;
}

```

### Filter with FilterDefinition

If you prefer to configure validation rules in an array, you can use a filter with a `filterDefinition` method
definition.
Create a filter class and extend it from the base filter class `Spiral\Filters\Model\Filter`,
add `Spiral\Filters\Model\HasFilterDefinition` interface.
Implement the `filterDefinition` method, which should return a `Spiral\Validation\Symfony\FilterDefinition` object with
data mapping rules and validation rules.

Example:

```php
<?php

declare(strict_types=1);

namespace App\Filters;

use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validation\Symfony\FilterDefinition;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Image;
use Spiral\Filters\Attribute\Input\Post;
use Spiral\Validation\Symfony\Attribute\Input\File;

final class CreatePostFilter extends Filter implements HasFilterDefinition
{
    #[Post]
    public string $title;

    #[Post]
    public string $slug;

    #[Post]
    public int $sort;
    
    #[File]
    public UploadedFile $image;
    
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(
            [
                'title' => [new NotBlank(), new Length(min: 5)],
                'slug' => [new NotBlank(), new Length(min: 5)],
                'sort' => [new NotBlank(), new Positive()],
                'image' => [new Image()]
            ]
        );
    }
}
```

or

```php
<?php

declare(strict_types=1);

namespace App\Filters;

use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validation\Symfony\FilterDefinition;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Image;

final class CreatePostFilter extends Filter implements HasFilterDefinition
{
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(
            [
                'title' => [new NotBlank(), new Length(min: 5)],
                'slug' => [new NotBlank(), new Length(min: 5)],
                'sort' => [new NotBlank(), new Positive()],
                'image' => [new Image()]
            ],
            [
                'title' => 'title',
                'slug' => 'slug',
                'sort' => 'sort',
                'image' => 'symfony-file:image'
            ]
        );
    }
}
```

### Using a Filter and getting validation errors

Example:

```php
use App\Filters\CreatePostFilter;
use Spiral\Filters\Exception\ValidationException;

try {
    $filter = $this->container->get(CreatePostFilter::class); 
} catch (ValidationException $e) {
    var_dump($e->errors); // Errors processing
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [butschster](https://github.com/spiral-packages)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
