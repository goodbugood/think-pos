<?php declare(strict_types=1);

namespace think\pos\dto;

/**
 * 复杂的扩展字段
 */
trait ExtInfoTrait
{
    /**
     * 扩展信息
     * 如果扩展的字段能够统一地稳定下来，建议转移到对应的 DO 中，避免每次都需要转换
     * @var array 扩展字段，通常用于上游返回需要存储的信息
     */
    private $extInfo = [
        // 收单机构：为你审核，帮你收钱的机构，如微信，支付宝，工行，农行
        // 'receiveAgent' => '',
    ];

    public function getExtInfo(): array
    {
        return $this->extInfo;
    }

    public function setExtInfo(array $extInfo): void
    {
        $this->extInfo = $extInfo;
    }
}
