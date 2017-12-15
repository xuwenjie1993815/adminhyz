<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/23
 * Time: 16:39
 */

namespace Home\Controller;


class PubController extends \Think\Controller
{

    
    public function login()
    {
        if (IS_POST) {
            $account = trim(I('post.account/s'));
            $pwd = md5(trim(I('post.password/s')));

            $model=M('admin');
            if (empty($account) || empty($pwd)) {
                $this->error('请输入账号或密码！');
            }
            $find = $model->where(array('account' => $account, 'pwd' => $pwd))->find();
            if (empty($find)) {
                $this->error('账号或密码输入错误');exit;
            }
            session('adminInfo', $find);
            $this->success('登录认证成功！正在跳转中...', U('Index/index'),1);
        } else {
            $this->display();
        }
//            $find=$model->where(array('account'=>$account, 'pwd'=>$pwd))->field('id,account,pwd,role_id,nick_name')->find();
//
//            if(empty($find)){
//                $this->error('登录失败！','login',1);
//            }
//
//            unset($find['pwd']);
//
//            session('adminInfo',$find);
//
//            $this->success('登录成功！','/index.php/Home/Index/index');
//
//        } else {
//            $this->display();
//        }
    }
    
    
    
}