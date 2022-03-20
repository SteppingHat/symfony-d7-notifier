<?php

namespace SteppingHat\D7Notifier\Tests;

use SteppingHat\D7Notifier\D7Transport;
use SteppingHat\D7Notifier\Exception\UnsupportedMessageLengthException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class D7TransportTest extends TransportTestCase {

    /**
     * @return D7Transport
     */
    public function createTransport(HttpClientInterface $client = null, string $from = 'from'): TransportInterface {
        return new D7Transport('authToken', $from, 'AU', $client ?? $this->createMock(HttpClientInterface::class));
    }

    public function toStringProvider(): iterable {
        yield ['d7://rest-api.d7networks.com?from=from', $this->createTransport()];
    }

    public function supportedMessagesProvider(): iterable {
        yield [new SmsMessage('0611223344', 'Hello!')];
    }

    public function unsupportedMessagesProvider(): iterable {
        yield [new ChatMessage('Hello!')];
        yield [$this->createMock(MessageInterface::class)];
    }

    /**
     * @dataProvider invalidFromProvider
     */
    public function testInvalidArgumentExceptionIsThrownIfFromIsInvalid(string $from) {
        $transport = $this->createTransport(null, $from);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "From" number "%s" is not a valid phone number, shortcode, or alphanumeric sender ID.', $from));

        $transport->send(new SmsMessage('+33612345678', 'Hello!'));
    }

    public function invalidFromProvider(): iterable {
        // alphanumeric sender ids
        yield 'too short' => ['a'];
        yield 'too long' => ['abcdefghijkl'];

        // phone numbers
        yield 'no zero at start if phone number' => ['+0'];
        yield 'phone number to short' => ['+1'];
    }

    /**
     * @dataProvider validFromProvider
     */
    public function testNoInvalidArgumentExceptionIsThrownIfFromIsValid(string $from) {
        $message = new SmsMessage('+33612345678', 'Hello!');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode([
                'data' => 'Success "7ed74619-8af4-4434-a7e0-0927ce272f8c',
                'message' => 'foo',
                'more_info' => 'bar',
            ]));

        $client = new MockHttpClient(function (string $method, string $url) use ($response): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame('https://rest-api.d7networks.com/secure/send', $url);

            return $response;
        });

        $transport = $this->createTransport($client, $from);

        $sentMessage = $transport->send($message);

        $this->assertSame('7ed74619-8af4-4434-a7e0-0927ce272f8c', $sentMessage->getMessageId());
    }

    public function validFromProvider(): iterable {
        // alphanumeric sender ids
        yield ['ab'];
        yield ['abc'];
        yield ['abcd'];
        yield ['abcde'];
        yield ['abcdef'];
        yield ['abcdefg'];
        yield ['abcdefgh'];
        yield ['abcdefghi'];
        yield ['abcdefghij'];
        yield ['abcdefghijk'];
        yield ['abcdef ghij'];
        yield [' abcdefghij'];
        yield ['abcdefghij '];

        // phone numbers
        yield ['+11'];
        yield ['+112'];
        yield ['+1123'];
        yield ['+11234'];
        yield ['+112345'];
        yield ['+1123456'];
        yield ['+11234567'];
        yield ['+112345678'];
        yield ['+1123456789'];
        yield ['+11234567891'];
        yield ['+112345678912'];
        yield ['+1123456789123'];
        yield ['+11234567891234'];
        yield ['+112345678912345'];
    }

    /**
     * @dataProvider invalidMessageLengthProvider
     */
    public function testUnsupportedMessageLengthExceptionIsThrown(string $message) {
        $transport = $this->createTransport(null, '33612345678');

        $this->expectException(UnsupportedMessageLengthException::class);

        $transport->send(new SmsMessage('+33612345678', $message));
    }

    public function invalidMessageLengthProvider(): iterable {
        yield [str_repeat('.', 766)];
    }

    public function unicodeMessageProvider(): iterable {
        yield ['😂❤😊💩🍆🍑'];
        yield ['Lorem 👩‍❤️‍💋‍👨 ipsum'];
    }

    /**
     * @dataProvider unicodeMessageProvider
     */
    public function testUnicodeCharactersIsEncoded(string $message) {
        $transport = $this->createTransport(null, '33612345678');

        $reflection = new \ReflectionClass(D7Transport::class);
        $method = $reflection->getMethod('getRequestContent');
        $method->setAccessible(true);

        $return = $method->invokeArgs($transport, [$message]);

        $this->assertArrayHasKey('coding', $return, 'Expected a coding value to be set for a unicode message');
        $this->assertEquals($return['coding'], 8);
    }

    public function asciiMessageProvider(): iterable {
        yield ['Lorem ipsum dolor sit amet, consectetur adipiscing elit.'];
        yield [''];
    }

    /**
     * @dataProvider asciiMessageProvider
     */
    public function testAsciiMessagesAreNotEncodedToUnicode(string $message) {
        $transport = $this->createTransport(null, '33612345678');

        $reflection = new \ReflectionClass(D7Transport::class);
        $method = $reflection->getMethod('getRequestContent');
        $method->setAccessible(true);

        $return = $method->invokeArgs($transport, [$message]);

        $this->assertArrayNotHasKey('coding', $return, 'Should not encode ASCII string');
        $this->assertArrayHasKey('content', $return, 'Content is missing from the payload');
        $this->assertEquals($message, $return['content'], 'Message should be untouched when being added to the payload');
    }

}
