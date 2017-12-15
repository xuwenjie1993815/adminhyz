<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/4
 * Time: 14:02
 */

namespace Home\Logic;

use Think\Exception;

use Base\Logic\PubLogic;

use Home\Logic\InviteLogic;

class DriverLogic
{

    /**
     * 验证司机注册状态
     * @param $mobile
     * @return bool
     * @throws Exception
     */
    static public function checkRegStatus($mobile)
    {
        $driver_model = new \Home\Model\DriverModel();

        $info = $driver_model->where(array('mobile' => $mobile))->field('id, is_audit')->find();

        unset($driver_model);

        // 该手机尚未注册
        if (empty($info)) return 0;

        switch ($info['is_audit']) {
            // 审核中 or 审核成功
            case 0:
            case 1:
                throw new Exception('该手机已经注册');

            // 审核失败，从新注册
            case -1:
                return $info['id'];

            default:
                throw new Exception('注册状态异常');
        }

    }


    /**
     * 添加司机
     * @param $data
     * @return mixed
     * @throws Exception
     */
    static public function addDriver($data)
    {
        $base_data['mobile'] = $data['mobile']; //手机号码
        $base_data['password'] = $data['password'];
        $invite_code = $base_data['invite_from_code'] = $data['invite_code'] ? : '';
        $base_data['cart_id'] = $data['cart_id'];
        $base_data['invite_name'] = $data['inviteName'];

        //1. 添加司机登录信息
        $driver_model = new \Home\Model\DriverModel();

        if (!$driver_model->create($base_data))
            throw new Exception($driver_model->getError());

        $driver_id = $info_data['id'] = $driver_model->add();

        //2. 上传图片(驾照图片，身份证图片，汽车图片)
        $info_data['license_pic_zm'] = PubLogic::base64_upload($data['license_pic_zm'], '驾驶证正面');
        $info_data['license_pic_fm'] = PubLogic::base64_upload($data['license_pic_fm'], '驾驶证反面');

        $info_data['xsz_zm'] = PubLogic::base64_upload($data['xsz_zm'], '行驶证正面');
        $info_data['xsz_fm'] = PubLogic::base64_upload($data['xsz_fm'], '行驶证反面');
        //3. 司机详细信息入库
        $info_data['type_id'] = $data['driver_car_id'];
        $info_data['driver_id'] = $data['driver_id'];
        $info_data['colour_id'] = $data['colour_id'];
        $info_data['fdj_num'] = $data['fdj_num'];
        $info_data['car_id'] = $data['ar_id'];
        $info_data['company_id'] = $data['company_id'];
        $info_data['license_sn'] = $data['license_sn']; //车牌号码
        $info_data['cart_id'] = $data['cart_id'];
        $info_data['car_load_num'] = $data['car_load_num'];
        $info_data['car_reg_time'] = strtotime($data['car_reg_time']);
        $info_data['car_engine'] = $data['car_engine'];
        $info_data['car_price'] = floatval($data['car_price']);
        $info_data['type'] = $data['type'];
        $info_data['true_name'] = $data['inviteName'];

        file_put_contents('driver.log', var_export($info_data, true) . '---driver-' . "\r\n", FILE_APPEND);

        $driver_info_model = new \Home\Model\DriverinfoModel();

        file_put_contents('driver.log', var_export(M('driver_info')->where(array('license_sn' => $info_data['license_sn']))->count(), true) . '--' . '司机上限-' . "\r\n", FILE_APPEND);
        if (M('driver_info')->where(array('license_sn' => $info_data['license_sn']))->count() > 1)
            throw new Exception('车辆注册司机已达上限');

        if (!$driver_info_model->create($info_data, 1))
            throw new Exception($driver_info_model->getError());

        if (!$driver_info_model->add())
            throw new Exception('系统繁忙');

        //4. 生成司机邀请码
//        InviteLogic::createInviteCode($driver_id, 1);
        $code = InviteLogic::createInviteCodeNew($driver_id, 1);

        //5. 验证是否填写邀请码
        if (!empty($invite_code))
            InviteLogic::createInviteRelationNew($invite_code,$code);
//            InviteLogic::createInviteRelation($driver_id, 1, $invite_code);

        unset($driver_info_model, $driver_model, $base_data);

        return $driver_id;
    }


