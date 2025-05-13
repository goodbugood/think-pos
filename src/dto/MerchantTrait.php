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
     * @var string 营业执照号
     */
    private $businessLicenseNo;

    /**
     * @var string 营业执照名称=商户经营名称=店铺名称
     */
    private $businessName;

    /**
     * @var string 法人身份证号码
     */
    private $idCardNo;

    /**
     * @var string 法人姓名
     */
    private $idCardName;

    /**
     * @var string 法人身份证过期日期：Y-m-d
     */
    private $idCardExpireDate;

    /**
     * @var string 法人手机号
     */
    private $phoneNo;

    /**
     * @var string 结算银行卡开户名称
     */
    private $bankAccountName;

    /**
     * @var string 结算银行卡卡号
     */
    private $bankAccountNo;

    /**
     * @var string 商户状态
     * @see \think\pos\constant\MerchantStatus
     */
    private $status;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
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

    public function getBusinessLicenseNo(): string
    {
        return $this->businessLicenseNo;
    }

    public function setBusinessLicenseNo(string $businessLicenseNo): void
    {
        $this->businessLicenseNo = $businessLicenseNo;
    }

    public function getBusinessName(): string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName): void
    {
        $this->businessName = $businessName;
    }

    public function getIdCardExpireDate(): string
    {
        return $this->idCardExpireDate;
    }

    public function setIdCardExpireDate(string $idCardExpireDate): void
    {
        $this->idCardExpireDate = $idCardExpireDate;
    }
}
