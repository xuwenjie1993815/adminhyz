<?php

namespace Home\Logic;


use Home\Model\AdvertModel;
use Think\Exception;

class BillLogic
{
    static public function billselect($where,$size){
        $model = M('bill');
        $count = $model->where($where)->count();
        $page = new \Org\Util\Page($count,$size);
        
        $bill_all = $model->where($where)->limit("$page->firstRow, $page->listRows")->order('id desc')->select();
        
        // 获取分页显示
        $fpage = $count > $size ? $page->show() : '';
        
        foreach ($bill_all as $key => $value) {
            $bill_all[$key]['money'] = ($value['money'])/100;
            switch ($value['usertypes']) {
                case 1://usertypes 1:司机 2：乘客
                    $driver = M('driver')->where(['id'=>$value['userid']])->field('invite_name')->find();
                    $bill_all[$key]['invite_name'] = $driver['invite_name'];
                    
                    break;
                case 2:
                    $user = M('user')->where(['id'=>$value['userid']])->field('invite_name')->find();
                    $bill_all[$key]['invite_name'] = $user['invite_name'];

                    break;
                case 3:
                    $company = M('company')->where(['id'=>$value['userid']])->field('invite_name')->find();
                    $bill_all[$key]['invite_name'] = $company['invite_name'];

                    break;

                default:
                    break;
            }
        }
        return array($bill_all, $fpage);
    }
	static public function remittanceselect($where, $size) {
        $model = M('remittance');
        $count = $model->where($where)->count();
        $page = new \Org\Util\Page($count, $size);

        $bill_all = $model->where($where)->limit("$page->firstRow, $page->listRows")->order('createtime desc')->select();

        // 获取分页显示
        $fpage = $count > $size ? $page->show() : '';
        foreach ($bill_all as $key => $value) {
            $bill_all[$key]['money'] = ($value['money']);

            $driver = M('driver')->where(['invite_code' => $value['invite_code']])->field('invite_name')->find();
            $user = M('user')->where(['invite_code' => $value['invite_code']])->field('invite_name')->find();
            $company = M('company')->where(['invite_code' => $value['invite_code']])->field('invite_name')->find();

            if (empty($company)) {
                if (empty($driver_info) && !empty($user_info)) {
                    $bill_all[$key]['invite_name'] = $user['invite_name'];
                } else if (!empty($driver_info) && empty($user_info)) {
                    $bill_all[$key]['invite_name'] = $driver['invite_name'];
                } else {
                    $bill_all[$key]['invite_name'] = $driver['invite_name'];
                }
            } else {
                $bill_all[$key]['invite_name'] = $company['invite_name'];
            }

        }
        return array($bill_all, $fpage);
    }
}

