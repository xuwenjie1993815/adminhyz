<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Client\Controller;

use Think\Exception;
use Home\Logic\DriverLogic;

/**
 * Description of IndexController
 *
 * @author Administrator
 */
session_start();

class IndexController extends BaseController {

    //put your code here
    //退出登录
    public function logout() {
        session('companyInfo', NULL);
        $this->redirect('Client/Login/login');
    }

    /**
     * 首页
     */
    public function index() {
        $this->display();
    }

    /**
     * 我的信息
     */
    public function info() {
        $session = session('companyInfo');
        $data = I('post.');
        $data['update_time'] = time();
        if (IS_POST) {
            $map['id'] = array('neq', $session['id']);
            $link_phone = M('company')->where($map)->where(array('link_phone' => $data['link_phone']))->find();
            if ($link_phone) {
                $this->error('手机号已存在');
                exit;
            }
            $res = M('company')->where(array('id' => $session['id']))->save($data);

            if ($res) {
                $this->success('修改成功');
                exit;
            } else {
                $this->error('修改失败');
                exit;
            }
        }
        $info = M('company')->where(array('id' => $session['id']))->find();
        $zong = $info['income'];
        $zhi = $info['spend'];
        $sheng = $zong - $zhi;
        $this->assign('info', $info);
        $this->assign('sheng', $sheng);
        $this->display();
    }

    /**
     * 司机列表
     */
    public function driver_list() {
        $keywords = I('get.keywords');
        if (!empty($keywords)) {
            $where['b.true_name|a.mobile|b.license_sn'] = array('like', "%$keywords%");
        }
        $where['c.id'] = session('companyInfo.id');
        $count = M('driver')->alias('a')
                ->join('__DRIVER_INFO__ as b on a.id = b.id', 'LEFT')
                ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                ->where($where)
                ->count();
        $size = 10;
        // 开启分页类
        $page = new \Think\Page($count, $size);

        // 获取分页显示
        $fpage = $count > $size ? $page->Show() : '';
        $list = M('driver')->alias('a')
                ->join('__DRIVER_INFO__ as b on a.id = b.id', 'LEFT')
                ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                ->field('a.*, b.*, c.invite_name as company_name')
                ->limit("{$page->firstRow}, {$page->listRows}")
                ->where($where)
                ->select();
        $this->assign('fpage', $fpage);
        $this->assign('keywords', $keywords);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 司机信息
     */
    public function driver_info() {
        $driver_id = I('get.id');
        $where['a.id'] = $driver_id;
        //1. 获取基本信息
        $info = M('driver')->alias('a')
                ->join('__DRIVER_INFO__ as b on a.id = b.id', 'LEFT')
                ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                ->where($where)
                ->field('a.*, b.*, c.invite_name as company_name')
                ->find();
        //2. 获取详细信息
        $this->assign('info', $info);
//        dump($info);exit;
        $this->display();
    }

    /**
     * 司机启用/停用
     */
    public function start() {
        try {
            $data['status'] = DriverLogic::driverUse(I('post.driver_id/d', 0));

            $this->ajaxReturn(returnData(1, '操作成功', $data));
        } catch (\Exception $e) {
            $this->ajaxReturn(returnData(0, $e->getMessage()));
        }
    }

    //编辑密码
    public function edit_password() {
        $password = md5(I('post.password'));
        $repassword = md5(I('post.repassword'));
        $session = session('companyInfo');
        if (IS_POST) {
            if ($password == $repassword) {
                $res = M('company')->where(array('id' => $session['id']))->save(array('password' => $password, 'update_time' => time()));
                if ($res) {
                    $this->ajaxReturn(array('status' => 1, 'msg' => '修改成功!'));
                } else {
                    $this->ajaxReturn(array('status' => 0, 'msg' => '修改失败'));
                }
            }
        }
        $this->assign('session', $session);
        $this->assign('id', $id);
        $this->display();
    }

    //推广管理
    public function extend() {
        $session = session('companyInfo');
        $keywords = I('get.keywords');
        if (!empty($keywords)) {
            $where['d.invite_name|d.mobile'] = array('like', "%$keywords%");
        }
        $where['dinfo.company_id'] = $session['id'];
        //1. 邀请人信息
        $driver_info = M('driver as d')
                ->field('d.id,d.invite_code,d.invite_type,d.invite_from_code,d.invite_name,d.mobile,d.cart_id')
                ->join('cjkc_driver_info as dinfo on dinfo.id = d.id', 'LEFT')
                ->where($where)
                ->select();
        foreach ($driver_info as $k => $v) {
            $driver = M('driver')->where(array('invite_from_code' => $v['invite_code']))->field('id,invite_name,mobile,cart_id,invite_code,invite_from_code')->select();
            $user = M('user')->where(array('invite_from_code' => $v['invite_code']))->field('id,invite_name,mobile,cart_id,invite_code,invite_from_code')->select();

            if (!empty($driver) && !empty($user)) {
                $tmp = array_merge($driver, $user);
            } elseif (!empty($driver) && empty($user)) {
                $tmp = $driver;
            } elseif (empty($driver) && !empty($user)) {
                $tmp = $user;
            } else {
                $tmp = [];
            }
<<<<<<< .mine
			$driver_info[$k]['list'] = $tmp;
||||||| .r32
=======
            $driver_info[$k]['list'] = $tmp;
>>>>>>> .r33
        }
//        dump($driver_info);exit;
        $this->assign('keywords', $keywords);
        $this->assign('info', $driver_info);
//        dump($driver_info);exit;
//        dump($driver);exit;
        $this->display();
    }

    /**
     * 该人所推广的人员
     */
    function son_of_driver_invite() {
        $code = I('invite_code');
        $name = I('invite_name');
        $invite_name = I('invites_name');
        if (!$code || empty($code)) {
            $this->error('邀请码有误');
            exit;
        }
        $keywords = I('get.keywords');
        if (!empty($keywords)) {
            $where['invite_name|mobile'] = array('like', "%$keywords%");
        }
        $where['invite_from_code'] = $code;
        $driver = M('driver')->where($where)->field('id,invite_name,mobile,cart_id,invite_code,invite_from_code')->select();
        $user = M('user')->where($where)->field('id,invite_name,mobile,cart_id,invite_code,invite_from_code')->select();
        if (!empty($user)) {
            foreach ($user as $key => $value) {
                array_push($driver, $value);
            }
        }
        $this->assign('info', $driver);
        $this->assign('name', $name);
        $this->assign('invite_name', $invite_name);
//        dump($name);exit;
        $this->display();
//        $this->ajaxReturn(['status'=>true,"msg"=>"操作成功","data"=>$driver]);exit;
    }

}