    /**
     * 邀请页面注册司机（基本信息）
     * @param $mobile
     * @param $password
     * @param string $invite_code
     * @return mixed
     * @throws Exception
     */
    static public function inviteAddDriver($mobile, $password, $invite_code='',$cart_id,$invite_name)
    {
        $data['invite_name'] = $invite_name;
        $data['mobile'] = $mobile;
        $data['password'] = $password;
        $data['cart_id'] = $cart_id;

        $driver_model = new \Home\Model\DriverModel();
        $user_model = new \Home\Model\UserModel();

        //1. 验证该手机是否被注册
        $user = $user_model->where(['mobile' => $mobile])->find();
        $driver = $driver_model->where(['mobile' => $mobile])->find();
        if ($driver || $user) throw new Exception('该手机已经注册');
        
        if (!empty($invite_code)) {
            $data['is_invite'] = 1;
            $data['invite_from_code'] = $invite_code;

            $driver_invite = $driver_model->where(['invite_code' => $invite_code])->find();
            $user_invite = $user_model->where(['invite_code' => $invite_code])->find();

            //分销公司
            if (empty($driver_invite) && empty($user_invite)) {
                throw new Exception('邀请信息异常');
            } else if (empty($driver_invite) && !empty($user_invite)) {//邀请人是用户
                $company_id = $user_model->where(['invite_code' => $invite_code])->getField('company_id');
            } else {//邀请人是用户
                $company_id = $driver_model->where(['invite_code' => $invite_code])->getField('company as company_id');
            }

            file_put_contents('driver.log', var_export($data, true) . '--' . '添加数据-' . "\r\n", FILE_APPEND);
            $data['company_id'] = $company_id;
        }
        
        //2. 添加司机基本信息
        if (!$driver_model->create($data))
            throw new Exception($dirvier_model->getError());

        $driver_id = $driver_model->add();

        if (!$driver_id)
            throw new Exception('系统繁忙');

        //3. 生成自身邀请码:
//        InviteLogic::createInviteCode($driver_id, 1);
        $code = InviteLogic::createInviteCodeNew($driver_id, 1); //修改为一个邀请码（去除邀请码中的类型）
        InviteLogic::createInviteRelationNew($invite_code,$code);
        //4. 创建邀请关系
        //if(!empty($invite_code)) InviteLogic::createInviteRelation($driver_id, 1, $invite_code);

        return $driver_id;
    }


