<?php

namespace SteppingHat\D7Notifier;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

class D7TransportFactory extends AbstractTransportFactory {

    public function create(Dsn $dsn): TransportInterface {
        $scheme = $dsn->getScheme();

        if($scheme !== 'd7') {
            throw new UnsupportedSchemeException($dsn, 'd7', $this->getSupportedSchemes());
        }

        $authToken = $this->getUser($dsn);
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        $from = $dsn->getRequiredOption('from');
        $defaultLocale = $dsn->getRequiredOption('defaultLocale');
        $allowUnicode = filter_var($dsn->getOption('allowUnicode', true), FILTER_VALIDATE_BOOLEAN);

        return (new D7Transport($authToken, $from, $defaultLocale, $allowUnicode, $this->client, $this->dispatcher))->setHost($host)->setPort($port);
    }

    protected function getSupportedSchemes(): array {
        return ['d7'];
    }
}