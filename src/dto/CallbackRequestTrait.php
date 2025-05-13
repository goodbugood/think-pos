<?php declare(strict_types=1);

namespace think\pos\dto;

trait CallbackRequestTrait
{
    /**
     * 响应状态
     * @var boolean
     */
    private $success;

    /**
     * @var string
     */
    private $errorMsg;

    // 静态方法生成此实例

    /**
     * @param string $errorMsg
     * @return static
     */
    public static function fail(string $errorMsg)
    {
        $response = new static();
        $response->setSuccess(false);
        $response->setErrorMsg($errorMsg);
        return $response;
    }

    /**
     * @return static
     */
    public static function success()
    {
        $response = new static();
        $response->setSuccess(true);
        return $response;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFail(): bool
    {
        return !$this->isSuccess();
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    public function getErrorMsg(): ?string
    {
        return $this->errorMsg;
    }

    public function setErrorMsg(string $errorMsg): void
    {
        $this->errorMsg = $errorMsg;
    }

    // 禁止 new
    private function __construct()
    {
    }
}
