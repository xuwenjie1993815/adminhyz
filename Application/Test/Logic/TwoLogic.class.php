<?php

namespace Test\Logic;

use Home\Model\SetModel;
use Think\Exception;
use Base\Logic\PubLogic;
use Home\Logic\InviteLogic;

class TwoLogic {

    public static function DriverInfo() {
        $driver_model = new \Home\Model\DriverModel();

        $driver_info = $driver_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,income,spend,id,qrcode_url,qrcode_address')->select();
        foreach ($driver_info as $key => $value) {
            //司机信息有邀请人
            if ($value['is_invite'] == 1) {
                $invite = [];
                //司机一级分销人信息
                $first = LastLogic::relation($value['invite_type'], $value['invite_from_code']);
                $invite['invite_code_first'] = $first['invite_code'];
                $invite['invite_type_first'] = $first['type'];

                //司机二级分销人信息
                if ($first['is_invite']) {
                    $two = LastLogic::relation($first['invite_type'], $first['invite_from_code']);
                    $invite['invite_code_two'] = $two['invite_code'];
                    $invite['invite_type_two'] = $two['type'];
                }
                $invite['beinvite_code'] = $value['invite_code'];
                $invite['beinvite_type'] = 1;

                if (!empty($invite['invite_code_first'])) {
                    $invite['create_time'] = time();
                    if (M('invite')->add($invite))
                        ;
                }
            }
        }
        return TRUE;
    }

