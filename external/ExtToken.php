<?php
/**
 * File Name: ExtToken.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021-01-14 3:52 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\token\external;


use qttx\web\External;
use QTTX_Base;

class ExtToken extends External
{

    /**
     * @var float|int token 有效期
     */
    public $effectiveTime = 3600 * 24;

    /**
     * @var int token过期多久后必须重新登录,单位秒
     * token的返回值带有 is_relogin 字段,会被标示为1
     * 这个值大多数情况意味着,用户N长时间不登录软件,需要重新登录
     */
    public $reLoginTime = 3600 * 24 * 7;

    /**
     * @var string 保存方式
     * 可以使用 `STORAGE_MODE_*`
     */
    public $storageModel = STORAGE_MODE_MYSQL;


    /**
     * 使用redis存储的键名
     * 结果将会使用`sprintf()`函数格式化,所以返回字符串必须携带一个 '%s'
     * @return string
     */
    public function tokenRedisKey()
    {
        $app_name = QTTX_Base::getConfig('app_name', 'qttx:frame');
        return "{$app_name}:token:%s:h";
    }


    /**
     * 对返回值进行格式化处理
     * @param $result
     * @return mixed
     */
    public function formatResult($result)
    {
        return $result;
    }

}