    /**
     * 完善司机详细信息（待完善司机）
     * @param $data
     * @return bool
     * @throws Exception
     */
    static public function perfectInfo($data)
    {
        $driver_model = new \Home\Model\DriverModel();
        $driver_info_model = new \Home\Model\DriverinfoModel();

        $mobile = $data['mobile']; unset($data['mobile']);

        //1. 验证该手机号码注册司机是否存在
        $driver = $driver_model->where(array('mobile'=>$mobile))->field('id,is_audit')->find();
        $data['id'] =  $driver['id'];
        $driver_id = $driver['id'];
        
        file_put_contents('perfectInfo.log', var_export($driver,true) . '--' .'司机完善资料-司机信息-'."\r\n", FILE_APPEND);
        
        if(!$driver) throw new Exception('司机信息异常');
        
        $driver_info = $driver_info_model->where(array('id'=>$driver_id))->count();
        
        //2. 验证该是否还未完善信息
//        if($driver['is_audit'] == -1){
//            
//        }else if($driver['is_audit'] == 1){
//            if($driver_info_model->where(array('id'=>$driver_id))->count()) throw new Exception('该用户已完善详细信息');
//        }

        //3. 添加基本信息

        // 上传图片(驾照图片，身份证图片，汽车图片)
        $data['license_pic_zm'] = PubLogic::base64_upload($data['license_pic_zm'], '驾驶证正面');
        $data['license_pic_fm'] = PubLogic::base64_upload($data['license_pic_fm'], '驾驶证反面');


        $data['xsz_zm'] = PubLogic::base64_upload($data['xsz_zm'], '行驶证正面');
        $data['xsz_fm'] = PubLogic::base64_upload($data['xsz_fm'], '行驶证反面');


        $data['car_reg_time'] = strtotime($data['car_reg_time']);
        
        file_put_contents('perfectInfo.log', var_export($data,true) . '--' .'司机完善资料-司机完善信息-'."\r\n", FILE_APPEND);
        file_put_contents('perfectInfo.log', var_export($driver_info,true) . '--' .'司机完善资料-司机信息和-'."\r\n", FILE_APPEND);
		
		$data['car_id'] = $data['ar_id'];
        $data['type_id'] = $data['driver_car_id'];
		
        //待审核且未完善信息
        if($driver['is_audit'] == 0 && !$driver_info){
            
            if(!$driver_info_model->create($data, 1)) throw new Exception($driver_info_model->getError());
            
            if(!$driver_info_model->add()) throw new Exception('系统异常');
            
        }else if($driver['is_audit'] == -1 && $driver_info){//审核失败
            
            if(!$driver_model->where(['id'=>$driver_id])->setField('is_audit',0)) throw new Exception('系统异常');
            if(!$driver_info_model->where(['id'=>$driver_id])->save($data)) throw new Exception('系统异常');
            
        }else{
            throw new Exception('司机信息异常');
        }
        return $driver_id;
    }



    /**
     * 删除司机信息
     * @param $driver_id 司机ID
     * @return bool
     * @throws Exception
     */
    static public function delDriverInfo($driver_id)
    {
        $driver_info_model = M('driver_info');

        //1. 获取子表信息
        $info = $driver_info_model->field('idcard_pic_zm, idcard_pic_fm, license_pic_zm, license_pic_fm,  head_pic')->find($driver_id);

        if (empty($info)) throw new Exception('司机信息异常');

        // 删除图片
        if (file_exists($info['idcard_pic_zm'])) @unlink($info['idcard_pic_zm']);
        if (file_exists($info['idcard_pic_fm'])) @unlink($info['idcard_pic_fm']);
        if (file_exists($info['license_pic_zm'])) @unlink($info['license_pic_zm']);
        if (file_exists($info['license_pic_fm'])) @unlink($info['license_pic_fm']);

        if (file_exists($info['head_pic'])) @unlink($info['head_pic']);

        //2. 删除子表信息
        if (!$driver_info_model->delete($driver_id)) throw new Exception('系统繁忙');

        //3. 删除主表信息
        if (!M('driver')->delete($driver_id)) throw new Exception('系统繁忙');

        return true;
    }


