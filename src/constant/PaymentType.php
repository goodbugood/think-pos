<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * 支付方式枚举，我们是细分的支付方式，可不是支付渠道
 * @author shali
 * @date 2025/05/13
 */
interface PaymentType
{
    /**
     * 支付宝扫码
     */
    const ALIPAY_QR = 'alipay_qr';

    /**
     * 微信扫码
     */
    const WECHAT_QR = 'wechat_qr';

    /**
     * 微信 app 支付方式
     * APP支付：在第三方应用中集成微信支付功能，用户可以在应用内完成支付。
     */
    const WECHAT_APP = 'wechat_app';

    /**
     * 银联扫码
     */
    const UNION_QR = 'union_qr';

    /**
     * 京东扫码
     */
    const JD_QR = 'jd_qr';

    /**
     * 扫码支付
     */
    const QR_SCAN = 'qr_scan';

    /**
     * 扫码支付列表
     */
    const QR_SCAN_LIST = [
        self::QR_SCAN,
        self::ALIPAY_QR,
        self::WECHAT_QR,
        self::JD_QR,
        self::UNION_QR,
    ];

    /**
     * 微信支付方式列表
     */
    const WECHAT_LIST = [
        self::WECHAT_APP,
        self::WECHAT_QR,
    ];

    /**
     * 信用卡
     */
    const CREDIT_CARD = 'credit_card';

    /**
     * 借记卡
     */
    const DEBIT_CARD = 'debit_card';

    /**
     * 银行卡刷卡支付
     */
    const BANK_CARD = 'bank_card';

    /**
     * 刷银行卡方式列表
     */
    const BANK_CARD_LIST = [
        self::BANK_CARD,
        self::DEBIT_CARD,
        self::CREDIT_CARD,
    ];

    /**
     * 闪付=nfc=碰一碰
     */
    const NFC = 'nfc';

    /**
     * 支付宝碰一碰闪付
     */
    const ALIPAY_NFC = 'alipay_nfc';

    /**
     * 闪付列表
     */
    const NFC_LIST = [
        self::NFC,
        self::ALIPAY_NFC,
    ];
}
