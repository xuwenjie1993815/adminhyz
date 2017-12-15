<?php

namespace Test\Controller;

use Base\Controller\BaseController;
use Base\Logic\PubLogic;
use Home\Logic\AdvertLogic;
use Test\Logic\LastLogic;
use Think\Exception;

/**
 * Description of LastController
 * 处理数据之前老数据
 * @author 姣姣
 */
class TwoController extends BaseController {
	public function driverbf() {
        $info = M('driver_copy_bf')->where(['id'=>['GT',1245]])->select();
        foreach ($info as $key => $value) {
            M('driver')->add($value);
        }
        
    }
    public function userbf() {
        $info = M('user_copy_bf')->where(['id'=>['GT',1245]])->select();
        foreach ($info as $key => $value) {
            M('user')->add($value);
        }
        
    }
    public function driverinfobf() {
        $info = M('driver_info_copy_bf')->where(['id'=>['GT',1245]])->select();
        foreach ($info as $key => $value) {
            M('driver_info')->add($value);
        }
        
    }
    public function driver() {
        $info = M('driver_copy')->where(['id'=>['GT',1245]])->select();
        foreach ($info as $key => $value) {
            M('driver_copy_bf')->add($value);
        }
        
    }
    public function driverInfos() {
        $info = M('driver_info_copy')->where(['id'=>['GT',1245]])->select();
        foreach ($info as $key => $value) {
            M('driver_info_copy_bf')->add($value);
        }
        
    }
    public function user() {
        $info = M('user_copy')->where(['id'=>['GT',1165]])->select();
        foreach ($info as $key => $value) {
            M('user_copy_bf')->add($value);
        }
        
    }
    public function driverbill() {
        $driver_model = new \Home\Model\DriverModel();
        
        $driver_info = $driver_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,income,spend,id,qrcode_url,qrcode_address')->select();

        foreach ($driver_info as $kb => $vb) {
            $bill = M('bill')->where(['usertypes' => 1, 'userid' => $vb['id']])->find();
            if ($bill) {
                $update['invite_code'] = $vb['invite_code'];
                if (!M('bill')->where(['usertypes' => 1, 'userid' => $vb['id']])->save($update)) {
                    return ['code' => 2, 'msg' => '编辑流水信息失败'];
                }
            }
        }
        
    }
    public function userbill() {
        $user_model = new \Home\Model\UserModel();
        $user_info = $user_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,id')->select();
        foreach ($user_info as $kb => $vb) {
            $bill = M('bill')->where(['usertypes' => 2, 'userid' => $vb['id']])->find();
			
            if ($bill) {
                $update['invite_code'] = $vb['invite_code'];
                if (M('bill')->where(['usertypes' => 2, 'userid' => $vb['id']])->save($update)) {
                    //return ['code' => 2, 'msg' => '编辑流水信息失败'];
                }
            }
        }
    }
    
    
    /**
     * 乘客
     * @desc 默认接口服务
     * @return string title 标题
     * @author string content 内容
     */
    public function UserInfo() {
        \Test\Logic\TwoLogic::UserInfo();
    }
    /**
     * 司机
     * @desc 默认接口服务
     * @return string title 标题
     * @author string content 内容
     */
    public function DriverInfo() {
        \Test\Logic\TwoLogic::DriverInfo();
    }
    
    public function test() {
        $driver_model = new \Home\Model\DriverModel();
        // $user_model = new \Home\Model\UserModel();

        $driver_info = $driver_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,income,spend,id,qrcode_url,qrcode_address')->select();


        foreach ($driver_info as $key => $value) {
            $user_id = M('user')->where(['mobile' => $value['mobile']])->getField('id');

            if ($user_id) {
                $save['invite_code'] = $value['invite_code'];
                $save['income'] = $value['income'];
                $save['spend'] = $value['spend'];
                $save['qrcode_url'] = $value['qrcode_url'];
                $save['qrcode_address'] = $value['qrcode_address'];
                ////dump($save);
                if (!M('user')->where(['mobile' => $value['mobile']])->save($save)) {
                    return ['code' => 2, 'msg' => '编辑乘客信息失败'];
                }
            }
        }
    }

