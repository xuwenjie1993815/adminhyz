<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/10
 * Time: 14:56
 */

namespace Home\Logic;

use Home\Model\SetModel;
use Think\Exception;
use Base\Logic\PubLogic;
use Home\Logic\InviteLogic;

class UserLogic
{
    /**
     * 添加乘客用户
     * @param $mobile
     * @param $pwd
     * @return mixed
     * @throws Exception
     */
    static public function addUserNew($invite_name, $cart_id, $mobile, $pwd, $invite_code = '') {
        $data['mobile'] = $mobile;
        $data['pwd'] = $pwd;
        $data['invite_name'] = $invite_name;
        $data['nick_name'] = $invite_name;
        $data['cart_id'] = $cart_id;

        file_put_contents('user.log', var_export($invite_code, true) . '--' . '邀请码-' . "\r\n", FILE_APPEND);
        if (!empty($invite_code)) {
            $data['is_invite'] = 1;
            $data['invite_type'] = substr($invite_code, -1);
            $data['invite_from_code'] = $invite_code;
            switch ($data['invite_type']) {
                case 1://司机
                    $company_id = M('driver')->alias('d')->join('__DRIVER_INFO__ as di on d.id=di.id')->where(['d.invite_code' => $invite_code])->getField('di.company_id');

                    break;
                case 2://乘客

                    $company_id = M('user')->where(['invite_code' => $invite_code])->getField('company_id');
                    break;
                case 3://公司
                    $company_id = M('company')->where(['invite_code' => $invite_code])->getField('id');
                    break;
                default:
                    $data['company_id'] = '';
                    break;
            }
            file_put_contents('user.log', var_export($data, true) . '--' . '添加数据-' . "\r\n", FILE_APPEND);
            $data['company_id'] = $company_id;
        }

        $user_model = new \Home\Model\UserModel();

          $set_model = new SetModel();
          $result = $set_model->find();
          $data['integral'] = $result['z_integral'];


        if (!$user_model->create($data))
            throw new Exception($user_model->getError());

        $user_id = $user_model->add();

        file_put_contents('user.log', var_export($user_id, true) . '--' . '用户id-' . "\r\n", FILE_APPEND);
        // 生成自身邀请码
//        InviteLogic::createInviteCode($user_id, 2);
        InviteLogic::createInviteCodeNew($user_id, 2);//修改为一个邀请码（去除邀请码中的类型）

        // 验证是否填写邀请码
        //if(!empty($invite_code)) InviteLogic::createInviteRelation($user_id, 2, $invite_code);

        return $user_id;
    }
    /**
     * 添加乘客用户
     * @param $mobile
     * @param $pwd
     * @return mixed
     * @throws Exception
     */
    static public function addUser($invite_name, $cart_id, $mobile, $pwd, $invite_code = '') {
        $data['mobile'] = $mobile;
        $data['pwd'] = $pwd;
        $data['invite_name'] = $invite_name;
        $data['nick_name'] = $invite_name;
        $data['cart_id'] = $cart_id;

        //file_put_contents('user.log', var_export($invite_code, true) . '--' . '邀请码-' . "\r\n", FILE_APPEND);

        $driver_model = new \Home\Model\DriverModel();
        $user_model = new \Home\Model\UserModel();
        
        
        if ($driver_model->where(['mobile' => $mobile])->find() || $user_model->where(['mobile' => $mobile])->find() || M('company')->where(['link_phone' => $mobile])->find())
            throw new Exception('账号已存在，请登录');

        if (!empty($invite_code)) {
            $data['is_invite'] = 1;
            $data['invite_from_code'] = $invite_code;

            $driver_invite = $driver_model->where(['invite_code' => $invite_code])->find();
            $user_invite = $user_model->where(['invite_code' => $invite_code])->find();
			$company_invite = M('company')->where(['invite_code' => $invite_code])->find();

			
			//分销公司
            if (empty($company_invite)) {
                if (empty($driver_invite) && empty($user_invite)) {
                    throw new Exception('邀请信息异常');
                } else if (empty($driver_invite) && !empty($user_invite)) {//邀请人是用户
                    $company_id = $user_model->where(['invite_code' => $invite_code])->getField('company_id');
                } else {//邀请人是用户
                    $company_id = $driver_model->where(['invite_code' => $invite_code])->getField('company as company_id');
                }
            } else {
                $company_id = $company_invite['id'];
            }
			

            //file_put_contents('user.log', var_export($data, true) . '--' . '添加数据-' . "\r\n", FILE_APPEND);
            $data['company_id'] = $company_id;
        }
        if (!$user_model->create($data))
            throw new Exception($user_model->getError());

        $user_id = $user_model->add();

        //file_put_contents('user.log', var_export($user_id, true) . '--' . '用户id-' . "\r\n", FILE_APPEND);
        // 生成自身邀请码
//        InviteLogic::createInviteCode($user_id, 2);
        $code = InviteLogic::createInviteCodeNew($user_id, 2); //修改为一个邀请码（去除邀请码中的类型）
		//file_put_contents('user.log', var_export($invite_code, true) . '--' . '邀请码222-' . "\r\n", FILE_APPEND);
        InviteLogic::createInviteRelationNew($invite_code,$code);
        
        // 验证是否填写邀请码
        //if(!empty($invite_code)) InviteLogic::createInviteRelation($user_id, 2, $invite_code);

        return $user_id;
    }


