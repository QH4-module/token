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