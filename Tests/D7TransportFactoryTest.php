<?php

namespace SteppingHat\D7Notifier\Tests;

use SteppingHat\D7Notifier\D7TransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

class D7TransportFactoryTest extends TransportFactoryTestCase {

    public function createFactory(): TransportFactoryInterface {
        return new D7TransportFactory();
    }

    public function createProvider(): iterable {
        yield [
            'd7://host.test?from=0611223344&defaultLocale=AU',
            'd7://authToken@host.test?from=0611223344&defaultLocale=AU'
        ];
    }

    public function supportsProvider(): iterable {
        yield [true, 'd7://authToken@default?from=0611223344&defaultLocale=AU'];
        yield [false, 'somethingElse://authToken@default?from=0611223344&defaultLocale=AU'];
    }

    public function missingRequiredOptionProvider(): iterable {
        yield 'missing option: from' => ['d7://authToken@default'];
    }

    public function unsupportedSchemeProvider(): iterable {
        yield ['somethingElse://authToken@default?from=0611223344'];
        yield ['somethingElse://authToken@default'];
        yield ['somethingElse://authToken@default?defaultLocale=AU'];
    }
}
