<?php

namespace shali\phpmate\tests\http;

use Exception;
use PHPUnit\Framework\TestCase;
use shali\phpmate\http\HttpClient;

class HttpClientTest extends TestCase
{
    private $httpClient;

    public function setUp(): void
    {
        $this->httpClient = new HttpClient();
    }

    /**
     * @test 测试发送 application/x-www-form-urlencoded
     * @throws Exception
     */
    public function postForm()
    {
        $rawBody = $this->httpClient->post('https://httpbin.org/post', ['name' => 'shali',]);
        self::assertEquals(200, $this->httpClient->getRawResponse()['statusCode']);
        $data = json_decode($rawBody, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('form', $data);
        self::assertArrayHasKey('name', $data['form']);
        self::assertEquals('shali', $data['form']['name']);
    }

    /**
     * 测试发送 application/json
     * @throws Exception
     */
    public function testPostJson()
    {
        $rawBody = $this->httpClient->post('https://httpbin.org/post', ['name' => 'shali',], ['content-type' => 'application/json']);
        self::assertEquals(200, $this->httpClient->getRawResponse()['statusCode']);
        $data = json_decode($rawBody, true);
        self::assertIsArray($data);
        // 非 form
        self::assertArrayHasKey('form', $data);
        self::assertEmpty($data['form']);
        // json
        self::assertArrayHasKey('json', $data);
        self::assertArrayHasKey('name', $data['json']);
        self::assertEquals('shali', $data['json']['name']);
    }
}
