<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Client\Controller;

use Think\Exception;

class LoginController extends \Think\Controller {

    //put your code here
    //登录
    public function login() {
        if (IS_POST) {
            $user = trim(I('post.link_phone'));
            $pwd = md5(I('post.password'));
            $model = M('company');
            if (empty($user) || empty($pwd)) {
                $this->error('请输入账号或密码！');
            }
            $find = $model->where(array('link_phone' => $user, 'password' => $pwd))->find();

            if (empty($find)) {
                $this->error('账号或密码输入错误');exit;
            }
            session('companyInfo', $find);
            $this->success('登录认证成功！正在跳转中...', U('Index/index'),2);
        } else {
            $this->display();
        }
    }

}
