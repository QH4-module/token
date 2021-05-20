<?php
/**
 * File Name: GenerateToken1.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021-01-14 4:15 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\token\models;


use QTTX;
use qh4module\token\external\ExtToken;
use qh4module\token\HpToken;
use qttx\exceptions\InvalidArgumentException;
use qttx\helper\StringHelper;
use qttx\web\ServiceModel;

/**
 * Class GenerateToken
 * 生成自定义的token
 * @package qh4module\token\models
 */
class GenerateToken extends ServiceModel
{
    /**
     * @var string 接收参数,旧的token
     */
    public $old_token;

    /**
     * @var string 接收参数,设备编号
     */
    public $device_id = '';

    /**
     * @var string 接收参数,设备类型
     */
    public $device_type = '';


    /**
     * @var ExtToken
     */
    protected $external;

    /**
     * @var string 新生成的token
     */
    protected $token;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['old_token'], 'string', ['max' => 64]],
            [['device_id'], 'string', ['max' => 200]],
            [['device_type'], 'string', ['max' => 20]],
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLangs()
    {
        return [
            'zh_cn' => [
                'old_token' => '旧Token',
                'device_id' => '设备编号',
                'device_type' => '设备类型',
            ]
        ];
    }


    public function run()
    {
        $resp = [
            'token' => $this->generateNewToken(),
            'is_login' => 0,
            'is_relogin' => 1,
            'create_time' => time(),
            'expiration_time' => time() + $this->external->effectiveTime,
        ];

        if ($this->old_token) {

            $result_old = HpToken::getTokenInfo($this->old_token, null, null, true);

            if ($result_old) {

                HpToken::setTokenInfo(['del_time' => time()],$this->old_token);

                if ($result_old['device_id'] == $this->device_id &&
                    $result_old['device_type'] == $this->device_type &&
                    $result_old['is_logout'] == 0 &&
                    (
                    $result_old['expiration_time'] > time() ||
                    $result_old['expiration_time'] < time() + $this->external->reLoginTime
                    )
                ) {

                    // 旧的token是有效的,则继承旧token的登录状态
                    if ($result_old['is_login'] == 1 && $result_old['user_id']) {
                        $resp['is_login'] = 1;
                        $resp['is_relogin'] = 0;
                        $resp['user_id'] = $result_old['user_id'];
                    }
                }
            }
        }

        // 保存token
        $this->insertToken($resp);

        return $resp;

    }

    /**
     * 保存token
     * @param $ary
     */
    public function insertToken($ary)
    {
        $cols = [
            'token' => $ary['token'],
            'create_time' => $ary['create_time'],
            'expiration_time' => $ary['expiration_time'],
            'is_login' => $ary['is_login'],
            'is_logout' => 0,
            'user_id' => isset($ary['user_id']) ? $ary['user_id'] : 0,
            'device_type' => $this->device_type,
            'device_id' => $this->device_id,
            'old_token' => $this->old_token,
            'del_time' => 0,
        ];

        if ($this->external->storageModel == STORAGE_MODE_MYSQL) {
            $this->external->getDb()
                ->insert('{{%token}}')
                ->cols($cols)
                ->query();
        } else if ($this->external->storageModel == STORAGE_MODE_REDIS) {
            $key = sprintf($this->external->tokenRedisKey(), $ary['token']);
            $this->external->getRedis()->hmset($key, $cols);
            $this->external->getRedis()->expire($key, $this->external->effectiveTime + $this->external->reLoginTime);
        } else {
            throw new InvalidArgumentException('无效的Token存储方式');
        }
    }

    /**
     * 生成一个新的token
     * @return string
     */
    public function generateNewToken()
    {
        $a = QTTX::$app->snowflake->id(true);
        $b = StringHelper::random(63 - strlen($a));
        return $a . '.' . $b;
    }
}
