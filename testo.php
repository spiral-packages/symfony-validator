<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

return new ApplicationConfig(
    src: ['src'],
    suites: [
        # Runs first: a Functional test boots the app before its coverage window opens, and PCOV only
        # counts a file's top-level code when the file is loaded inside one.
        new SuiteConfig(
            name: 'Unit',
            location: ['tests/src/Unit'],
        ),
        new SuiteConfig(
            name: 'Functional',
            location: ['tests/src/Functional'],
        ),
        new SuiteConfig(
            name: 'Acceptance',
            location: ['tests/src/Acceptance'],
        ),
    ],
);
