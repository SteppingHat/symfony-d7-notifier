<?php

namespace SteppingHat\D7Notifier;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use SteppingHat\D7Notifier\Exception\UnsupportedMessageLengthException;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class D7Transport extends AbstractTransport {

    const HOST = 'rest-api.d7networks.com';
    const MAX_LENGTH = 765;

    private string $authToken;
    private string $from;
    private string $defaultLocale;
    private bool $allowUnicode;

    public function __construct(string $authToken, string $from, string $defaultLocale, bool $allowUnicode, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null) {
        $this->authToken = $authToken;
        $this->from = $from;
        $this->defaultLocale = $defaultLocale;
        $this->allowUnicode = $allowUnicode;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string {
        return sprintf('d7://%s?from=%s&defaultLocale=%s', $this->getEndpoint(), $this->from, $this->defaultLocale);
    }

    public function supports(MessageInterface $message): bool {
        return $message instanceof SmsMessage;
    }

    /**
     * @param MessageInterface $message
     * @return SentMessage
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws NumberParseException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws UnsupportedMessageLengthException
     */
    protected function doSend(MessageInterface $message): SentMessage {
        if(!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        if (!preg_match('/^[a-zA-Z0-9\s]{2,11}$/', $this->from) && !preg_match('/^\+[1-9]\d{1,14}$/', $this->from)) {
            throw new InvalidArgumentException(sprintf('The "From" number "%s" is not a valid phone number, shortcode, or alphanumeric sender ID.', $this->from));
        }

        if(class_exists(PhoneNumberUtil::class)) {
            $phoneNumberUtl = PhoneNumberUtil::getInstance();
            $phoneNumber = $phoneNumberUtl->parse($message->getPhone(), $this->defaultLocale);
            $phoneNumber = $phoneNumberUtl->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        } else {
            $phoneNumber = $message->getPhone();
            if(!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
                throw new InvalidArgumentException(sprintf('The recipient number "%s" is not a valid internationally formatted number.', $phoneNumber));
            }
        }

        $body = ['from' => $this->from, 'to' => $phoneNumber];
        $body = array_merge($body, $this->getRequestContent($message->getSubject()));

        $endpoint = sprintf('https://%s/secure/send', $this->getEndpoint());
        $response = $this->client->request('POST', $endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '. $this->authToken
            ],
            'body' => json_encode($body)
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach remote D7 service.', $response, 0, $e);
        }

        if($statusCode < 200 || $statusCode > 208) {
            if($statusCode >= 400 && $statusCode <= 499) {
                $message = $response->toArray(false)['message'];
                throw new TransportException(sprintf('Unable to send SMS. %s: %s', $statusCode, $message), $response);
            } else {
                throw new TransportException(sprintf('Unable to send SMS: Status %s', $statusCode), $response);
            }
        }

        $success = $response->toArray(false);
        $messageId = substr($success['data'], strpos($success['data'], '"') + 1);

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($messageId);

        return $sentMessage;
    }

    /**
     * @throws UnsupportedMessageLengthException
     */
    private function getRequestContent($content): array {
        $body = [];
        $encoding = mb_detect_encoding($content);

        if($this->allowUnicode && $encoding === 'UTF-8') {
            $body['hex_content'] = bin2hex(mb_convert_encoding($content, 'UTF-16'));
            $body['coding'] = 8;
        } else {
            if(strlen($content) > self::MAX_LENGTH) {
                throw new UnsupportedMessageLengthException($content, self::MAX_LENGTH);
            }

            $body['content'] = $content;
        }

        return $body;
    }
}