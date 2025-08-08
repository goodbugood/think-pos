<?php declare(strict_types=1);

/**
 * pos 配置参数
 */

use think\pos\provider\jlpay\JLLSBPosStrategy;
use think\pos\provider\lipos\LiPosStrategy;

return [
    // pos 品牌服务提供者
    'providers' => [
        // 力 pos
        'lipos' => [
            'class' => LiPosStrategy::class,
            'config' => [
                // 是否开启测试环境模式
                'test' => false,
                'agentNo' => '代理id',
                // 正式网关地址
                'gateway' => '力pos正式网关',
                // 测试网关地址
                'testGateway' => '力pos测试网关',
                // 代理商私钥：签名使用
                'privateKey' => '',
                // 代理商公钥：验签平台响应使用
                'publicKey' => '',
                // pos 平台公钥，加密请求参数用
                'platformPublicKey' => '',
            ],
        ],
        // 移联
        'yilian' => [
            'class' => '\think\pos\provider\yilian\YiLianPosPlatform',
            'config' => [
                'test' => false,
                'gateway' => '移联正式网关',
                'testGateway' => '移联测试网关',
                // 代理商编号
                'agentNo' => '代理编号',
                'aesKey' => '签名，加密，解密密码',
                'md5Key' => '通知验签密钥',
                // 扫码提现手续费费率，单位百分数，如 0.03% 填 0.03
                'scanTypeWithdrawRate' => '0.03',
                // 银联刷卡最大费率限制，单位百分数，如 0.03% 填 0.03，如果提交的费率超过此费率，则取此费率
                'maxBankCardRate' => '0.63',
            ],
        ],
        // 鲲鹏 pos 平台对接参数
        'kunpeng' => [
            'class' => '\think\pos\provider\kunpeng\KunPengPosPlatform',
            'config' => [
                'test' => false,
                'gateway' => '鲲鹏正式服务器地址',
                'testGateway' => '鲲鹏测试网关地址',
                // 应用 id 每个代理一个应用 id 类似其他平台的代理编号
                'appId' => '代理商应用id',
                // 代理商私钥
                'privateKey' => '',
                'publicKey' => '',
                // 鲲鹏平台公钥
                'platformPublicKey' => '',
            ],
        ],
        // 服务商标识
        'lishuaB' => [
            // pos 配置参数
            'config' => [
                // 测试环境
                'test' => false,
                'gateway' => '立刷正式网关',
                // 测试网关地址
                'testGateway' => '立刷测试网关',
                // 立刷机构号
                'agentId' => '立刷机构号',
                // 签名方法，02:RSA私钥签名(SHA256withRSA)
                'signMethod' => '02',
                // 私钥签名
                'privateKey' => '平台私钥',
                // 公钥验签
                'publicKey' => '立刷公钥',
            ],
            'class' => JLLSBPosStrategy::class,
        ]
    ],
];
