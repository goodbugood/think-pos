# pos 机对接框架

> 如果觉得 `think-pos` 不错，欢迎给个 star，谢谢。

## 目录结构

```php
src/config/pos.php pos 配置文件，写好每个厂商的配置参数模版，记住是模版，不要把真实参数提交
src/extend/ pos 接口扩展，你在对接时定义的工具类
src/provider/ pos 接口服务提供者，一个厂商一个包，每个包里新建 `README.md` 文件，里面写厂商的接口文档
src/phpmate/ 该目录下的文件未来将独立出去进行单独维护的，你不可以在此包下添加文件
```

## 说明

### dto

对于解析后返回的 dto，如 `PosInfoResponse` 等：

1. 属性值是对象，getXxx 会返回两种值；如果返回 null，说明未使用此属性，或上游未返回该字段，都会尽量返回对象或 null。
2. 属性值是字符串类型，getXxx 会返回两种值；如果返回 '' 说明未使用此字段或上游返回 ''，如果返回 'null' 说明上游返回了 null。都会尽量保持返回字符串。

## 使用方法

### 安装

```shell
composer require shali/think-pos
```

### 配置

1. 找到你项目的 `config` 目录，新建 `pos.php` 文件，并复制 `think-pos/src/config/pos.php` 文件内容到 `pos.php` 文件中
2. 如果是项目第一次安装，他会自动帮你生成 `pos.php` 文件，然后按照服务商的配置进行配置

### 接入请求业务

```php
// 1. 构建 pos 策略完成业务
$posRequestDto = new PosRequestDto();
$posRequestDto->setDeviceSn($posSn);

$posStrategy = PosStrategyFactory::create('lakala');
$posInfoResponse = $posStrategy->getPosInfo($posRequestDto);

if ($posInfoResponse->isFail()) {
    // 出错了，获取错误信息
    exit($posInfoResponse->getErrorMsg());
}
// 处理 pos 查询，通过 $posInfoResponse 获得你的业务数据
$withdrawFee = $posInfoResponse->getWithdrawFee();
```

### 接入回调通知

```php
// 1. 回调业务接入也很简单
$callbackRequest = $posStrategy->handleCallback('pos 平台回调数据');
if ($callbackRequest->isFail()) {
    exit($callbackRequest->getErrorMsg());
}
if ($callbackRequest instanceof PosTransCallbackRequest) {
    // 处理 pos 订单交易回调，通过 $callbackRequest 获得你的业务数据
}
// 返回 ack 给 pos 平台
exit($posStrategy->getCallbackAckContent());
```

你只需要在你定义业务中添加上述代码，后续再需要接入其他 pos 机厂商时，无需改动业务代码，只需要在 `pos.php` 配置文件中添加新的
pos 机厂商的配置参数，即可完成接入。

## 参与贡献

### 流程

1. fork 本项目到你的 github 仓库
2. 提交代码到你的 github 仓库
3. 创建 pull request 请求到 dev 分支

### 贡献原则

1. git 提交信息必须携带标签，fix，feat 之类
2. 注释，文档必须遵循[中文文案排版指北](https://github.com/sparanoid/chinese-copywriting-guidelines/blob/master/README.zh-Hans.md)
3. 代码风格必须遵循 [PSR-2](https://www.php-fig.org/psr/psr-2/) 规范
4. 接入的厂商尽可能提供完整的接口文档和测试用例

## 支持厂商列表

1. [拉卡拉](https://github.com/shali/think-pos/tree/master/src/provider/lakala)
2. [立刷](https://github.com/shali/think-pos/tree/master/src/provider/jlpay)
3. [力POS](https://github.com/shali/think-pos/tree/master/src/provider/lipos)
4. [移联]()

## 依赖

1. [shali/phpmate](https://github.com/shali/phpmate)
2. `ext-curl` http 请求依赖
3. `ext-openssl` curl 请求，签名使用
4. `ext-json` json 处理
5. `php` 7.3+