    /**
     * 登录逻辑
     * @param $type 1：密码登录，2：手机短信验证码登录
     * @param $mobile 手机号码
     * @param $check_code 类型为1：密码明文，类型为2:手机验证码
     * @return mixed
     * @throws Exception
     */
    static public function login($type, $mobile, $check_code, $registration_id) {
        $driver_model = new \Home\Model\DriverModel();
        $user_model = new \Home\Model\UserModel();

        //1. 匹配手机号码查询
        $driver_info = $driver_model->field('id, mobile, password, status, is_audit,audit_because')->where(array('mobile' => $mobile))->find();
        $user_info = $user_model->where(array('mobile' => $mobile))->field('mobile,pwd as password, status,'
                . 'invite_name,cart_id,invite_code,is_invite,income,spend,qrcode_url,qrcode_address,alipay,company_id as company,invite_from_code')->find();

        if (empty($driver_info) && empty($user_info))return ['msg'=>'用户不存在，请先注册','data'=>'']; 
        
        if ($driver_info['is_audit'] == -1) return ['msg'=>']err' . $driver_info['audit_because'],'data'=>'']; //throw new Exception();
        
        if(empty($driver_info) && !empty($user_info)){
            
            $user_info['create_time'] = time();
            
            $driver_id = M('driver')->add($user_info);
            
            if(!$driver_id) return ['msg'=>'司机信息添加失败','data'=>''];
            $types = 2;
            
            $driver_info = $user_info;
            $driver_info['id'] = $driver_id;
        }else{
            $types = 1;
        }
        //2. 验证密码或短信验证码
        switch ($type) {
            // 密码登录
            case 1:
                file_put_contents('driver.log', var_export($check_code, true) . '--' . '司机登录-' . "\r\n", FILE_APPEND);
                if (md5($check_code) != $driver_info['password']) return ['msg'=>'手机或密码错误','data'=>''];

                break;

            // 验证码登录
            case 2:

                PubLogic::checkMobileCode($driver_info['mobile'], $check_code,$types);

                break;

            default:
                 return ['msg'=>'登录类型异常','data'=>''];
        }

        // 验证是否邀请注册，需要完善信息
        if (!M('driver_info')->where(array('id' => $driver_info['id']))->count())
            return ['msg'=>'待完善详细信息','data'=>''];

        //3. 验证审核状态
        switch ($driver_info['is_audit']) {
            case 0:
                return ['msg'=>'系统审核中，请耐心等待','data'=>''];

            case -1:
                return ['msg'=>'err' . $driver_info['audit_because'],'data'=>''];
        }

        //4. 验证禁用状态
        if (!$driver_info['status'])
            return ['msg'=>'账号已被禁用，请联系管理员','data'=>''];

        //5. 修改登录信息
        self::updateLoginState($driver_info['id'], $driver_info['mobile'], $registration_id);
        
        return ['data'=>$driver_info['id']];
    }

    /**
     * @name 修改司机登录信息
     * @param $driver_id
     * @param $mobile
     * @return mixed
     * @throws Exception
     */
    static public function updateLoginState($driver_id, $mobile, $registration_id)
    {
        $driver_model = new \Home\Model\DriverModel();
        
        if(empty($registration_id)) throw new Exception('别名信息异常');

        $base_data['id'] = $driver_id;
        $base_data['login_time'] = NOW_TIME;
        $base_data['token'] = base64_encode($base_data['id'] . ',' . $mobile . ',' . $registration_id );

        if ($driver_model->save($base_data) === false) throw new Exception('系统异常');

        unset($driver_model);

        return $base_data['token'];
    }


    /**
     * @获得司机列表（完整信息）
     * @param type $mobile
     * @param type $size
     * @return type
     */
    static public function getDriveList($data=array(), $size = 20)
    {
        $field = '*';
        
        $mobile = $data['mobile'];
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        $is_audit = $data['is_audit'];
        
        $where['a.is_audit'] = ['neq',0];
        
        if(!empty($is_audit)) $where['a.is_audit'] = $is_audit;
       

        if(!empty($mobile)) $where['a.mobile|c.invite_name|a.invite_name'] = ['like',"%$mobile%"];

        if(empty($start_time) && !empty($end_time)){
            $where['a.create_time'] = ['ELT',  strtotime($end_time)];
        }else if(empty ($end_time) && !empty ($start_time)){
            $where['a.create_time'] = ['EGT',  strtotime($start_time)];
        }else if(!empty ($start_time) && !empty ($end_time)){
            $where['a.create_time'] = ['between',[strtotime($start_time),  strtotime($end_time)]];
        }
        //dump($where);exit;
		
        $model = M('driver');

        $count = $model->alias('a')
            ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
            ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
            ->where($where)
            ->count();

        // 开启分页类
        $page = new \Think\Page($count, $size);

        // 获取分页显示
        $fpage = $count > $size ? $page->Show() : '';

        $list = $model->alias('a')
                ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
            ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
            ->where($where)
            ->field('a.*, b.company_id,b.license_sn,b.carframe_sn,b.driver_sn,b.car_load_num,b.car_reg_time,b.car_engine,b.car_price,b.type,b.idcard_pic_zm,b.idcard_pic_fm,'
                    . 'b.id as infoid,b.license_pic_zm,b.license_pic_fm,b.car_pic,b.true_name,b.head_pic,b.driver_age,b.driver_id,b.fdj_num,b.car_id,b.colour_id,b.type_id,'
                    . 'c.invite_name as company_name,b.xsz_zm,b.xsz_fm')
			->order('a.create_time desc')
            ->limit("{$page->firstRow}, {$page->listRows}")
            ->select();
		switch ($is_audit) {
            case 1:
                $where['a.is_audit'] = 1;
                $countaudit = $model->alias('a')
                        ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
                        ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                        ->where($where)
                        ->count();
                $countno = 0;
                break;
            case -1:
                $where['a.is_audit'] = -1;
                $countno = $model->alias('a')
                        ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
                        ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                        ->where($where)
                        ->count();
                $countaudit = 0;
                break;

            default:
                $where['a.is_audit'] = 1;
                $countaudit = $model->alias('a')
                        ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
                        ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                        ->where($where)
                        ->count();
                $where['a.is_audit'] = -1;
                $countno = $model->alias('a')
                        ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
                        ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
                        ->where($where)
                        ->count();
                break;
        }
        $all['count'] = $count;
        $all['countaudit'] = $countaudit;
        $all['countno'] = $countno;
        return array($list, $fpage,$all);
    }
    