    public function editUser() {
        $driver_info = M('driver')->field('password,mobile,is_invite,invite_type,invite_from_code,invite_code,income,spend,id,qrcode_url,qrcode_address')->select();

//        foreach ($driver_info as $kb => $vb) {
//            $bill = M('bill')->where(['usertypes' => 1, 'userid' => $vb['id']])->find();
//            if ($bill) {
//                $update['invite_code'] = $vb['invite_code'];
//                if (!M('bill')->where(['usertypes' => 1, 'userid' => $vb['id']])->save($update)) {
//                    return ['code' => 2, 'msg' => '编辑流水信息失败'];
//                }
//            }
//        }
        foreach ($driver_info as $key => $value) {
            $user_id = M('user')->where(['mobile' => $value['mobile']])->getField('id');
            if ($user_id) {
                $save['invite_code'] = $value['invite_code'];
                $save['income'] = $value['income'];
                $save['spend'] = $value['spend'];
                $save['qrcode_url'] = $value['qrcode_url'];
                $save['qrcode_address'] = $value['qrcode_address'];
                $save['pwd'] = $value['password'];
                if (M('user')->where(['id' => $user_id])->save($save)) {
//                    return ['code' => 2, 'msg' => '编辑乘客信息失败'];
                }
            }
        }
    }

    public function addInvite() {
        //获取司机分销关系并添加
        $driver = LastLogic::getDrriverInviteList();
        //获取乘客分销关系并添加
        $user = LastLogic::getUserInviteList();
    }

    /**
     * 读取之前数据的分销关系
     * @desc 默认接口服务
     * @return string title 标题
     * @author string content 内容
     */
    public function getDriverInvite() {
        $model = M();
        $model->startTrans();
        //获取司机分销关系并添加
        $driver = LastLogic::getDrriverInviteList();

        //获取乘客分销关系并添加
        $user = LastLogic::getUserInviteList();
        //获取所有分销关系
        $invite = M('invite')->field('invite_code_first,invite_type_first,invite_code_two,invite_type_two,beinvite_code,beinvite_type,id')->select();

        foreach ($invite as $key => $value) {
            //获取被邀请人信息
            $beinvite_info = LastLogic::getInfo($value['beinvite_type'], $value['beinvite_code']);

            //一级邀请人信息
            $first_info = LastLogic::getInfo($value['invite_type_first'], $value['invite_code_first']);

            //二级邀请人信息
            if (!empty($value['invite_code_two'])) {
                $two_info = LastLogic::getInfo($value['invite_type_two'], $value['invite_code_two']);
            }

            //被邀请人与一级邀请人是同一人信息
            if ($value['invite_type_first'] != 3) {
                if (!empty($two_info)) {//二级邀请人存在
                    if ($first_info['mobile'] == $beinvite_info['mobile']) {//一级与被邀请人为同一个的乘客与司机信息
                        if ($value['invite_type_first'] == 1) {//一级推荐人是司机
                            ////dump(['111']);
                            //dump($value['id']);
                            //dump($value['beinvite_code']);
                            //dump($value['beinvite_type']);
                            M('invite')->where(['id' => $value['id']])->delete();
                        } else if ($value['invite_type_first'] == 2) {//一级推荐人是乘客
                            //dump(['222']);
                            //dump($value['id']);
                            //dump($value['beinvite_code']);
                            //dump($value['beinvite_type']);
                            if (!LastLogic::firstCommon($value['beinvite_code'], $value['invite_code_first'], $value['invite_code_two'], $value['invite_type_two'], $value['id'], 2)) {
                                $model->rollback();
                                //dump(['一级与被邀请人为同一个的乘客与司机信息时，编辑信息失败']);
                                exit;
                            }
                        }
                    }
                } else {
                    if ($first_info['mobile'] == $beinvite_info['mobile']) {//被邀请人与一级邀请人是同一人信息
                        //dump(['333']);
                        //dump($value['beinvite_code']);
                        //dump($value['beinvite_type']);
                        if (!M('invite')->where(['id' => $value['id']])->delete()) {
                            //dump(['一级与被邀请人为同一个的乘客与司机信息时，删除信息失败']);
                            exit;
                            $model->rollback();
                        }
                    }
                }
            }
        }
        $model->commit();
    }

}
