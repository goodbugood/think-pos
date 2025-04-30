# pos 机对接框架

## 目录结构

```php
src/config/pos.php // pos 配置文件，写好每个厂商的配置参数模版，记住是模版，不要把真实参数提交
src/dto/ // pos 接口数据传输对象
src/exception/ // pos 接口异常，不细分不需要再定义
src/extend/ // pos 接口扩展，你在对接时定义的工具类
src/provider/ // pos 接口服务提供者，一个厂商一个包，每个包里新建 `README.md` 文件，里面写厂商的接口文档
src/phpmate/ // 该目录下的文件未来将独立出去进行单独维护的，你不可以在此包下添加文件
```

## 使用方法

```php
// 1. 在你的 tp 项目下面安装 `shali/think-pos` 包
composer require shali/think-pos

// 2. 找到你项目的 `config` 目录里的 `pos.php` 配置文件，按照服务商的配置进行配置

// 3. 构建 pos 策略完成业务
$posRequestDto = new PosRequestDto();
$posRequestDto->setDeviceSn($posSn);

$posStrategy = PosStrategyFactory::create('lakala');
$posInfoResponse = $posStrategy->getPosInfo($posRequestDto);

if ($posInfoResponse->isFail()) {
    // 出错了，获取错误信息
    exit($posInfoResponse->getErrorMsg());
}
// 获取成功，提取返回数据
$withdrawFee = $posInfoResponse->getWithdrawFee();
```

你只需要在你定义业务中添加上述代码，后续再需要接入其他 pos 机厂商时，无需改动业务代码，只需要在 `pos.php` 配置文件中添加新的
pos 机厂商的配置参数，即可完成接入。

## tp5 和 tp6 的服务发现有区别

tp5

```json
{
    "extra": {
        "think-config": {
            "pos": "src/config/pos.php"
        },
        "think-extend": {
            "service": [
                "think\\pos\\service\\PosService"
            ]
        }
    }
}
```

tp6

```json
{
    "extra": {
        "think": {
            "config": {
                "pos": "src/config/pos.php"
            },
            "services": {
                "pos": "think\\pos\\service\\PosService"
            }
        }
    }
}
```