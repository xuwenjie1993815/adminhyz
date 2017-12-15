<?php

/* * *************************************************************
 * @Desc 系统通用控制器：需登录
 * @Version v1.0.0 
 * @Author chenhg  <945076855@qq.com>
 * @Date  2017年4月7日11:47:44
 * *********************************************************** */

namespace Client\Controller;

class BaseController extends \Think\Controller {

    /**
     * 初始化方法
     */
    protected function _initialize() {
        $uid = session('companyInfo.id');
        if (!$uid) {
            $this->error('请先登录账户', U('Client/login/login'), 1);
        }
    }

}