    /**
     * @获得待审核司机信息
     * @param type $mobile
     * @param type $size
     * @return type
     */
    static public function getAuditDriveList($mobile='', $size = 20)
    {
        $field = '*';

        $where = array();

        if (!empty($mobile))
            $where = array("is_audit=0 and a.mobile like '%" . $mobile . "%' or c.invite_name like '%" . $mobile . "%' or b.license_sn like '%" . $mobile . "%'");
        else
            $where = array("is_audit=0");

        $model = M('driver');

        $count = $model->alias('a')
            ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
            ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
            ->where($where)
            ->count();

        // 开启分页类// 导入分页类
        
        $page = new \Org\Util\Page($count,$size);

        // 获取分页显示
        $fpage = $count > $size ? $page->Show() : '';

        $list = $model->alias('a')
            ->join('__DRIVER_INFO__ as b on a.id = b.id', 'INNER')
            ->join('__COMPANY__ as c on b.company_id = c.id', 'LEFT')
            ->where($where)
            ->order('create_time desc')
            ->field('a.*, b.company_id,b.license_sn,b.carframe_sn,b.driver_sn,b.car_load_num,b.car_reg_time,b.car_engine,b.car_price,b.type,b.idcard_pic_zm,b.idcard_pic_fm,'
                    . 'b.id as infoid,b.license_pic_zm,b.license_pic_fm,b.car_pic,b.true_name,b.head_pic,b.driver_age,b.driver_id,b.fdj_num,b.car_id,b.colour_id,b.type_id,'
                    . 'c.invite_name as company_name,b.xsz_zm,b.xsz_fm')
            ->limit("{$page->firstRow}, {$page->listRows}")
            ->select();

        return array($list, $fpage);
    }

    
    /**
     * @已注册但未完善资料
     * @param type $mobile
     * @param type $size
     * @return type
     */
    static public function getRegDriveList($data=array(), $size = 20)
    {
		
        $field = '*';
        
        $mobile = $data['mobile'];
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        
        $where = array();
        
        $where['a.is_audit'] = 0;
        
        if(!empty($mobile)) $where['a.mobile|a.invite_name|a.cart_id'] = ['like',"%$mobile%"];
        
        if(empty($start_time) && !empty($end_time)){
            $where['a.create_time'] = ['ELT',  strtotime($end_time)];
        }else if(empty ($end_time) && !empty ($start_time)){
            $where['a.create_time'] = ['EGT',  strtotime($start_time)];
        }else if(!empty ($start_time) && !empty ($end_time)){
            $where['a.create_time'] = ['between',[strtotime($start_time),  strtotime($end_time)]];
        }
		
        $model = M('driver');

        $count = $model->alias('a')
            ->where($where)
            ->count();

        // 开启分页类// 导入分页类
        
        $page = new \Org\Util\Page($count,$size);

        // 获取分页显示
        $fpage = $count > $size ? $page->Show() : '';

        $list = $model->alias('a')->order('create_time desc')//,b.invite_name as invite_from_code
            ->field('a.id,a.invite_name,a.mobile,a.cart_id,a.create_time,a.invite_type,a.invite_from_code')
			->limit("{$page->firstRow}, {$page->listRows}")
			->where($where)
            ->select();
            //->join('__VIEW_INVITES__ as b on a.invite_type=b.invite_type and a.invite_from_code = b.invite_code','LEFT')
            //->where($where)
            //->order('create_time desc')
            //->field('a.id,a.invite_name,a.mobile,a.cart_id,a.create_time,a.invite_type,a.invite_from_code,b.invite_name as invite_from_code')
            //->limit("{$page->firstRow}, {$page->listRows}")
            //->select();
			

		foreach ($list as $key => $value) {
            switch ($value['invite_type']) {
                case 1://司机
                    $list[$key]['invite_from_code'] = M('driver')->where(['invite_code'=>$value['invite_from_code']])->getField('invite_name');

                    break;
                case 2://乘客
                    $list[$key]['invite_from_code'] = M('user')->where(['invite_code'=>$value['invite_from_code']])->getField('invite_name');
                    break;
                case 3://公司
                    $list[$key]['invite_from_code'] = M('company')->where(['invite_code'=>$value['invite_from_code']])->getField('invite_name');
                    break;
                default:
                    break;
            }
        }
		
        return array($list, $fpage);
    }
    
