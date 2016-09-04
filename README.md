# Aliyun OSS SDK for PHP. (&Support streamWrapper) 支持自定义流协议操作。

[![Build Status](https://travis-ci.org/medz/oss-stream-wrapper.svg?branch=master)](https://travis-ci.org/medz/oss-stream-wrapper)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/medz/oss-stream-wrapper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/medz/oss-stream-wrapper/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/medz/oss-stream-wrapper/version)](https://packagist.org/packages/medz/oss-stream-wrapper)
[![Total Downloads](https://poser.pugx.org/medz/oss-stream-wrapper/downloads)](https://packagist.org/packages/medz/oss-stream-wrapper)
[![Latest Unstable Version](https://poser.pugx.org/medz/oss-stream-wrapper/v/unstable)](//packagist.org/packages/medz/oss-stream-wrapper)
[![License](https://poser.pugx.org/medz/oss-stream-wrapper/license)](https://packagist.org/packages/medz/oss-stream-wrapper)

## 概述
看名字就知道，这个包住要实现了oss的流协议封装，因为是基于官方sdk，也包含了官方sdk的全部功能，应该说，这个包，是在官方的sdk基础上增加流协议功能。

## sdk文档
[Aliyun OSS SDK](https://github.com/medz/aliyun-oss-php-sdk/blob/master/README.md)

## Composer
```shell
composer require medz/oss-stream-wrapper
```

### 别名包
 * ⚠️*如果*觉得这个包的名字不是那么容易记住，请看另一个包[medz/aliyun-oss](https://packagist.org/packages/medz/aliyun-oss)这个包作为本包的别名，为了更方便的记忆，当然，类名称有所变化。
 * composer:
```shell
composer require medz/aliyun-oss
```

## Demo
```php
use Medz\Component\StreamWrapper\AliyunOSS\AliyunOSS;

#  如果使用的是medz/aliyun-oss这个包
# use Medz\Component\AliyunOSS\AliyunOSS;

$accessKeyId = "<您从OSS获得的AccessKeyId>"; ;
$accessKeySecret = "<您从OSS获得的AccessKeySecret>";
$endpoint = "<您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com>";
$bucket = "<您使用的Bucket名字，注意命名规范>";
$oss = new AliyunOSS($$accessKeyId, $$accessKeySecret, $$endpoint);
$oss->setBucket($bucket);

// 重要步骤，注册流协议（可以注册多个）
$oss->registerStreamWrapper('oss'); // 默认流协议注册的是 oss:// 这个是可以自定义的，就在这里，定义你希望的流协议前缀。
```
到了这一步，已经成功的注册了流协议，我们来看看怎么使用吧，先来个最简单的写入文件：
```php
file_put_contents('oss://demo/test.txt', 'This is a test content.');
```
很简单吧？如此以来就可以使用原生的文件操作函数或者类来操作oss上的object了～

## 案例：如何在symfony/finder中使用自定义流协议。
首先我说一点，我们都知道，finder组件是可以支持s3等云存储的，没错，finder本身不支持云存储，而是通过自定义流协议完成的，这个在组件文档中有例子，我们来看看在oss中怎么使用它。
```php
// 上面我们已经注册了流协议，所以这里直接写重点代码。
use Symfony\Component\Finder\Finder;

$finder = new Finder();

$path = 'oss://demo'; //拟定一个已经存在的目录，如果不存在也没关系，流协议中已经支持了目录存在与否的判断，是本地文件一样，没有任何区别。
$finder->files()->in($path);

foreach ($finder as $file) {
  var_dump($file); // Symfony\Component\Finder\SplFileInfo
}
```
这样，我们就遍历出了在demo目录下存在的文件了。

## 更多
如果你觉得，这个是自定义协议流的很好案例，但是你却不知道具体需要实现什么方法，没关系，你只需要依赖一个只包含一个接口类的组件，继承它，实现里面的方法，就可以完成你的自定义流协议。

组件：[medz/stream-wrapper-interface](https://packagist.org/packages/medz/stream-wrapper-interface)

## License
MIT