    public static function UserInfo() {
        $user_model = new \Home\Model\UserModel();
        $driver_model = new \Home\Model\DriverModel();

        $user_info = $user_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,income,spend,id,qrcode_url,qrcode_address')->select();
        foreach ($user_info as $key => $value) {
            //乘客一级分销
            $first = LastLogic::relation($value['invite_type'], $value['invite_from_code']);
            //乘客二级分销
            if ($first['is_invite']) {
                $two = LastLogic::relation($first['invite_type'], $first['invite_from_code']);
            }
            //乘客信息有邀请人
            if ($value['is_invite'] == 1) {
                $invite = [];
                if(!empty($first['invite_code'])){
                    
                    $invite['invite_code_first'] = $first['invite_code'];
                    $invite['invite_type_first'] = $first['type'];
                    $invite['invite_code_two'] = $two['invite_code'];
                    $invite['invite_type_two'] = $two['type'];
                    $invite['create_time'] = time();
                    //司机一级分销人信息
                    $driver_info = $driver_model->where(['mobile' => $value['mobile']])->find();

                    if ($driver_info) {
                        if ($driver_info['is_invite'] == 1) {//司机被邀请
//                        $invite_driver = M('invite')->where(['beinvite_code' => $driver_info['invite_code']])->find();
                        } else {//司机信息未被邀请
                            $invite['beinvite_code'] = $driver_info['invite_code'];
                            $invite['beinvite_type'] = 4;
                            M('invite')->add($invite);
                        }
                    } else {
                        $invite['beinvite_code'] = $value['invite_code'];
                        $invite['beinvite_type'] = 2;
                        M('invite')->add($invite);
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * 处理数据之前司机老数据分销关系
     * @desc 默认接口服务
     * @return string title 标题
     * @author string content 内容
     */
    static public function getDrriverInviteList() {
        $driver_model = new \Home\Model\DriverModel();
//        $user_model = new \Home\Model\UserModel();

        $driver_info = $driver_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,income,spend,id,qrcode_url,qrcode_address')->select();

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
//            $user_id = M('user')->where(['mobile' => $value['mobile']])->getField('id');
//            if ($user_id) {
//                $save['invite_code'] = $value['invite_code'];
//                $save['income'] = $value['income'];
//                $save['spend'] = $value['spend'];
//                $save['qrcode_url'] = $value['qrcode_url'];
//                $save['qrcode_address'] = $value['qrcode_address'];
//                if (!M('user')->where(['mobile' => $value['mobile']])->save($save)) {
//                    return ['code' => 2, 'msg' => '编辑乘客信息失败'];
//                }
//            }
            //司机信息有邀请人
            if ($value['is_invite'] == 1) {
                $invite = [];
                //司机一级分销人信息
                $first = LastLogic::relation($value['invite_type'], $value['invite_from_code']);
                $invite['invite_code_first'] = $first['invite_code'];
                $invite['invite_type_first'] = $first['type'];

                //司机二级分销人信息
                if ($first['is_invite']) {
                    $two = LastLogic::relation($first['invite_type'], $first['invite_from_code']);
                    $invite['invite_code_two'] = $two['invite_code'];
                    $invite['invite_type_two'] = $two['type'];
                }
                $invite['beinvite_code'] = $value['invite_code'];
                $invite['beinvite_type'] = 1;

                if (!empty($invite['invite_code_first'])) {
//                    if (M('invite')->where(['beinvite_code' => $value['invite_code']])->find()) {
//                        $invite['update_time'] = time();
//                        if (!M('invite')->where(['beinvite_code' => $value['invite_code']])->save($invite))
//                            return ['code' => 2, 'msg' => '编辑邀请关系失败'];
//                    }else {
                    $invite['create_time'] = time();
                    if (M('invite')->add($invite))
                        ;
//                            return ['code' => 2, 'msg' => '添加邀请关系失败'];
//                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * 处理数据之前乘客老数据分销关系
     * @desc 默认接口服务
     * @return string title 标题
     * @author string content 内容
     */
    static public function getUserInviteList() {
        $user_model = new \Home\Model\UserModel();

        $user_info = $user_model->field('mobile,is_invite,invite_type,invite_from_code,invite_code,id')->select();
//        foreach ($user_info as $uk => $uv) {
//            $bill = M('bill')->where(['usertypes' => 2, 'userid' => $uv['id']])->select();
//            if ($bill) {
//                foreach ($bill as $kb => $vb) {
//                    if(empty($vb['invite_code'])) {
//                        $update['invite_code'] = $uv['invite_code'];
//                        if (!M('bill')->where(['usertypes' => 2, 'userid' => $uv['id']])->save($update)) {
//                            return ['code' => 2, 'msg' => '编辑流水信息失败'];
//                        }
//                    }
//                }
//            }
//        }

        foreach ($user_info as $key => $value) {
            if ($value['is_invite'] == 1) {
                $invite = [];
                //乘客一级分销人信息
                $first = LastLogic::relation($value['invite_type'], $value['invite_from_code']);
                $invite['invite_code_first'] = $first['invite_code'];
                $invite['invite_type_first'] = $first['type'];

                //乘客二级分销人信息
                if ($first['is_invite']) {
                    $two = LastLogic::relation($first['invite_type'], $first['invite_from_code']);
                    $invite['invite_code_two'] = $two['invite_code'];
                    $invite['invite_type_two'] = $two['type'];
                }
                $invite['beinvite_code'] = $value['invite_code'];
                $invite['beinvite_type'] = 2;

                if (!empty($invite['invite_code_first'])) {
                    if (M('invite')->where(['beinvite_code' => $value['invite_code']])->find()) {
                        $invite['update_time'] = time();
                        if (!M('invite')->where(['beinvite_code' => $value['invite_code']])->save($invite))
                            return ['code' => 2, 'msg' => '编辑邀请关系失败'];
                    }else {
                        $invite['create_time'] = time();
                        if (!M('invite')->add($invite))
                            return ['code' => 2, 'msg' => '添加邀请关系失败'];
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * 获取分销关系
     * @$invite_type 邀请人类型
     * @$invite_from_code 邀请人邀请码
     */
    static public function relation($invite_type, $invite_from_code) {

//        switch ($invite_type) {
//            case 1://司机
        $invite_driver = M('driver')->where(['invite_code' => $invite_from_code])->field('is_invite,invite_type,invite_from_code,invite_code')->find();
//                break;
//            case 2://乘客
        $invite_user = M('user')->where(['invite_code' => $invite_from_code])->field('is_invite,invite_type,invite_from_code,invite_code')->find();
//                break;
//            case 3://公司
        $invite_company = M('company')->where(['invite_code' => $invite_from_code])->find();


        if (!empty($invite_driver) && empty($invite_user) && empty($invite_company)) {
            $invite = $invite_driver;
            $invite['type'] = 1;
        } else if (empty($invite_driver) && !empty($invite_user) && empty($invite_company)) {
            $invite = $invite_user;
            $invite['type'] = 2;
        } else if (empty($invite_driver) && empty($invite_user) && !empty($invite_company)) {
            $invite = $invite_company;
            $invite['type'] = 3;
        } else if (!empty($invite_driver) && !empty($invite_user) && empty($invite_company)) {
            $invite = $invite_driver;
            $invite['type'] = 4;
        } else {
            $invite = [];
        }
//                break;
//
//            default:
//                throw new Exception('分销类型异常');
//                break;
        return $invite;
    }

    /**
     * 获取乘客/司机信息
     * @desc 默认接口服务
     * @return string title 标题
     * @author string content 内容
     */
    static public function getInfo($type, $invite_code) {
        $invite_driver = M('driver')->where(['invite_code' => $invite_code])->field('mobile,id')->find();
//                break;
//            case 2://乘客
        $invite_user = M('user')->where(['invite_code' => $invite_code])->field('mobile,id')->find();
//                break;
//            case 3://公司
        $invite_company = M('company')->where(['invite_code' => $invite_code])->field('link_phone as mobile,id')->find();

        if (!empty($invite_driver) && empty($invite_user) && empty($invite_company)) {
            $invite = $invite_driver;
            $invite['type'] = 1;
        } else if (empty($invite_driver) && !empty($invite_user) && empty($invite_company)) {
            $invite = $invite_user;
            $invite['type'] = 2;
        } else if (empty($invite_driver) && empty($invite_user) && !empty($invite_company)) {
            $invite = $invite_company;
            $invite['type'] = 3;
        } else if (!empty($invite_driver) && !empty($invite_user) && empty($invite_company)) {
            $invite = $invite_driver;
            $invite['type'] = 4;
        } else {
            throw new Exception('分销类型异常');
        }
//        switch ($type) {
//            case 1://司机
//                $invite = M('driver')->where(['invite_code' => $invite_code])->field('mobile,id')->find();
//                $invite['type'] = 1;
//                break;
//            case 2://乘客
//                $invite = M('user')->where(['invite_code' => $invite_code])->field('mobile,id')->find();
//                $invite['type'] = 2;
//                break;
//            case 3://公司
//                $invite = M('company')->where(['invite_code' => $invite_code])->field('link_phone as mobile,id')->find();
//                $invite['type'] = 3;
//                break;
//
//            default:
//                throw new Exception('分销类型异常');
//                break;
//        }
        return $invite;
    }

    /**
     * 司机与一级乘客邀请人是同一信息
     * @$beinvite_code 被邀请人邀请码
     * @$invite_code_first 一级邀请人
     * @$type 邀请人类型，2：乘客
     * @$invite_code_two 二级邀请人邀请码
     * @$invite_type_two 二级邀请人类型
     * @$id invite 表id值
     */
    static public function firstCommon($beinvite_code, $invite_code_first, $invite_code_two, $invite_type_two, $id, $type) {
        if ($type == 2) {
            $invite = M('invite')->where(['beinvite_code' => $invite_code_two, 'beinvite_type' => $invite_type_two])->field('invite_code_first,invite_type_first')->find();
            if (!empty($invite)) {

                $update['invite_code_first'] = $invite_code_two;
                $update['invite_type_first'] = $invite_type_two;
                $update['invite_code_two'] = $invite['invite_code_first'];
                $update['invite_type_two'] = $invite['invite_type_first'];
                $update['update_time'] = time();
                if (!M('invite')->where(['id' => $id])->save($update)) {
                    throw new Exception('编辑邀请信息失败');
                }
            }
        } else {
            throw new Exception('邀请人类型异常');
        }
        return true;
    }

}