    /**
     * 获取司机信息
     * @param $driver_id
     * @return mixed
     * @throws Exception
     */
    static public function getInfo($driver_id)
    {
        //1. 获取基本信息
        $base_info = M('driver')->field('invite_code_old,income,spend,mobile, status, is_audit,audit_because, create_time, login_time, token,cart_id,alipay,invite_code,invite_name,invite_type,invite_from_code')->find($driver_id);

        //2. 获取详细信息
        $detail_info = M('driver_info')->find($driver_id);

        //if (empty($base_info) || empty($detail_info)) throw new Exception('司机信息异常');
        

        $detail_info['company_name'] = M('company')->where(array('id'=>$detail_info['company_id']))->getField('invite_name');
        $detail_info['type_name'] = M('type')->where(array('id'=>$detail_info['type_id']))->getField('brand_type');
        $detail_info['car_name'] = M('car')->where(array('id'=>$detail_info['car_id']))->getField('car_type');
        $detail_info['colour_name'] = M('colour')->where(array('id'=>$detail_info['colour_id']))->getField('colours');
        $detail_info['brand_name'] = M('brand')->where(array('id'=>$detail_info['driver_id']))->getField('brand_name');

        $detail_info['xsz_zm'] = $detail_info['xsz_zm'] ? getPicUrl($detail_info['xsz_zm']) : '';
        $detail_info['xsz_fm'] = $detail_info['xsz_fm'] ? getPicUrl($detail_info['xsz_fm']) : '';

        $detail_info['idcard_pic_zm'] = $detail_info['idcard_pic_zm'] ? getPicUrl($detail_info['idcard_pic_zm']) : '';
        $detail_info['idcard_pic_fm'] = $detail_info['idcard_pic_fm'] ? getPicUrl($detail_info['idcard_pic_fm']) : '';
        $detail_info['license_pic_zm'] = $detail_info['license_pic_zm'] ? getPicUrl($detail_info['license_pic_zm']) : '';
        $detail_info['license_pic_fm'] = $detail_info['license_pic_fm'] ? getPicUrl($detail_info['license_pic_fm']) : '';
        
        $detail_info['car_pic'] = $detail_info['car_pic'] ? getPicUrl($detail_info['car_pic']) : '';
        $detail_info['head_pic'] = $detail_info['head_pic'] ? getPicUrl($detail_info['head_pic']) : getPicUrl('./Public/default/images/ic_default_avator.png');
//        $detail_info['integral'] = intval($detail_info['integral']);
        
//        $detail_info['colour'] = M('colour')->where(['id'=>$detail_info['colour_id']])->getField('colours');
//        $detail_info['car_type'] = M('car')->where(['id'=>$detail_info['car_id']])->getField('car_type');
//        $detail_info['brand_type'] = M('type')->where(['id'=>$detail_info['type_id']])->getField('brand_type');
        // 获取邀请人类型
        $ttype = $base_info['invite_type'];
        $from_code = $base_info['invite_from_code'];

        if(!empty($ttype)){
            switch ($ttype)
            {
                case 1: 
                    $detail_info['be_invite_name'] = M('driver')->where(array('invite_code'=>$from_code))->getField('invite_name'); 
                    $detail_info['link_phone'] = M('driver')->where(array('invite_code'=>$from_code))->getField('mobile'); 
                    break;
                case 2: 
                    $detail_info['be_invite_name'] = M('user')->where(array('invite_code'=>$from_code))->getField('invite_name'); 
                    $detail_info['link_phone'] = M('user')->where(array('invite_code'=>$from_code))->getField('mobile'); 
                    break;
                case 3: 
                    $detail_info['be_invite_name'] = M('company')->where(array('invite_code'=>$from_code))->getField('invite_name'); 
                    $detail_info['link_phone'] = M('company')->where(array('invite_code'=>$from_code))->getField('link_phone'); 
                    break;
                default: throw new Exception('邀请码异常');
            }
        }else{
            $detail_info['be_invite_name'] = '';
            $detail_info['link_phone'] = '';
        }
        
        
        //3. 获取当月成功订单数

        // 获取当前司机本月的服务单信息
        $map['status'] = 3;
        $map['driver_id'] = $driver_id;

        list($s_time, $e_time) = getMontyTime(date('Y-m'));
//        $map['create_time'] = array(array('EGT', $s_time),array('ELT', $e_time), 'AND');
        
        $service_ids = M('service')->where($map)->getField('id', true);

        if(empty($service_ids))
        {
            $detail_info['order_num'] = 0;
        }
        else
        {
            $service_ids = implode(',', $service_ids);
            //$detail_info['order_num'] = M('order')->where(array('service_id'=>array('in', $service_ids), 'status'=>3))->count();
            $detail_info['order_num'] = M('service')->where($map)->count();
//            $count=M('vipcar')->where(array('dirverid'=>$driver_id,'state'=>array('in',array(5,6))))->count();
//            $detail_info['order_num']+=$count;
        }

        $data = array_merge($base_info, $detail_info);

        return $data;
    }
    
