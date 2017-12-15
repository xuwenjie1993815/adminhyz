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

class BillController extends BaseController {

    public function index() {
        $model = M('bill');
        $size = 15;

        $start_time = I('start_time');
        $end_time = I('end_time', date('Y-m-d H:i:s', time()));

        $map = array();

        if (empty($start_time) && !empty($end_time)) {
            $map['tradetime'] = ['ELT', $end_time];
        } else if (empty($end_time) && !empty($start_time)) {
            $map['tradetime'] = ['EGT', $start_time];
        } else if (!empty($start_time) && !empty($end_time)) {
            $map['tradetime'] = ['between', [($start_time), ($end_time)]];
        }
        $map['usertypes'] = 3;
        $map['userid'] = session('companyInfo.id');

        $count = $model->where($map)->count();
        $page = new \Org\Util\Page($count, $size);

        // 获取分页显示
        $fpage = $count > $size ? $page->show() : '';

        $list = $model->where($map)->limit("$page->firstRow, $page->listRows")->order('id desc')->select();

        foreach ($list as $key => $value) {
            $info = M('company')->where(['id' => $value['userid']])->field('invite_name,link_phone as mobile')->find();
            $list[$key]['invite_name'] = $info['invite_name'];
            $list[$key]['mobile'] = $info['mobile'];
            $list[$key]['usertypes'] = '租赁公司';
            $list[$key]['money'] = ($value['money'] / 100);
        }
        $this->assign('fpage', $fpage);
        $this->assign('info', $list);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->display();
    }

}
