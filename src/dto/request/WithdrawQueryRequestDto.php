<?php declare(strict_types=1);

namespace think\pos\dto\request;

use think\pos\dto\ProviderRequestTrait;

/**
 * 代付查询请求DTO
 */
class WithdrawQueryRequestDto
{
    use ProviderRequestTrait;

    /**
     * @var string 提现订单编号
     */
    private $orderNo = '';

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }
}