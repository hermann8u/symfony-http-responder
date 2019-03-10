<?php

declare(strict_types=1);

namespace ro0NL\HttpResponder\Test;

use PHPUnit\Framework\TestCase;
use ro0NL\HttpResponder\Exception\BadRespondTypeException;
use ro0NL\HttpResponder\OuterResponder;
use ro0NL\HttpResponder\Respond\Respond;
use ro0NL\HttpResponder\Responder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * @author Roland Franssen <franssen.roland@gmail.com>
 */
abstract class ResponderTestCase extends TestCase
{
    protected const DEFAULT_RESPONSE_CLASS = Response::class;
    protected const DEFAULT_RESPONSE_STATUS = 200;
    protected const IS_CATCH_ALL_RESPONDER = false;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    protected function tearDown(): void
    {
        $this->getFlashBag()->clear();
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithStatus(Respond $respond): void
    {
        $response = $this->doRespond($respond->withStatus(1 + $prevStatus = $respond->status[0]));

        self::assertSame(1 + $prevStatus, $response->getStatusCode());
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithInvalidStatus(Respond $respond): void
    {
        $responder = $this->getOuterResponder();

        try {
            $responder->respond($respond->withStatus(999));
            self::fail();
        } catch (\LogicException $e) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithStatusText(Respond $respond): void
    {
        $response = $this->doRespond($respond->withStatus(201, 'Hello HTTP'));

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith("HTTP/1.0 201 Hello HTTP\r\n", (string) $response);
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithDate(Respond $respond): void
    {
        $response = $this->doRespond($respond->withDate($date = new \DateTime('yesterday')));

        self::assertInstanceOf(\DateTime::class, $response->getDate());
        /** @psalm-suppress PossiblyNullReference */
        self::assertSame($date->getTimestamp(), $response->getDate()->getTimestamp());
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithoutDate(Respond $respond): void
    {
        $response = $this->doRespond($respond);

        self::assertInstanceOf(\DateTime::class, $date = $response->getDate());
        self::assertTrue($date > new \DateTime('yesterday'));
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithHeaders(Respond $respond): void
    {
        $response = $this->doRespond($respond->withHeaders([
            'h1' => 'v',
            'H2' => ['v1', 'V2'],
        ]));
        $headers = $response->headers->allPreserveCase();

        self::assertArrayHasKey('h1', $headers);
        self::assertSame(['v'], $headers['h1']);
        self::assertArrayHasKey('H2', $headers);
        self::assertSame(['v1', 'V2'], $headers['H2']);
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithHeader(Respond $respond): void
    {
        $response = $this->doRespond($respond
            ->withHeader('h1', 'v')
            ->withHeader('H2', 'ignored')
            ->withHeader('H2', ['v1', 'V2']));
        $headers = $response->headers->allPreserveCase();

        self::assertArrayHasKey('h1', $headers);
        self::assertSame(['v'], $headers['h1']);
        self::assertArrayHasKey('H2', $headers);
        self::assertSame(['v1', 'V2'], $headers['H2']);
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithFlashes(Respond $respond): void
    {
        $this->getOuterResponder()->respond($respond->withFlashes(['type1' => 'X', 'TYPE2' => ['y', true, []]]));

        self::assertSame(['type1' => ['X'], 'TYPE2' => ['y', true, []]], $this->getFlashBag()->all());
    }

    /**
     * @dataProvider provideResponds
     */
    public function testRespondWithFlash(Respond $respond): void
    {
        $this->getOuterResponder()->respond($respond
            ->withFlash('type1', 'X')
            ->withFlash('TYPE2', 'not ignored')
            ->withFlash('TYPE2', ['y', true, []]));

        self::assertSame(['type1' => ['X'], 'TYPE2' => ['not ignored', 'y', true, []]], $this->getFlashBag()->all());
    }

    public function provideResponds(): iterable
    {
        foreach ($this->getResponds() as $respond) {
            yield [$respond];
        }
    }

    public function testUnknownRespond(): void
    {
        $responder = $this->getResponder();

        if (static::IS_CATCH_ALL_RESPONDER) {
            $responder->respond($this->getMockForAbstractClass(Respond::class));

            $this->addToAssertionCount(1);

            return;
        }

        $this->expectException(BadRespondTypeException::class);

        $responder->respond($this->getMockForAbstractClass(Respond::class));
    }

    protected static function assertResponse(Response $response, int $status = null): void
    {
        if (Response::class !== static::DEFAULT_RESPONSE_CLASS) {
            self::assertInstanceOf(static::DEFAULT_RESPONSE_CLASS, $response);
        }

        self::assertSame($status ?? static::DEFAULT_RESPONSE_STATUS, $response->getStatusCode());
    }

    abstract protected function getResponder(): Responder;

    /**
     * @return iterable|Respond[]
     */
    abstract protected function getResponds(): iterable;

    protected function getThrowingResponder(): Responder
    {
        return new class() implements Responder {
            public function respond(Respond $respond): Response
            {
                throw BadRespondTypeException::create($this, $respond);
            }
        };
    }

    protected function getOuterResponder(): OuterResponder
    {
        $responder = $this->getResponder();

        return $responder instanceof OuterResponder ? $responder : new OuterResponder($responder, $this->getFlashBag());
    }

    protected function getFlashBag(): FlashBagInterface
    {
        return $this->flashBag ?? ($this->flashBag = new FlashBag());
    }

    protected function doRespond(Respond $respond): Response
    {
        return $this->getOuterResponder()->respond($respond);
    }
}
