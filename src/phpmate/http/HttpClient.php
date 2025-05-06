<?php declare(strict_types=1);

namespace shali\phpmate\http;

use Curl\Curl;
use Exception;

class HttpClient
{
    /**
     * @var Curl
     */
    private $curl;

    protected $rawRequest = [
        'url' => null,
        'params' => null,
    ];

    protected $rawResponse = [
        // 请求耗时，单位：秒
        'costTime' => null,
        // http status code
        'statusCode' => null,
        // http status message
        'message' => null,
        'headers' => null,
        // http body
        'body' => null,
    ];

    public function __construct()
    {
        $this->curl = new Curl();
    }

    /**
     * @throws Exception
     */
    public function post($url, $params, $headers = [], int $timeout = 30): string
    {
        if ($headers) {
            $this->curl->setHeaders($headers);
        }
        $this->rawRequest['url'] = $url;
        $this->rawRequest['params'] = $params;
        // 禁用证书
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOpt(CURLOPT_TIMEOUT, $timeout);
        $startTime = microtime(true);
        $this->curl->post($url, $params);
        $this->rawResponse['costTime'] = microtime(true) - $startTime;
        $this->rawResponse['statusCode'] = 0 !== $this->curl->getErrorCode() ? $this->curl->getErrorCode() : 200;
        $this->rawResponse['message'] = '' !== $this->curl->getErrorMessage() ? $this->curl->getErrorMessage() : 'OK';
        $this->rawResponse['headers'] = $this->curl->getRawResponseHeaders();
        $this->rawResponse['body'] = $this->curl->getRawResponse();
        // 检查状态码
        if ($this->rawResponse['statusCode'] !== 200) {
            throw new Exception(sprintf('请求失败，状态码：%s，信息：%s', $this->rawResponse['statusCode'], $this->rawResponse['message']));
        }

        return $this->rawResponse['body'];
    }

    public function getRawRequest(): array
    {
        return $this->rawRequest;
    }

    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}
