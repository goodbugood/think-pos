<?php

namespace shali\phpmate\tests\http;

use PHPUnit\Framework\TestCase;
use shali\phpmate\http\HttpClient;

class HttpClientTest extends TestCase
{
    private $httpClient;

    public function setUp(): void
    {
        $this->httpClient = new HttpClient();
    }

    public function testPost()
    {
        $rawBody = $this->httpClient->post('https://httpbin.org/post', ['name' => 'shali',]);
        self::assertEquals(200, $this->httpClient->getRawResponse()['statusCode']);
        $data = json_decode($rawBody, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('form', $data);
        self::assertArrayHasKey('name', $data['form']);
        self::assertEquals('shali', $data['form']['name']);
    }
}
