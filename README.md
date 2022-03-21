Symfony D7 Networks Notifier
============================

[![Latest Stable Version](http://poser.pugx.org/steppinghat/symfony-d7-notifier/v)](https://packagist.org/packages/steppinghat/symfony-d7-notifier) [![Total Downloads](http://poser.pugx.org/steppinghat/symfony-d7-notifier/downloads)](https://packagist.org/packages/steppinghat/symfony-d7-notifier) [![License](http://poser.pugx.org/steppinghat/symfony-d7-notifier/license)](https://packagist.org/packages/steppinghat/symfony-d7-notifier) [![Build Status](https://travis-ci.com/SteppingHat/symfony-d7-notifier.svg?branch=master)](https://travis-ci.com/SteppingHat/symfony-d7-notifier)

Provides D7 Networks integration for Symfony Notifier.

## Installation

Install this package using composer

```bash
composer require steppinghat/symfony-d7-notifier
```

Add the D7 transport in `config/pakages/notifier.yaml`

```yaml
framework:
    notifier:
        texter_transports:
            d7: '%env(D7_DSN)%' # ADD MEE!
```

Define the D7 DSN environment variable in `.env`

```
D7_DSN=d7://<TOKEN>@default?<PARAMETERS>
```

`TOKEN` is your D7 Networks API token and `PARAMETERS` is a query string that is built with the following parameters:

| Parameter       | Type   | Description |
|-----------------|--------|---------------------------------|
| `from`          | string | The sender number or alphanumeric name. |
| `defaultLocale` | string | The two-letter country code to convert local numbers to international numbers. |
| `allowUnicode`  | bool   | Allow messages to be encoded and sent using unicode if UTF-8 characters are present. _Optional, defaults to **true**._ |

A typical example DSN would be:

```
D7_DSN=d7://abcd1234@default?from=SteppingHat&defaultLocale=AU
```

and lastly register the service in `services.yaml`

```yaml
notifier.transport_factory.d7:
    class: SteppingHat\D7Notifier\D7TransportFactory
    parent: notifier.transport_factory.abstract
    tags: [texter.transport_factory]
```

## Contributing

### Tests

Included for library development purposes is a small set of test cases to assure that basic library functions work as
expected. These tests can be launched by running the following:

```
$ vendor/bin/phpunit
```

### License

Made with ❤ by Javan Eskander

Available for use under the MIT license