    /**
     * @获取已注册司机信息
     * @param type $driver_id
     * @return type
     * @throws Exception
     */
    static public function getRegInfo($driver_id)
    {
        //1. 获取基本信息
        $base_info = M('driver')->field('mobile, status, is_audit, create_time, login_time, token,cart_id,alipay,invite_code,invite_name,invite_type,invite_from_code')->find($driver_id);

        if (empty($base_info)) throw new Exception('司机信息异常');        

        // 获取邀请人类型        
        
        $ttype = $base_info['invite_type'];
        $from_code = $base_info['invite_from_code'];

        if(!empty($ttype)){
            switch ($ttype)
            {
                case 1: 
                    $base_info['be_invite_name'] = M('driver')->where(array('invite_code'=>$from_code))->getField('invite_name'); 
                    break;
                case 2: 
                    $base_info['be_invite_name'] = M('user')->where(array('invite_code'=>$from_code))->getField('invite_name'); 
                    break;
                case 3: 
                    $base_info['be_invite_name'] = M('company')->where(array('invite_code'=>$from_code))->getField('invite_name'); 
                    break;
                default: throw new Exception('邀请码异常');
            }
        }else{
            $base_info['be_invite_name'] = '';
        }
        
        $data = array_merge($base_info);

        return $data;
    }


