<?php
/**
 * File Name: TraitTokenController.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/4/19 4:16 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\token;


use qh4module\token\external\ExtToken;
use qh4module\token\models\GenerateToken;

trait TraitTokenController
{
    /**
     * 控制Token模块用的扩展类
     * @return ExtToken
     */
    protected function ext_token()
    {
        return new ExtToken();
    }

    /**
     * 申请自定义的token,和jwt不同,token关联的信息可变
     * 存储在数据库或redis,可以对字段任意修改
     * 该接口可以传入一个旧的token,如果旧的token有效,则不会产生新的token,会将旧的token返回
     * 如果旧的token已过期,并且旧的token是有效的登录状态,则返回的新token的 is_login 字段被标记为 1
     * @return array
     * [
     *      token,
     *      is_login  返回的token是否是登录状态
     *      is_relogin 前端是否应该重新登录
     *      create_time
     *      expiration_time 返回token的过期时间
     * ]
     * 关于 expiration_time 字段,实际业务中,这个时间可能随着请求被更改,前端不能依赖这个时间做token的失效处理
     * 一般来说,接口都会做token的过滤器,如果token失效了会返回401或者403的状态码,前端应该根据http状态码判定
     * 实际的实现方式,前后端约定一致就好
     */
    public function actionApplyToken()
    {
        $model = new GenerateToken([
            'external' => $this->ext_token()
        ]);

        return $this->runModel($model);
    }
}