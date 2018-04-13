<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use unapi\anticaptcha\twocaptcha\Client;
use unapi\anticaptcha\twocaptcha\Service;
use unapi\anticaptcha\common\dto\CaptchaSolvedDto;
use unapi\anticaptcha\common\task\ImageTask;

class AnticaptchaTest extends TestCase
{
    public function testResolveCaptcha()
    {
        $mock = new MockHandler([
            new Response(200, [], 'OK|99999'),
            new Response(200, [], 'CAPCHA_NOT_READY'),
            new Response(200, [], 'OK|mf4azc')
        ]);
        $service = new Service([
            'key' => 'mocked',
            'client' => new Client([
                'handler' => HandlerStack::create($mock),
                'delay' => 0,
            ])
        ]);
        $service->resolve(
            new ImageTask([
                'body' => file_get_contents(__DIR__ . '/fixtures/captcha/mf4azc.png'),
                'minLength' => 6,
                'maxLength' => 6,
            ])
        )->then(function (CaptchaSolvedDto $solved) {
            $this->assertEquals('mf4azc', $solved->getCode());
        })->wait();
    }
}