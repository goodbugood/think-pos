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
                'gateway' => 'https://extra-business-api.51ydmw.com/',
                'testGateway' => 'https://extra-business-api.ylv3.com/',
                // 代理商编号
                'agentNo' => '119911',
                'aesKey' => '79b13739ff4e4e07',
                'md5Key' => '7d11ffd7850bd7626ac21bb8bdd3dabb',
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
