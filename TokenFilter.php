<?php
/**
 * File Name: TokenFilter.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2020-11-30 10:40 上午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\token;


use QTTX;
use qh4module\token\external\ExtToken;
use qh4module\token\models\GenerateToken;
use qttx\exceptions\InvalidArgumentException;

/**
 * Class TokenFilter
 * Token过滤器,一般用于 controller 中执行 action 之前
 * @package service\filters\token
 */
class TokenFilter
{

    public static $payload = [];

    /**
     * 获取token中的携带的用户信息,通过此方法可以防止出现下标不存在的异常
     * @param string $field 不传入则获取整个数组
     * @param mixed $default 获取单个元素时候,不存在的默认值
     * @return array|mixed|null
     */
    public static function getPayload($field = null, $default = null)
    {
        if (empty($field)) {
            return self::$payload;
        } else {
            return isset(self::$payload[$field]) ? self::$payload[$field] : $default;
        }
    }

    /**
     * 校验自定义的token
     * 校验成功后会将token相关信息保存到静态属性 $payload 中
     * @param bool $checkLogin 是否校验登录状态
     * @param ExtToken $external
     * @return bool
     * @see GenerateToken 自定义token的生成
     */
    public static function customer($checkLogin = false, ExtToken $external = null)
    {
        if (is_null($external)) $external = new ExtToken();

        $token = HpToken::getTokenFromHeader();

        $info = HpToken::getTokenInfo($token, null, $external);

        if (empty($info) ||
            !isset($info['expiration_time']) ||
            $info['expiration_time'] < time() ||
            !isset($info['is_logout']) ||
            $info['is_logout'] != 0
        ) {
            QTTX::$response->setStatusCode(401);
            return false;
        }

        if ($checkLogin) {
            if ($info['is_login'] != 1 || !$info['user_id']) {
                QTTX::$response->setStatusCode(403);
                return false;
            }
        }

        self::$payload = $info;

        return true;
    }


    /**
     * 校验jwt格式的token
     * 校验成功后会将用户信息保存到静态属性 $payload 中
     * @param string $key 解密用的密钥
     *                    如果不传该参数,则默认去 libs 目录下搜索 rsa-private-key.pem 文件
     * @return bool
     */
    public static function jwt($key = '')
    {
        if (empty($key)) {
            $key = file_get_contents(APP_PATH . '/libs/rsa-private-key.pem');
        }
        if (empty($key)) {
            throw new InvalidArgumentException('jwt key is cannot be empty!');
        }

        $token = HpToken::getTokenFromHeader();

        if (empty($token)) {
            QTTX::$response->setStatusCode(401);
            return false;
        }

        if (!JWT::verify($token, $key)) {
            QTTX::$response->setStatusCode(401);
            return false;
        }

        list($header, $payload) = JWT::decode($token);
        $time = time();
        if (isset($payload['iat']) && $payload['iat'] > $time) {
            QTTX::$response->setStatusCode(401);
            return false;
        }
        if (isset($payload['nbf']) && $payload['nbf'] > $time) {
            QTTX::$response->setStatusCode(401);
            return false;
        }
        if (isset($payload['exp']) && $payload['exp'] < $time) {
            QTTX::$response->setStatusCode(401);
            return false;
        }

        static::$payload = $payload;

        return true;
    }
}