    /**
     * 修改司机信息（单一）
     * @param $id 主键
     * @param $key 修改字段名
     * @param $val 修改值
     * @return bool
     * @throws Exception
     */
    static public function updateOneField($id, $key, $val)
    {
        $driverInfo_model = new \Home\Model\DriverinfoModel();

        if (!in_array($key, $driverInfo_model->updateFields())) throw new Exception('修改字段异常');

        if ($key == 'true_name' && $val == '') throw new Exception('名字不能为空');

        if ($key == 'head_pic') $val = PubLogic::base64_upload($val, '头像');

        $data['id'] = $id;
        $data[$key] = $val;

        if ($driverInfo_model->save($data) === false) throw new Exception('系统繁忙');

        return true;
    }


    /**
     * 司机启用 or 停用
     * @param $driver_id 司机ID
     * @return int
     * @throws Exception
     * 
     */
    static public function driverUse($driver_id)
    {
        $driver_model = new \Home\Model\DriverModel();

        //1. 验证司机信息
        $driver_info = $driver_model->field('id, status')->where(array('id'=>$driver_id))->find();
        
        if(empty($driver_info)) throw new Exception('司机信息异常');

        $driver_info['status'] = $driver_info['status'] ? 0 : 1;

        //2. 修改启用状态
        if($driver_model->save($driver_info) === false) throw new Exception('系统繁忙');
        
        return $driver_info['status'];
    }







    /**
     * 修改手机
     * @param $driver_id 司机ID
     * @param $mobile 手机号码
     * @return bool
     * @throws Exception
     */
    static public function updateMobile($driver_id, $mobile)
    {
        $driver_model = new \Home\Model\DriverModel();

        $data['id'] = $driver_id;
        $data['mobile'] = $mobile;

        //1. 修改手机信息
        if (!$driver_model->create($data)) throw new Exception($driver_model->getError());

        if ($driver_model->save() === false) throw new Exception('系统繁忙');

        //2. 删除登录信息
        $driver_model->where(array('id' => $driver_id))->setField('token', '');
        
        unset($driver_model);
        
        return true;
    }


    /**
     * 重置司机密码By手机号码
     * @param $mobile
     * @param $pwd
     * @return bool
     * @throws Exception
     */
    static public function updatePwdByMobile($mobile, $pwd)
    {
        $driver_model = new \Home\Model\DriverModel();

        //1. 验证手机号
        $driver_id = $driver_model->where(array('mobile'=>$mobile))->getField('id');

        if(empty($driver_id)) throw new Exception('手机号码有误');

        //2. 修改密码
        $data['id'] = $driver_id;
        $data['password'] = $pwd ? : '';
        $data['token'] = '';
        if(!$driver_model->create($data, 2)) throw new Exception($driver_model->getError());
        
        if($driver_model->save() === false) throw new Exception('系统繁忙');

        return true;
    }



    /**
     * 提交司机审核状态
     * @param $driver_id 司机ID
     * @param $type 1:审核通过，2：审核失败
     * @return bool
     * @throws Exception
     */
    static public function regAudit($driver_id, $type)
    {
        $driver_model = new \Home\Model\DriverModel();
        
        //1. 验证司机当前状态
        $info = $driver_model->field('is_audit, mobile')->find($driver_id);
        
        if(empty($info)) throw new Exception('司机信息异常');
        
        if(intval($info['is_audit']) !== 0) throw new Exception('当前状态异常');
        
        $data['id'] = $driver_id;
        
        switch ($type)
        {
            // 通过审核
            case 1:
                $data['is_audit'] = 1;
                break;
            
            // 拒绝审核
            case 2:
                $data['is_audit'] = -1;
                break;
            
            default:
                throw new Exception('审核状态异常');
        }

        if($driver_model->save($data) === false) throw new Exception('系统繁忙');
        
        return $info['mobile'];
    }
    
    
}