<?php
/**
 * File Name: HpToken.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021-01-15 10:06 上午
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
use qttx\exceptions\InvalidArgumentException;

class HpToken
{
    static private $token_info = [];

    /**
     * 从请求头部获取token
     * @param string $field
     * @return array|string
     */
    public static function getTokenFromHeader($field = 'authorization')
    {
        return QTTX::$request->headers->get($field);
    }

    /**
     * 获取自定义token的信息,同一个请求该方法反复调用并不会增加开销
     * @param string $token 默认获取当前请求的token信息
     * @param ExtToken|null $ext
     * @param bool $refresh 设置为true,则舍弃缓存,从源获取
     * @param string $field 传入值,则获取指定字段,返回字符串. 不传入则返回整个数组
     * @return array|mixed|null
     */
    public static function getTokenInfo($token = '', $field = null, ExtToken $ext = null, $refresh = false)
    {
        // 获取当前请求token
        if (empty($token)) $token = self::getTokenFromHeader();
        if (empty($token)) {
            return is_null($field) ? null : [];
        }

        // 强制刷新
        if ($refresh) {
            unset(self::$token_info[$token]);
        }

        if (!isset(self::$token_info[$token])) {
            // 使用默认配置类
            if (is_null($ext)) $ext = new ExtToken();

            // 根据配置获取token信息
            if ($ext->storageModel == STORAGE_MODE_MYSQL) {
                $select = '*';
                self::$token_info[$token] = $ext->getDb()
                    ->select($select)
                    ->from('{{%token}}')
                    ->where('token= :token and del_time=0')
                    ->bindValue('token', $token)
                    ->row();
            } else if ($ext->storageModel == STORAGE_MODE_REDIS) {
                $key = sprintf($ext->tokenRedisKey(), $token);
                self::$token_info[$token] = $ext->getRedis()->hgetall($key);
            }
        }

        // 根据字段返回
        if (is_null($field)) {
            return (isset(self::$token_info[$token]) && !empty(self::$token_info[$token])) ? self::$token_info[$token] : [];
        } else {
            return isset(self::$token_info[$token][$field]) ? self::$token_info[$token][$field] : null;
        }

    }

    /**
     * 设置自定义token信息
     * @param $fields
     * @param $token
     * @param ExtToken|null $ext
     */
    public static function setTokenInfo($fields, $token = '', ExtToken $ext = null)
    {
        if (empty($token)) $token = self::getTokenFromHeader();
        if (empty($token)) {
            throw new InvalidArgumentException('无效的Token');
        }

        // 使用默认配置类
        if (is_null($ext)) $ext = new ExtToken();

        // 根据配置获取token信息
        if ($ext->storageModel == STORAGE_MODE_MYSQL) {

            $ext->getDb()
                ->update('{{%token}}')
                ->cols($fields)
                ->where('token= :token')
                ->bindValue('token', $token)
                ->query();

        } else if ($ext->storageModel == STORAGE_MODE_REDIS) {
            $key = sprintf($ext->tokenRedisKey(), $token);
            $ext->getRedis()->hmget($key, $fields);
        }

        unset(self::$token_info[$token]);
    }
}
