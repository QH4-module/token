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
     */
    public function actionApplyToken()
    {
        $model = new GenerateToken([
            'external' => $this->ext_token()
        ]);

        return $this->runModel($model);
    }
}