    /**
     * 乘客登录逻辑
     * @param $type 1：密码登录，2：验证码登录
     * @param $mobile 手机号码
     * @param $check_code 密码或验证码
     * @param $type 1：发送司机，2：发送乘客
     * @return bool
     * @throws Exception
     */
    static public function login($type, $mobile, $check_code, $registration_id) {
        $user_model = new \Home\Model\UserModel();
        $driver_model = new \Home\Model\DriverModel();
        $driver_info_model = new \Home\Model\DriverinfoModel();

        //1. 验证手机号
        $info = $user_model->where(array('mobile' => $mobile))->field('id, pwd, status')->find();
        $driver_info = $driver_model->where(['mobile' => $mobile])->field('company as company_id'
                        . ',invite_code,password as pwd,mobile,status,cart_id,invite_name,'
                        . 'is_invite,qrcode_url,qrcode_address,alipay,income,spend,invite_from_code')->find();

        if (empty($info) && empty($driver_info))
            throw new Exception('用户不存在，请先注册');

        //添加乘客信息
        if ($driver_info && empty($info)) {
            $driver_info['nick_name'] = $driver_info['invite_name'];
            $driver_info['create_time'] = time();
            
            file_put_contents('userlogin.log', var_export($driver_info, true) . '--' . '乘客添加信息-' . "\r\n", FILE_APPEND);
            
            if(!empty($info))throw new Exception('该手机已经被注册!');
            $user_id = M('user')->add($driver_info);    
            
            if (!$user_id)
                throw new Exception('乘客信息添加失败');

            $info = $driver_info;

            $types = 1;
            $info['id'] = $user_id;
        }else {
            $types = 2;
        }

        //2. 验证密码
        switch ($type) {
            case 1:
                if (md5($check_code) != $info['pwd'])
                    throw new Exception('密码错误');
                break;

            case 2:
                PubLogic::checkMobileCode($mobile, $check_code, $types);
                break;

            default:
                throw new Exception('登录类型异常');
        }

        //3. 验证状态
        if (!$info['status'])
            throw new Exception('该账号已被禁用，请联系管理员');

        unset($user_model);

        return $info['id'];
    }


    
    /**
     * 保存登录信息
     * @param $user_id 乘客iD
     * @param $mobile 手机号码
     * @return mixed
     * @throws Exception
     */
    static public function SaveLoginInfo($user_id, $mobile, $registration_id)
    {
        if(empty($registration_id)) throw new Exception('别名信息异常');

        $user_model = new \Home\Model\UserModel();

        $base_data['id'] = $user_id;
        $base_data['login_time'] = NOW_TIME;
        $base_data['token'] = base64_encode($base_data['id'] . ',' . $mobile . ',' . $registration_id);

        if ($user_model->save($base_data) === false) throw new Exception('系统异常');

        unset($user_model);

        return $base_data['token'];
    }



