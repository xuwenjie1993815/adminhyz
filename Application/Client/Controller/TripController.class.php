<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Client\Controller;

use Think\Exception;

/**
 * Description of IndexController
 *
 * @author Administrator
 */
class TripController extends BaseController {

    /**
     * 行程列表
     */
    public function index() {
        $session = session('companyInfo.id');
        $keywords = I('get.keywords');
        if (!empty($keywords)) {
            $where['a.order_sn|a.link_phone|b.true_name'] = array('like', "%$keywords%");
        }
        $where['b.company_id'] = $session;
        $model = M('service');
        $count = $model->alias('a')->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')->where($where)->count();
        $size = 10;
        // 开启分页类
         $page = new \Org\Util\Page($count, $size);


        // 获取分页显示
        $fpage = $count > $size ? $page->Show() : '';


        $list = $model->alias('a')
                ->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')
				->join('__DRIVER__ as c on b.id = c.id', 'INNER')
                ->field('b.company_id,a.*, b.true_name, b.license_sn, b.head_pic,c.invite_name')
                ->limit("{$page->firstRow}, {$page->listRows}")
                ->where($where)
                ->select();
        $this->assign('fpage', $fpage);
        $this->assign('list', $list);
        $this->assign('keywords', $keywords);
        $this->display();
    }

    public function info() {
        $service_model = new \Home\Model\ServiceModel();
        $service_id = I('get.service_id');
        $field = 'a.id, a.dj_status, a.order_sn, a.link_phone, a.remark, a.start_city, a.start_point, a.purpose_city, a.purpose_point, a.type';
        $field .= ' ,a.is_yy, a.price, a.block, a.status, a.departur_time, a.create_time, b.license_sn, b.car_load_num';

        //获取司机行程信息
        $info = $service_model->alias('a')->join('__DRIVER_INFO__ AS b ON a.driver_id = b.id', 'INNER')
                ->where(array('a.id' => $service_id))
                ->field($field)
                ->find();
        $info['start_city'] = M('region')->where(array('id' => $info['start_city']))->getField('name') ? : '-';
        $info['purpose_city'] = M('region')->where(array('id' => $info['purpose_city']))->getField('name') ? : '-';
        $info['start_point'] = M('region')->where(array('id' => $info['start_point']))->getField('name') ? : '-';
        $info['purpose_point'] = M('region')->where(array('id' => $info['purpose_point']))->getField('name') ? : '-';

        $order_model = new \Home\Model\OrderModel();
        $info['order_list'] = $order_model->alias('a')->join('__USER__ as b ON a.user_id = b.id', 'INNER')
                ->where(array('a.service_id' => $info['id']))
                ->order('a.create_time')
                ->field('a.order_sn, a.seat_num, a.money, a.pay_mothod, a.status, a.create_time, a.remark, b.mobile, b.nick_name')
                ->select();
        $this->assign('info', $info);
        $this->display();
    }

}
