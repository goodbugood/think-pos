# PHP内置方法扩展

## composer 安装

```
composer require shali/phpmate
```

## 功能

### str2time

在统计数据时，我们经常需要使用 `strtotime` 函数，来计算上个月，下个月之类。但是其返回并不是我们想要的，例如：

```php
$now = '2022-03-31';

echo date('Y-m-d', strtotime('-1 month', strtotime($now)));// 返回的竟然是 2022-03-03，而不是 2022-02-28
echo date('Y-m-d', strtotime('+1 month', strtotime($now)));// 返回的是 2022-05-01，而不是 2022-04-30
```

但是我们想要像 MySQL 内置函数一样的功能。

```sql
SELECT DATE_ADD('2022-03-31', INTERVAL -1 MONTH );
-- 返回 2022-02-28
SELECT DATE_ADD('2022-03-31', INTERVAL +1 MONTH );
-- 返回 2022-04-30
```

那么使用 `str2time` 即可解决跳月问题：

```php
$now = '2022-03-31';

echo date('Y-m-d', str2time('-1 month', strtotime($now)));// 2022-02-28
echo date('Y-m-d', str2time('+1 month', strtotime($now)));// 2022-04-30
```

### equals

PHP8.0 以下版本，`0 == 'php'` 返回的是 true，这在我们看来有点不合符常理，应该是返回 false 才对啊。那么我们封装的 `equals`
就是解决这个问题的，8.0 版本以下不用也可以，要么使用强类型比较，要么自己多加注意，不要让数值 0 跟非空字符串进行比较即可。

```php
0 == 'php';// true
equals(0, 'php');// false
```

## 依赖

1. `ext-bcmath` 货币计算