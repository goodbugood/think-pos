<?php declare(strict_types=1);

/**
 * pos 配置参数
 */

use think\pos\provider\jlpay\JLLSBPosStrategy;

return [
    // pos 品牌服务提供者
    'providers' => [
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
