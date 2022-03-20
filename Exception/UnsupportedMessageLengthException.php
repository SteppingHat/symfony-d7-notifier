<?php

namespace SteppingHat\D7Notifier\Exception;

use Exception;

class UnsupportedMessageLengthException extends Exception {

    public function __construct($message, int $maxLength)
    {
        $smsMessage = sprintf(
            'D7 can only handle messages up to %s characters (message was %s characters).',
            $maxLength,
            strlen($message)
        );

        parent::__construct($smsMessage);
    }

}