<?php declare(strict_types=1);

namespace think\pos\dto;

trait MerchantTrait
{
    /**
     * @var string 商户号
     */
    private $merchantNo;

    /**
     * @var string 商户名称
     */
    private $merchantName;

    /**
     * @var string 注册时间
     */
    private $regDateTime;

    /**
     * @var string 身份证号
     */
    private $idCardNo;

    /**
     * @var string 身份证姓名
     */
    private $idCardName;

    /**
     * @var string 手机号
     */
    private $phoneNo;

    /**
     * @var int 商户状态
     * @see \think\pos\constant\MerchantStatus
     */
    private $status;

    /**
     * @var string 结算银行卡开户名称
     */
    private $bankAccountName;

    /**
     * @var string 结算银行卡卡号
     */
    private $bankAccountNo;

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    public function getMerchantName(): string
    {
        return $this->merchantName;
    }

    public function setMerchantName(string $merchantName): void
    {
        $this->merchantName = $merchantName;
    }

    public function getRegDateTime(): string
    {
        return $this->regDateTime;
    }

    public function setRegDateTime(string $regDateTime): void
    {
        $this->regDateTime = $regDateTime;
    }

    public function getIdCardNo(): string
    {
        return $this->idCardNo;
    }

    public function setIdCardNo(string $idCardNo): void
    {
        $this->idCardNo = $idCardNo;
    }

    public function getIdCardName(): string
    {
        return $this->idCardName;
    }

    public function setIdCardName(string $idCardName): void
    {
        $this->idCardName = $idCardName;
    }

    public function getPhoneNo(): string
    {
        return $this->phoneNo;
    }

    public function setPhoneNo(string $phoneNo): void
    {
        $this->phoneNo = $phoneNo;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getBankAccountName(): string
    {
        return $this->bankAccountName;
    }

    public function setBankAccountName(string $bankAccountName): void
    {
        $this->bankAccountName = $bankAccountName;
    }

    public function getBankAccountNo(): string
    {
        return $this->bankAccountNo;
    }

    public function setBankAccountNo(string $bankAccountNo): void
    {
        $this->bankAccountNo = $bankAccountNo;
    }
}
