<?php

namespace SteppingHat\D7Notifier;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

class D7TransportFactory extends AbstractTransportFactory {

    private string $defaultLocale = 'AU'; // TODO: Pull default locale from the container parameter (framework.default_locale)

    public function create(Dsn $dsn): TransportInterface {
        $scheme = $dsn->getScheme();

        if($scheme !== 'd7') {
            throw new UnsupportedSchemeException($dsn, 'd7', $this->getSupportedSchemes());
        }

        $authToken = $this->getUser($dsn);
        $from = $dsn->getRequiredOption('from');
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return (new D7Transport($authToken, $from, $this->defaultLocale, $this->client, $this->dispatcher))->setHost($host)->setPort($port);
    }

    protected function getSupportedSchemes(): array {
        return ['d7'];
    }
}