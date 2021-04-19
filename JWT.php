<?php
/**
 * File Name: JWT.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2020-11-18 1:08 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\token;


use qttx\exceptions\InvalidArgumentException;

/**
 * Class JWT
 * JSON Web Token
 * 这种验证方式比较轻巧,不需要任何数据库或者缓存,可以直接使用
 * 缺点是:一旦生成变更会非常麻烦,因此,并不适合携带可变数据
 * 可以使用其它数据库来辅助使用,将过期时间和关联信息等可变数据保存到数据库中,但是这样做也就失去了轻便性
 * 如果保存可变数据,推荐使用另一种自定义方式的token
 * @package service\account
 */
class JWT
{
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
    {
        $_header = array('typ' => 'JWT', 'alg' => $alg);
        if (!is_null($header) && is_array($header)) {
            $_header = array_merge($header, $_header);
        }

        $ary = array();
        $ary[] = static::urlBase64Encode(json_encode($_header));
        $ary[] = static::urlBase64Encode(json_encode($payload));
        $signing_input = implode('.', $ary);

        $signature = static::sign($signing_input, $key, $alg);
        $ary[] = static::urlBase64Encode($signature);

        return implode('.', $ary);
    }

    /**
     * 解析JWT字符串
     * @param $jwt
     * @param bool $assoc 作为 json_decode() 函数的第二个参数
     * @return array|null 无效返回 null, 有效返回解析出的数据
     */
    public static function decode($jwt, $assoc = true)
    {
        $ary = explode('.', $jwt);
        if (count($ary) != 3) return null;

        $header = json_decode(static::urlBase64Decode($ary[0]),
            $assoc, 512, JSON_BIGINT_AS_STRING);
        $payload = json_decode(static::urlBase64Decode($ary[1]),
            $assoc, 512, JSON_BIGINT_AS_STRING);
        $signature = static::urlBase64Decode($ary[2]);

        return array($header, $payload, $signature);
    }

    /**
     * 校验JWT
     * @param string $jwt
     * @param string $key HS系列算法使用同一个密钥,RS系列算法区分公钥和私钥
     * @param string $alg
     * @return bool
     */
    public static function verify($jwt, $key, $alg = 'HS256')
    {
        $ary = explode('.', $jwt);
        if (count($ary) != 3) return false;

        if (!isset(static::$supported_algs[$alg])) {
            throw new InvalidArgumentException('Invalid signature algorithm');
        }

        list($header, $payload, $signature) = $ary;
        $input = "$header.$payload";
        $signature = static::urlBase64Decode($signature);

        list($type, $alg) = static::$supported_algs[$alg];
        if ($type == 'openssl') {
            $ret = openssl_verify($input, $signature, $key, $alg);
            if($ret !== 1) return false;
        } else {
            $hash = hash_hmac($alg, $input, $key, true);
            if(!hash_equals($signature, $hash)) return false;
        }

        return true;
    }


    /**
     * 允许签名算法
     * @var \string[][]
     */
    protected static $supported_algs = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
    );


    /**
     * 将字符串编码为 URL 格式的 base64格式
     * @param $input
     * @return string|string[]
     */
    public static function urlBase64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * 将 urlBase64Encode() 函数编码的字符串进行解码
     * @param $input
     * @return false|string
     */
    public static function urlBase64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * 使用指定的密钥和算法对数据加密
     * @param $input string  要加密的数据
     * @param $key  string 密钥
     * @param $alg  string  算法
     * @return string
     */
    protected static function sign($input, $key, $alg)
    {
        if (!isset(static::$supported_algs[$alg])) {
            throw new InvalidArgumentException('Invalid signature algorithm');
        }

        list($type, $alg) = static::$supported_algs[$alg];

        if ($type == 'hash_hmac') {
            return hash_hmac($alg, $input, $key, true);
        } else if ($type == 'openssl') {
            $signature = '';
            openssl_sign($input, $signature, $key, $alg);
            return $signature;
        } else {
            throw new InvalidArgumentException('Invalid signature algorithm');
        }
    }
}
