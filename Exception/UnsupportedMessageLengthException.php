<?php

namespace SteppingHat\D7Notifier\Exception;

use Exception;
use Symfony\Component\Notifier\Message\SmsMessage;

class UnsupportedMessageLengthException extends Exception {

    public function __construct(SmsMessage $smsMessage, int $maxLength)
    {
        $smsMessage = sprintf(
            'D7 can only handle messages up to %s characters (message was %s characters).',
            $maxLength,
            strlen($smsMessage->getSubject())
        );

        parent::__construct($smsMessage);
    }

}