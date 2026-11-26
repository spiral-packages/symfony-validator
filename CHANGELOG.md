# Changelog

## [1.6.0](https://github.com/spiral-packages/symfony-validator/compare/1.5.0...1.6.0) (2026-11-26)


### Features

* migrate tests to Testo, require PHP 8.2 ([#31](https://github.com/spiral-packages/symfony-validator/issues/31)) ([0cb5ad1](https://github.com/spiral-packages/symfony-validator/commit/0cb5ad1de0a6f62308bc435f73f8a1d6cf3ef90d))
* support Symfony 8 ([#41](https://github.com/spiral-packages/symfony-validator/issues/41)) ([875841f](https://github.com/spiral-packages/symfony-validator/commit/875841f7b00125417d367afc06597166c78b330f))


### Bug Fixes

* accept a validation context that is not validation groups ([#57](https://github.com/spiral-packages/symfony-validator/issues/57)) ([3ddae96](https://github.com/spiral-packages/symfony-validator/commit/3ddae963254d16d0883ef209cc4b9f30b90cd0ca))
* read values of filter objects in Validator ([#56](https://github.com/spiral-packages/symfony-validator/issues/56)) ([9dc0964](https://github.com/spiral-packages/symfony-validator/commit/9dc09647906deba2b25ab62bdecba90de119418b))
* support nested file uploads in FilesBag ([#55](https://github.com/spiral-packages/symfony-validator/issues/55)) ([4c8ca3b](https://github.com/spiral-packages/symfony-validator/commit/4c8ca3b7ef18e7ee2cd0c5edbd32258fda13253e)), closes [#52](https://github.com/spiral-packages/symfony-validator/issues/52)


### Documentation

* add mutation testing badge ([#58](https://github.com/spiral-packages/symfony-validator/issues/58)) ([9bee207](https://github.com/spiral-packages/symfony-validator/commit/9bee207c0f884975e56825964305344e2ac270ba))
* rework README with badges and usage examples ([#46](https://github.com/spiral-packages/symfony-validator/issues/46)) ([c9ed270](https://github.com/spiral-packages/symfony-validator/commit/c9ed2704e84cbc998f65c3940995cee72b2cabd9))
* rewrite changelog in the release-please format ([#53](https://github.com/spiral-packages/symfony-validator/issues/53)) ([0354042](https://github.com/spiral-packages/symfony-validator/commit/0354042ceba9cc2edc10a3b5439541833ffa93f4))


### Code Refactoring

* analyse with Psalm at level 1 without a baseline ([#44](https://github.com/spiral-packages/symfony-validator/issues/44)) ([3030d3b](https://github.com/spiral-packages/symfony-validator/commit/3030d3b4d677cdbf2a498059b16ca747f1896bf8))

## [1.5.0](https://github.com/spiral-packages/symfony-validator/compare/1.4.0...1.5.0) (2024-04-02)

### Features

* support Symfony 7.0 ([#27](https://github.com/spiral-packages/symfony-validator/pull/27))

## [1.4.0](https://github.com/spiral-packages/symfony-validator/compare/1.3.0...1.4.0) (2023-05-08)

### Features

* allow `doctrine/annotations` 2.x ([#25](https://github.com/spiral-packages/symfony-validator/pull/25))
* bind `Symfony\Component\Validator\Validator\ValidatorInterface` in the container ([#25](https://github.com/spiral-packages/symfony-validator/pull/25))

## [1.3.0](https://github.com/spiral-packages/symfony-validator/compare/1.2.1...1.3.0) (2023-01-27)

### Features

* add `ConstraintValidatorFactory` ([#24](https://github.com/spiral-packages/symfony-validator/pull/24))

## [1.2.1](https://github.com/spiral-packages/symfony-validator/compare/1.2.0...1.2.1) (2023-01-26)

### Bug Fixes

* fix the bootloader name in `composer.json` ([#22](https://github.com/spiral-packages/symfony-validator/pull/22))
* fix the `spiral/attributes` version constraint ([#23](https://github.com/spiral-packages/symfony-validator/pull/23))

## [1.2.0](https://github.com/spiral-packages/symfony-validator/compare/1.1.1...1.2.0) (2022-09-29)

### Features

* add a default validator ([#20](https://github.com/spiral-packages/symfony-validator/pull/20))

## [1.1.1](https://github.com/spiral-packages/symfony-validator/compare/1.1.0...1.1.1) (2022-09-16)

### Bug Fixes

* rename `Dto` to `Model` in namespaces ([#14](https://github.com/spiral-packages/symfony-validator/pull/14))
* require a stable version of `spiral/framework` ([#19](https://github.com/spiral-packages/symfony-validator/pull/19))

## [1.1.0](https://github.com/spiral-packages/symfony-validator/compare/1.0.0...1.1.0) (2022-07-19)

### Features

* add support for `spiral/attributes` ^2.8
* add `spiral/nyholm-bridge` for testing purposes

## 1.0.0 (2022-05-26)

* initial release
