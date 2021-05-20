QH4框架扩展模块-Token模块

该模块提供了两种 `Token` 的生成方式

<span style="color: #f03c15"> 注意：关联信息中必须有 user_id 字段保存登录用户的ID.作为一种约定，后续所有的服务层获取用户ID都将取该字段 </span>


### `JSON web token` 方式

使用这种方式,需要一份密钥对,关于密钥对的生成,参见 `JWT` 类的 `encode` 方法的注释

### `自定义token` 方式

使用这种方式,支持 MySQL 存储或者 Redis 存储

默认使用 MySQL 存储

如果使用 Redis 存储,请注意所有接收 `ExtToken` 参数的公共方法,都需要手动传入配置类,否则获取的所有结果均为空

例如 `HpToken::getTokenInfo` 方法的第三个参数

### 关于`TokenFilter` 类

`getPayload` 方法用于获取 Token 的关联信息,比如用户id

类中的其它方法一般用于 `Controller` 过滤器


### api列表
```php
actionApplyToken()
```
申请自定义的token

### 方法列表
```php
/**
 * 从请求头部获取token
 * @param string $field
 * @return array|string
 */
public static function getTokenFromHeader($field = 'authorization')
```

```php
/**
 * 获取自定义token的信息,同一个请求该方法反复调用并不会增加开销
 * @param string $token 默认获取当前请求的token信息
 * @param ExtToken|null $ext
 * @param bool $refresh 设置为true,则舍弃缓存,从源获取
 * @param string $field 传入值,则获取指定字段,返回字符串. 不传入则返回整个数组
 * @return array|mixed|null
 */
public static function getTokenInfo($token = '', $field = null, ExtToken $ext = null, $refresh = false)
```

```php
/**
 * 设置自定义token信息
 * @param $fields
 * @param $token
 * @param ExtToken|null $ext
 */
public static function setTokenInfo($fields, $token = '', ExtToken $ext = null)
```

### JWT 方法列表
```php
/**
 * 将用户数据转换为JWT字符串
 * @param mixed $payload 用户自定义数据,如果使用和用户相关的上层服务,请务必在该参数中加入 user_id
 * 标准中注册的声明 (建议但不强制使用)
 * iss: jwt签发者
 * sub: jwt所面向的用户
 * aud: 接收jwt的一方
 * exp: jwt的过期时间，这个过期时间必须要大于签发时间
 * nbf: 定义在什么时间之前，该jwt都是不可用的
 * iat: jwt的签发时间
 * jti: jwt的唯一身份标识。
 *
 * @param string $key 密钥
 * @param string $alg 算法,参见 $supported_algs
 *                     使用RS系列的算法虽然更安全,但是根据私钥长度,生成的字符串长度可能很大
 *                      RS系列: 同样的密钥,用户数据越大,签名越大;同样的用户数据,私钥越长,签名越大
 *                      HS系列: 用户数据越大,签名越大,和密钥长度无关
 *                      经过作者实验,RS使用256位私钥比HS系列短,使用512及以上长度比HS系列长
 *                      另外:RS系列密钥必须符合一定格式,HS系列密钥任意字符串
 *                      RS系列密钥生成命令:
 *                      私钥: openssl genrsa -out 文件名.pem 长度
 *                          openssl genrsa -out rsa-private-key.pem 1024
 *                      通过私钥生成公钥: openssl rsa -in 私钥.pem -pubout -out 公钥.pem
 *                          openssl rsa -in rsa-private-key.pem -pubout -out rsa-public-key.pem
 *
 *                     使用RS系列的算法,密钥区分公钥和私钥,HS系列不区分
 *                     根据实际情况选择,一般日常应用,使用HS系列加32位随机key即可满足需求
 * @param array $header 额外的头部
 * @return string
 * @see $supported_algs 允许的算法
 */
public static function encode($payload, $key, $alg = 'HS256', $header = null)
```

```php
/**
 * 解析JWT字符串
 * @param $jwt
 * @param bool $assoc 作为 json_decode() 函数的第二个参数
 * @return array|null 无效返回 null, 有效返回解析出的数据
 */
public static function decode($jwt, $assoc = true)
```

```php
/**
 * 校验JWT字符串
 * @param string $jwt
 * @param string $key HS系列算法使用同一个密钥,RS系列算法区分公钥和私钥
 * @param string $alg
 * @return bool
 */
public static function verify($jwt, $key, $alg = 'HS256')
```

### TokenFilter 方法列表
```php
/**
 * 获取token中的携带的用户信息,通过此方法可以防止出现下标不存在的异常
 * @param string $field 不传入则获取整个数组
 * @param mixed $default 获取单个元素时候,不存在的默认值
 * @return array|mixed|null
 */
public static function getPayload($field = null, $default = null)
```
<span style="color: #f03c15">非常重要的一个方法,最常用的就是获取访问的用户id</span>

```php
/**
 * 校验自定义的token
 * 校验成功后会将token相关信息保存到静态属性 $payload 中
 * @param bool $checkLogin 是否校验登录状态
 * @param ExtToken $external
 * @return bool
 * @see GenerateToken 自定义token的生成
 */
public static function customer($checkLogin = false, ExtToken $external = null)
```

```php
/**
 * 校验jwt格式的token
 * 校验成功后会将用户信息保存到静态属性 $payload 中
 * @param string $key 解密用的密钥
 *                    如果不传该参数,则默认去 libs 目录下搜索 rsa-private-key.pem 文件
 * @return bool
 */
public static function jwt($key = '')
```