    /**
     * 乘客启用 or 停用
     * @param $driver_id 司机ID
     * @return int
     * @throws Exception
     *
     */
    static public function userUse($user_id)
    {
        $user_model = new \Home\Model\UserModel();

        //1. 验证司机信息
        $user_info = $user_model->field('id, status')->where(array('id'=>$user_id))->find();

        if(empty($user_info)) throw new Exception('乘客信息异常');

        $user_info['status'] = $user_info['status'] ? 0 : 1;

        //2. 修改启用状态
        if($user_model->save($user_info) === false) throw new Exception('系统繁忙');

        return $user_info['status'];
    }
    
    

    /**
     * 获取乘客基本信息
     * @param $user_id 乘客ID
     * @return mixed
     * @throws Exception
     */
    static public function getUserInfo($user_id)
    {
        $user_model = new \Home\Model\UserModel();

        $info = $user_model->field('mobile, token, nick_name, head_pic, login_time, level, integral,invite_name')->find($user_id);
        
        if(empty($info)) throw new Exception('用户信息异常');
        
        $info['head_pic'] = $info['head_pic'] ? getPicUrl($info['head_pic']) : getPicUrl('./Public/default/images/ic_default_avator.png');
        $info['login_time'] = $info['login_time'] ? date('Y-m-d H:i:s', $info['login_time']) : '-';
        $info['level'] = intval($info['level']);
        $info['integral'] = intval($info['integral']);

        return $info;
    }


    
    /**
     * 修改乘客信息（单一）
     * @param $user_id 乘客ID
     * @param $key 修改字段名
     * @param $val 修改值
     * @return bool
     * @throws Exception
     */
    static public function updateField($user_id, $key, $val)
    {
        $user_model = new \Home\Model\UserModel();

        if (!in_array($key, $user_model->updateFields())) throw new Exception('修改字段异常');

        if ($key == 'nick_name' && $val == '') throw new Exception('名字不能为空');
        if ($key == 'pwd')
        {
            if(empty($val)) throw new Exception('密码不能为空');
            
            $val = md5($val);
        }

        if ($key == 'head_pic') $val = PubLogic::base64_upload($val, '头像');

        $data['id'] = $user_id;
        $data[$key] = $val;

        if ($user_model->save($data) === false) throw new Exception('系统繁忙');

        return true;
    }
	/**
     * 修改手机
     * @param $user_id 乘客ID
     * @param $mobile 手机号码
     * @return bool
     * @throws Exception
     */
    static public function updateMobile($user_id, $mobile,$pwd) {
        $user_model = M('user');

        $data['id'] = $user_id;
        $data['mobile'] = $mobile;
        if (!preg_match("/^1[3|4|5|8|7][0-9]\d{4,8}$/", $data['mobile'])) {
            throw new Exception('手机号码错误！');
        }
        if($user_model->where(['mobile'=>$data['mobile']])->find()){
            throw new Exception('该手机已经被注册！');
        }
		if($user_model->where(['id'=>$data['id']])->getField('pwd') != md5($pwd)){
            throw new Exception('密码错误');
        }
        //1. 修改手机信息

        if ($user_model->where(['id'=>$data['id']])->save(['mobile'=>$data['mobile']]) === false)
            throw new Exception('系统繁忙');

        //2. 删除登录信息
        $user_model->where(array('id' => $driver_id))->setField('token', '');

        unset($user_model);

        return true;
    }
}