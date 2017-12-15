<?php

namespace Home\Logic;

use Think\Exception;

use Base\Logic\PubLogic;

class InviteLogic
{
	/**
     * 创建邀请码
     * @param $id 用户ID
     * @param $type 用户类型 1：司机，2：乘客，3：公司
     * @return mixed
     */
    static public function createInviteCodeNew($uid, $type)
    {
        switch ($type)
        {
            case 1: $model = M('driver'); break;
            case 2: $model = M('user'); break;
            case 3: $model = M('company'); break;
            default: throw new Exception('类型异常');
        }

        //1. 验证用户信息
        $user_info = $model->where(array('id'=>$uid))->field('id,invite_code')->find();

        if(empty($user_info)) throw new Exception('用户信息异常');

        if($user_info['invite_code']) throw new Exception('请勿重复获取邀请码');

        //2. 生成邀请码
//        $user_info['invite_code'] = getIntiteCode($type);
        $user_info['invite_code'] = getIntiteCodeNew();

        //3. 生成二维码
        $url = 'http://'.$_SERVER['HTTP_HOST'].U('Home/Invite/reg/code/'.$user_info['invite_code']);

        $filename = PubLogic::createQRcode($url);

        $user_info['qrcode_url'] = $filename;
        $user_info['qrcode_address'] = $url;

        if(!$model->save($user_info)) throw new Exception('系统繁忙');

        return $user_info['invite_code'];
    }
    /**
     * 创建邀请码
     * @param $id 用户ID
     * @param $type 用户类型 1：司机，2：乘客，3：公司
     * @return mixed
     */
    static public function createInviteCode($uid, $type)
    {
        switch ($type)
        {
            case 1: $model = M('driver'); break;
            case 2: $model = M('user'); break;
            case 3: $model = M('company'); break;
            default: throw new Exception('类型异常');
        }

        //1. 验证用户信息
        $user_info = $model->where(array('id'=>$uid))->field('id,invite_code')->find();

        if(empty($user_info)) throw new Exception('用户信息异常');

        if($user_info['invite_code']) throw new Exception('请勿重复获取邀请码');

        //2. 生成邀请码
        $user_info['invite_code'] = getIntiteCode($type);

        //3. 生成二维码
        $url = 'http://'.$_SERVER['HTTP_HOST'].U('Home/Invite/reg/code/'.$user_info['invite_code']);

        $filename = PubLogic::createQRcode($url);

        $user_info['qrcode_url'] = $filename;
        $user_info['qrcode_address'] = $url;

        if(!$model->save($user_info)) throw new Exception('系统繁忙');

        return true;
    }
    
    static public function resetInviteCode($uid, $type)
    {
        switch ($type)
        {
            case 1: $model = M('driver'); break;
            case 2: $model = M('user'); break;
            case 3: $model = M('company'); break;
            default: throw new Exception('类型异常');
        }

        //1. 验证用户信息
        $user_info = $model->where(array('id'=>$uid))->field('id,invite_code')->find();

        if(empty($user_info)) throw new Exception('用户信息异常');

        //if($user_info['invite_code']) throw new Exception('请勿重复获取邀请码');

        //2. 生成邀请码
        //$user_info['invite_code'] = getIntiteCode($type);

        //3. 生成二维码
        $url = 'http://'.$_SERVER['HTTP_HOST'].U('Home/Invite/reg/code/'.$user_info['invite_code']);

        $filename = PubLogic::createQRcode($url);
        //echo $filename;exit();
        $reset_info['qrcode_url'] = $filename;
        $reset_info['qrcode_address'] = $url;

        if(!$model->where(array('id'=>$uid))->save($reset_info)){
            return false;
        }else{
            return true;
        }        
    }


    /**
     * 创建邀请关系(多层级)
     * @param $beinvite_uid 被邀请人ID
     * @param $u_type 被邀请人类型 1：司机，2：乘客
     * @param $invite_code 邀请码
     * @return bool
     */
    static public function createInviteRelation($beinvite_uid, $u_type, $invite_code)
    {
        //1. 验证被邀请人信息
        switch ($u_type)
        {
            case 1: $model = M('driver'); break;
            case 2: $model = M('user'); break;
            default: throw new Exception('被邀请人类型异常');
        }

        $u_info = $model->where(array('id'=>$beinvite_uid))->field('id, is_invite')->find();

        if(empty($u_info)) throw new Exception('被邀请用户信息异常');

        if($u_info['is_invite']) throw new Exception('该用户已被邀请过，请勿重复操作');

        //2. 验证邀请人信息
        if(empty($invite_code)) throw new Exception('邀请码异常');

        // 获取邀请人类型
        $t = substr($invite_code, -1);

        switch ($t)
        {
            case 1: $mmodel = M('driver'); break;
            case 2: $mmodel = M('user'); break;
            case 3: $mmodel = M('company'); break;
            default: throw new Exception('邀请码异常');
        }

        // 获取邀请人ID
        $inviter_info =  $mmodel->where(array('invite_code'=>$invite_code))->field('id')->find();
        
        if(empty($inviter_info)) throw new Exception('邀请码异常');

        //3. 通过邀请人的上级关系，依次添加被邀请人的邀请关系
        $invite_model = M('invite');

        $current_time = NOW_TIME;

        $data = [];

        $dd['invite_id'] = $inviter_info['id'];
        $dd['invite_type'] = $t;
        $dd['beinvite_id'] = $u_info['id'];
        $dd['beinvite_type'] = $u_type;
        $dd['level'] = 1;
        $dd['create_time'] = $current_time;
        array_push($data, $dd);

        // 邀请人的推荐关系
        $intites = $invite_model->where(['beinvite_id'=>$inviter_info['id'], 'beinvite_type'=>$t])->field('invite_id, invite_type, level')->select();

        if(!empty($intites))
        {
            foreach($intites as $k=>$v)
            {
                $d['invite_id'] = $v['invite_id'];
                $d['invite_type'] = $v['invite_type'];
                $d['beinvite_id'] = $u_info['id'];
                $d['beinvite_type'] = $u_type;
                $d['level'] = intval($v['level']) + 1;
                $d['create_time'] = $current_time;
                array_push($data, $d);
            }
        }

        // 推荐关系入库
        foreach ($data as $k=>$v)
        {
            if(!$invite_model->add($v)) throw new Exception('系统异常');
        }

        //4. 修改被推荐人推荐标示
        $u_info['is_invite'] = 1;

        if(!$model->save($u_info)) throw new Exception('系统异常');

        return true;
    }
	/**
     * 创建邀请关系
     * @param $invite_code 一级邀请人邀请码
     * @param $code 自身邀请码
     * @return bool
     */
    static public function createInviteRelationNew($invite_code, $code) {
		//file_put_contents('user.log', var_export($invite_code) . '--' . '邀请码444-' . "\r\n", FILE_APPEND);
        $invite_info = [];
        //分销表信息
        if (!empty($invite_code)) {
            
            $invite_code_first = M('invite')->where(['beinvite_code' => $invite_code])->getField('invite_code_first');

            $driver_invite = M('driver')->where(['invite_code' => $invite_code])->find();
            $user_invite = M('user')->where(['invite_code' => $invite_code])->find();

            if (!empty($invite_code_first)) {
                $invite_info['invite_code_two'] = $invite_code_first;
                $invite_info['invite_code_first'] = $invite_code;
            } else {
                $invite_info['invite_code_first'] = $invite_code;
//                if (empty($driver_invite) && empty($user_invite)) {
//                    throw new Exception('邀请信息异常');
//                } else if (empty($driver_invite) && !empty($user_invite)) {//邀请人是用户
//                    $invite_info['invite_code_first'] = $invite_code;//M('user')->where(['invite_code' => $invite_code])->getField('invite_code');
//                } else {//邀请人是用户
//                    $invite_info['invite_code_first'] = M('driver')->where(['invite_code' => $invite_code])->getField('invite_code');
//                }
            }
            if (!empty($invite_info)) {
                $invite_info['beinvite_code'] = $code;
                $invite_info['create_time'] = time();
				//file_put_contents('user.log', var_export($invite_info, true) . '--' . '邀请码333-' . "\r\n", FILE_APPEND);
                if (!M('invite')->add($invite_info))
                    throw new Exception('添加分销信息失败');
            }
        }
        return TRUE;
    }
    /**
     * 订单分成（依据提成比例）
     * @param $order_id 订单ID
     * @return bool
     */
    static public function commissionByInviteRelation($order_id)
    {
        $bill_obj = new \Common\Controller\BillController();

        //1. 查询订单信息
        $order_info = M('order')->where(['id'=>$order_id])->field('id, user_id, service_id, money')->find();
        
        file_put_contents('order.log', var_export($order_info,true) . '--' .'订单信息-'."\r\n", FILE_APPEND);
        
        if(empty($order_info)) throw new Exception('订单信息异常');

        //3. 获取提成比例
        $set = M('set')->field('driver_rate, one_rate, two_rate, three_rate, terrace_rate, income,company_rate')->find();
        
        file_put_contents('order.log', var_export($set,true) . '--' .'提成比例-'."\r\n", FILE_APPEND);
        
        if(empty($set)) throw new Exception('系统配置异常');
  
        //4. 获取司机信息
        $driver_id = M('service')->where(array('id'=>$order_info['service_id']))->getField('driver_id');
        $driver_info = M('driver')->field('id, income')->where(array('id'=>$driver_id))->find();
        if(empty($driver_info)) throw new Exception('司机信息异常');
        
        file_put_contents('order.log', var_export($driver_info,true) . '--' .'司机信息-'."\r\n", FILE_APPEND);
        
        //5. 获取各项金额
        $order_money = floatval($order_info['money']); // 订单金额

        $driver_rate = floatval($set['driver_rate']); // 司机提成比例
        $driver_get_money = ($order_money * $driver_rate) / 100; // 司机所得金额
        $sy_money = $comm_money = $order_money - $driver_get_money; // 待抽成金额,剩余金额 初始相等
        
        file_put_contents('order.log', var_export($driver_get_money,true) . '--' .'司机所得金额-'."\r\n", FILE_APPEND);
        
        //6. 司机收入添加,流水记录添加
        $driver_info['income'] = floatval($driver_info['income']) + $driver_get_money;
        if(M('driver')->save($driver_info) === false) throw new Exception('系统异常');

        // 添加司机入账流水
        $driver_get_money = $driver_get_money * 100;
   
        $res = $bill_obj::write($driver_info['id'], $driver_get_money, '快车服务', 1);

        if($res['errCode']) throw new Exception($res['msg']);
        
        //获取公司信息
        $company_id = M('driver_info')->where(array('id'=>$driver_id))->getField('company_id');
        if(empty($company_id)) throw new Exception('所属公司信息异常');
        
        file_put_contents('order.log', var_export($company_id,true) . '--' .'司机分成公司信息-'."\r\n", FILE_APPEND);
        
        //公司分成(车)
        $company_rate = $set['company_rate'];
        $company_get_money = ($sy_money * $company_rate)/100;
        
        file_put_contents('order.log', var_export($company_get_money,true) . '--' .'司机分成公司所得金额-'."\r\n", FILE_APPEND);
        
        $res = $bill_obj::write($company_id, $company_get_money*100, '公司分成', 3);
        if($res['errCode']) throw new Exception($res['msg']);
        
        $company_income = M('company')->where(array('id'=>$company_id))->getField('income');
        $company_money['income'] = $company_income + $company_get_money;
        if(M('company')->where(array('id'=>$company_id))->save($company_money) === false) throw new Exception('公司分成失败');
        
        
        //2. 验证该用户是否被推荐
        $user_info =  M('user')->where(['id'=>$order_info['user_id']])->field('id, is_invite,invite_type,invite_from_code,company_id')->find();
        
        file_put_contents('order.log', var_export($user_info,true) . '--' .'用户信息-'."\r\n", FILE_APPEND);
        
        if(empty($user_info)) throw new Exception('用户信息异常');
        
        //公司分销
        if($user_info['company_id']){
            $company_income_first = M('company')->where(['id'=>$user_info['company_id']])->getField('income');
            //租赁公司提成
            $company_sales_rate = $set['three_rate'];
            
            $company_sales_money = ($sy_money * $company_sales_rate)/100;
            
            file_put_contents('order.log', var_export($company_sales_money,true) . '--' .'租赁公司提成-'."\r\n", FILE_APPEND);
            
            $res = $bill_obj::write($user_info['company_id'], $company_sales_money*100, '租赁公司提成', 3);
            if($res['errCode']) throw new Exception($res['msg']);
            
            $company_income_end['income'] = $company_income_first + $company_sales_money;
            if(M('company')->where(array('id'=>$user_info['company_id']))->save($company_income_end) === false) throw new Exception('租赁公司提成失败');
        }
        
        
        // 有推荐人
        if($user_info['is_invite'])
        {
            //7. 获取该用户的上级推荐人
            if(empty($user_info['invite_from_code']))  throw new Exception ('推荐人信息异常');

            $invite_users = $user_info['invite_from_code'];//邀请人
            
            $first_rate = floatval($set['one_rate'])/100;
            //一级分销
            switch ($user_info['invite_type']) {
                case 1://司机
                    $model = M('driver');
                    
                    $first = $model->alias('d')->join('__DRIVER_INFO__ as di on d.id=di.id')
                        ->where(['d.invite_code'=>$invite_users])->field('d.id,d.invite_type,d.invite_from_code,di.company_id,d.is_invite,d.income')->find();
                    
                    $type = 1;
                    break;
                case 2://乘客
                    $model = M('user');
                    
                    $first = $model->where(['invite_code'=>$invite_users])->field('id,invite_type,invite_from_code,company_id,is_invite,income')->find();
                    
                    $type = 2;
                    break;
                default:
                    $first = '';
                    break;
            }
            if(is_array($first)){
                file_put_contents('order.log', var_export($first,true) . '--' .'一级分销信息-'."\r\n", FILE_APPEND);
                $tc_first_money = $comm_money * $first_rate;

                $tj_u_info['income'] = floatval($first['income']) + $tc_first_money;
                if($model->where(['id'=>$first['id']])->save($tj_u_info) === false) throw new Exception('系统繁忙');

                file_put_contents('order.log', var_export($tc_first_money,true) . '--' .'一级分销金额-'."\r\n", FILE_APPEND);
                
                // 分成流水添加
                $tc_money = $tc_first_money * 100;
                $res = $bill_obj::write($first['id'], $tc_money, '一级分销奖励', $type);
                if($res['errCode']) throw new Exception($res['msg']);
                
                $mobile = $model->where(array('id'=>$first['id']))->getField('mobile');

                if(empty($mobile)) throw new Exception('推荐人电话获取异常');

                $alias = $type.$mobile;

//                PubLogic::pushMessage($type, $alias, '您已获得分销提成：'.round(($tc_money/100), 2).'元', ['state'=>3]);
            }
            
            //二级分销
            if(is_array($first) && $first['is_invite'] == 1){
                switch ($first['invite_type']) {
                    case 1://司机
                        $model = M('driver');
                        $two = $model->where(['invite_code'=>$first['invite_from_code']])->field('id,income')->find();
                        
                        $type = 1;
                        break;
                    case 2://乘客
                        $model = M('user');
                        
                        $two = $model->where(['invite_code'=>$first['invite_from_code']])->field('id,income')->find();
                        $type = 2;
                        break;
                    default:
                        $two = '';
                        break;
                }
                $two_rate = floatval($set['two_rate'])/100;
                if(is_array($two)){
                    $tc_two_money = $comm_money * $two_rate;

                    $tj_u_info_two['income'] = floatval($two['income']) + $tc_two_money;
                    if($model->where(['id'=>$two['id']])->save($tj_u_info_two) === false) throw new Exception('系统繁忙');

                    // 分成流水添加
                    $tc_money_two = $tc_two_money * 100;
                    
                    file_put_contents('order.log', var_export($tc_two_money,true) . '--' .'二级分销金额-'."\r\n", FILE_APPEND);
                    
                    $res = $bill_obj::write($two['id'], $tc_money_two, '二级分销奖励', $type);
                    if($res['errCode']) throw new Exception($res['msg']);
                    
                    $mobile = $model->where(array('id'=>$two['id']))->getField('mobile');

                    if(empty($mobile)) throw new Exception('推荐人电话获取异常');

                    $alias = $type.$mobile;

//                    PubLogic::pushMessage($type, $alias, '您已获得分销提成：'.round(($tc_money_two/100), 2).'元', ['state'=>3]);
                }
            }
            
                $sy_money = $sy_money - $company_get_money - $company_sales_money - $tc_first_money - $tc_two_money;
        }
            unset($model);
        file_put_contents('order.log', var_export($sy_money,true) . '--' .'剩余金额-'."\r\n", FILE_APPEND);
        //9. 平台提成
        $pt_income = floatval($set['income']) + $sy_money;
        
        file_put_contents('order.log', var_export($pt_income,true) . '--' .'平台提成-'."\r\n", FILE_APPEND);
        
        if(M('set')->where(array('id'=>1))->setField('income', $pt_income) === false) throw new Exception('系统繁忙');

        return true;
    }
	
	/**
     * 专车订单分成（依据提成比例）
     * @param $order_id 订单ID
     * @return bool
     */
    static public function VipcommissionByInviteRelation($pay)
    {
        
        $bill_obj = new \Common\Controller\BillController();

        //1. 查询订单信息
        $order_info = M('vipcar')->where(['order_sn'=>$pay['order_sn']])->field('id, uid, driverid, pay,money')->find();
        
        file_put_contents('vip.log', var_export($order_info,true) . '--' .'订单信息-'."\r\n", FILE_APPEND);
        
        if(empty($order_info)) throw new Exception('订单信息异常');

        //3. 获取提成比例
        $set = M('set')->field('driver_rate, one_rate, two_rate, three_rate, terrace_rate, income,company_rate')->find();
        
        file_put_contents('vip.log', var_export($set,true) . '--' .'提成比例-'."\r\n", FILE_APPEND);
        
        if(empty($set)) throw new Exception('系统配置异常');
  
        //4. 获取司机信息
        $driver_id = $order_info['driverid'];
        $driver_info = M('driver')->field('id, income')->where(array('id'=>$driver_id))->find();
        if(empty($driver_info)) throw new Exception('司机信息异常');
        
        file_put_contents('vip.log', var_export($driver_info,true) . '--' .'司机信息-'."\r\n", FILE_APPEND);
        
        //5. 获取各项金额
        $order_money = floatval($order_info['money']); // 订单金额

        $driver_rate = floatval($set['driver_rate']); // 司机提成比例
        $driver_get_money = ($order_money * $driver_rate) / 100; // 司机所得金额
        $sy_money = $comm_money = $order_money - $driver_get_money; // 待抽成金额,剩余金额 初始相等
        
        file_put_contents('vip.log', var_export($driver_get_money,true) . '--' .'司机所得金额-'."\r\n", FILE_APPEND);
        
        //6. 司机收入添加,流水记录添加
        $driver_info['income'] = floatval($driver_info['income']) + $driver_get_money;
        if(M('driver')->save($driver_info) === false) throw new Exception('系统异常');

        // 添加司机入账流水
        $driver_get_money = $driver_get_money * 100;
   
        $res = $bill_obj::write($driver_info['id'], $driver_get_money, '专车服务', 1);

        if($res['errCode']) throw new Exception($res['msg']);
        
        //获取公司信息
        $company_id = M('driver_info')->where(array('id'=>$driver_id))->getField('company_id');
        if(empty($company_id)) throw new Exception('所属公司信息异常');
        
        file_put_contents('vip.log', var_export($company_id,true) . '--' .'司机分成公司信息-'."\r\n", FILE_APPEND);
        
        //公司分成(车)
        $company_rate = $set['company_rate'];
        $company_get_money = ($sy_money * $company_rate)/100;
        
        file_put_contents('vip.log', var_export($company_get_money,true) . '--' .'司机分成公司所得金额-'."\r\n", FILE_APPEND);
        
        $res = $bill_obj::write($company_id, $company_get_money*100, '公司分成', 3);
        if($res['errCode']) throw new Exception($res['msg']);
        
        $company_income = M('company')->where(array('id'=>$company_id))->getField('income');
        $company_money['income'] = $company_income + $company_get_money;
        if(M('company')->where(array('id'=>$company_id))->save($company_money) === false) throw new Exception('公司分成失败');
        
        
        //2. 验证该用户是否被推荐
        $user_info =  M('user')->where(['id'=>$order_info['uid']])->field('id, is_invite,invite_type,invite_from_code,company_id')->find();
        
        file_put_contents('vip.log', var_export($user_info,true) . '--' .'专车用户信息-'."\r\n", FILE_APPEND);
        
        if(empty($user_info)) throw new Exception('用户信息异常');
        
        //公司分销
        if($user_info['company_id']){
            $company_income_first = M('company')->where(['id'=>$user_info['company_id']])->getField('income');
            //租赁公司提成
            $company_sales_rate = $set['three_rate'];
            
            $company_sales_money = ($sy_money * $company_sales_rate)/100;
            
            file_put_contents('vip.log', var_export($company_sales_money,true) . '--' .'租赁公司提成-'."\r\n", FILE_APPEND);
            
            $res = $bill_obj::write($user_info['company_id'], $company_sales_money*100, '租赁公司提成', 3);
            if($res['errCode']) throw new Exception($res['msg']);
            
            $company_income_end['income'] = $company_income_first + $company_sales_money;
            if(M('company')->where(array('id'=>$user_info['company_id']))->save($company_income_end) === false) throw new Exception('租赁公司提成失败');
        }
        
        
        // 有推荐人
        if($user_info['is_invite'])
        {
            //7. 获取该用户的上级推荐人
            if(empty($user_info['invite_from_code']))  throw new Exception ('推荐人信息异常');

            $invite_users = $user_info['invite_from_code'];//邀请人
            
            $first_rate = floatval($set['one_rate'])/100;
            //一级分销
            switch ($user_info['invite_type']) {
                case 1://司机
                    $model = M('driver');
                    
                    $first = $model->alias('d')->join('__DRIVER_INFO__ as di on d.id=di.id')
                        ->where(['d.invite_code'=>$invite_users])->field('d.id,d.invite_type,d.invite_from_code,di.company_id,d.is_invite,d.income')->find();
                    
                    $type = 1;
                    break;
                case 2://乘客
                    $model = M('user');
                    
                    $first = $model->where(['invite_code'=>$invite_users])->field('id,invite_type,invite_from_code,company_id,is_invite,income')->find();
                    
                    $type = 2;
                    break;
                default:
                    $first = '';
                    break;
            }
            if(is_array($first)){
                file_put_contents('vip.log', var_export($first,true) . '--' .'一级分销信息-'."\r\n", FILE_APPEND);
                $tc_first_money = $comm_money * $first_rate;

                $tj_u_info['income'] = floatval($first['income']) + $tc_first_money;
                if($model->where(['id'=>$first['id']])->save($tj_u_info) === false) throw new Exception('系统繁忙');

                file_put_contents('vip.log', var_export($tc_first_money,true) . '--' .'一级分销金额-'."\r\n", FILE_APPEND);
                
                // 分成流水添加
                $tc_money = $tc_first_money * 100;
                $res = $bill_obj::write($first['id'], $tc_money, '一级分销奖励', $type);
                if($res['errCode']) throw new Exception($res['msg']);
                
                $mobile = $model->where(array('id'=>$first['id']))->getField('mobile');

                if(empty($mobile)) throw new Exception('推荐人电话获取异常');

                $alias = $type.$mobile;

//                PubLogic::pushMessage($type, $alias, '您已获得分销提成：'.round(($tc_money/100), 2).'元', ['state'=>3]);
            }
            
            //二级分销
            if(is_array($first) && $first['is_invite'] == 1){
                switch ($first['invite_type']) {
                    case 1://司机
                        $model = M('driver');
                        $two = $model->where(['invite_code'=>$first['invite_from_code']])->field('id,income')->find();
                        
                        $type = 1;
                        break;
                    case 2://乘客
                        $model = M('user');
                        
                        $two = $model->where(['invite_code'=>$first['invite_from_code']])->field('id,income')->find();
                        $type = 2;
                        break;
                    default:
                        $two = '';
                        break;
                }
                $two_rate = floatval($set['two_rate'])/100;
                if(is_array($two)){
                    $tc_two_money = $comm_money * $two_rate;

                    $tj_u_info_two['income'] = floatval($two['income']) + $tc_two_money;
                    if($model->where(['id'=>$two['id']])->save($tj_u_info_two) === false) throw new Exception('系统繁忙');

                    // 分成流水添加
                    $tc_money_two = $tc_two_money * 100;
                    
                    file_put_contents('order.log', var_export($tc_two_money,true) . '--' .'二级分销金额-'."\r\n", FILE_APPEND);
                    
                    $res = $bill_obj::write($two['id'], $tc_money_two, '二级分销奖励', $type);
                    if($res['errCode']) throw new Exception($res['msg']);
                    
                    $mobile = $model->where(array('id'=>$two['id']))->getField('mobile');

                    if(empty($mobile)) throw new Exception('推荐人电话获取异常');

                    $alias = $type.$mobile;

//                    PubLogic::pushMessage($type, $alias, '您已获得分销提成：'.round(($tc_money_two/100), 2).'元', ['state'=>3]);
                }
            }
            
                $sy_money = $sy_money - $company_get_money - $company_sales_money - $tc_first_money - $tc_two_money;
        }
            unset($model);
        file_put_contents('vip.log', var_export($sy_money,true) . '--' .'剩余金额-'."\r\n", FILE_APPEND);
        //9. 平台提成
        $pt_income = floatval($set['income']) + $sy_money;
        
        file_put_contents('vip.log', var_export($pt_income,true) . '--' .'平台提成-'."\r\n", FILE_APPEND);
        
        if(M('set')->where(array('id'=>1))->setField('income', $pt_income) === false) throw new Exception('系统繁忙');

        return true;
    }
	

    /**
     * 现付订单，司机回款（分成）
     * @param $money
     * @return bool
     */
    static public function commissionByHkd($service_id)
    {
        $service_model = new \Home\Model\ServiceModel();

        //1. 获取行程单信息
        $service_info = $service_model->field('id, status, dj_status,driver_id')->where(array('id'=>$service_id))->find();
        
        $company_id = M('driver_info')->where(['id'=>$service_info['driver_id']])->getField('company_id');
        
        file_put_contents('order1.log', var_export($service_info,true) . '--' .'判断回款-'."\r\n", FILE_APPEND);
        
        if(empty($service_info)) throw new Exception('行程单信息异常');
        if($service_info['status'] != 3) throw new Exception('行程单状态异常');
        if($service_info['dj_status'] != 3) throw new Exception('回款状态异常');
        if(empty($company_id)) throw new Exception('司机信息异常');

        //2. 获取未付款订单信息
        $order_model = new \Home\Model\OrderModel();
        $user_model = new \Home\Model\UserModel();

        $orders = $order_model->where(array('pay_mothod'=>3, 'status'=>3,'service_id'=>$service_id))->field('id, user_id, money')->select();
        
        file_put_contents('order1.log', var_export($orders,true) . '--' .'判断回款order-'."\r\n", FILE_APPEND);
        if(empty($orders)) throw new Exception('现付订单信息异常');

        //3. 按比例分成
        $set = M('set')->field('one_rate, two_rate, three_rate,company_rate,income')->find();

//        $invite_model = M('invite');

        $bill_obj = new \Common\Controller\BillController();

        foreach($orders as $k=>$v)
        {
            // 获取待分成金额
            $sy_money = $comm_money = $money = floatval($v['money']) - ( floatval($v['money']) * (floatval($set['driver_rate'])/100));

            if($money <= 0) continue;
            
            //公司分成(车)
            $company_rate = $set['company_rate'];
            $company_get_money = ($sy_money * $company_rate)/100;

            file_put_contents('order1.log', var_export($company_get_money,true) . '--' .'司机分成公司所得金额-'."\r\n", FILE_APPEND);

            $res = $bill_obj::write($company_id, $company_get_money*100, '公司分成', 3);
            if($res['errCode']) throw new Exception($res['msg']);

            $company_income = M('company')->where(array('id'=>$company_id))->getField('income');
            $company_money['income'] = $company_income + $company_get_money;
            if(M('company')->where(array('id'=>$company_id))->save($company_money) === false) throw new Exception('公司分成失败');


            //2. 验证该用户是否被推荐
            $user_info =  M('user')->where(['id'=>$v['user_id']])->field('id, is_invite,invite_type,invite_from_code,company_id')->find();

            file_put_contents('order1.log', var_export($user_info,true) . '--' .'用户信息-'."\r\n", FILE_APPEND);

            if(empty($user_info)) throw new Exception('用户信息异常');

            //公司分销
            if($user_info['company_id']){
                $company_income_first = M('company')->where(['id'=>$user_info['company_id']])->getField('income');
                //租赁公司提成
                $company_sales_rate = $set['three_rate'];

                $company_sales_money = ($sy_money * $company_sales_rate)/100;

                file_put_contents('order1.log', var_export($company_sales_money,true) . '--' .'租赁公司提成-'."\r\n", FILE_APPEND);

                $res = $bill_obj::write($user_info['company_id'], $company_sales_money*100, '租赁公司提成', 3);
                if($res['errCode']) throw new Exception($res['msg']);

                $company_income_end['income'] = $company_income_first + $company_sales_money;
                if(M('company')->where(array('id'=>$user_info['company_id']))->save($company_income_end) === false) throw new Exception('租赁公司提成失败');
            }


            // 有推荐人
            if($user_info['is_invite'])
            {
                //7. 获取该用户的上级推荐人
                if(empty($user_info['invite_from_code']))  throw new Exception ('推荐人信息异常');

                $invite_users = $user_info['invite_from_code'];//邀请人

                $first_rate = floatval($set['one_rate'])/100;
                //一级分销
                switch ($user_info['invite_type']) {
                    case 1://司机
                        $model = M('driver');

                        $first = $model->alias('d')->join('__DRIVER_INFO__ as di on d.id=di.id')
                            ->where(['d.invite_code'=>$invite_users])->field('d.id,d.invite_type,d.invite_from_code,di.company_id,d.is_invite,d.income')->find();

                        $type = 1;
                        $typemsg = 'driver';
                        break;
                    case 2://乘客
                        $model = M('user');

                        $first = $model->where(['invite_code'=>$invite_users])->field('id,invite_type,invite_from_code,company_id,is_invite,income')->find();

                        $type = 2;
                        $typemsg = 'user';
                        break;
                    default:
                        $first = '';
                        break;
                }
                if(is_array($first)){
                    file_put_contents('order1.log', var_export($first,true) . '--' .'一级分销信息-'."\r\n", FILE_APPEND);
                    $tc_first_money = $comm_money * $first_rate;

                    $tj_u_info['income'] = floatval($first['income']) + $tc_first_money;
                    if($model->where(['id'=>$first['id']])->save($tj_u_info) === false) throw new Exception('系统繁忙');

                    file_put_contents('order1.log', var_export($tc_first_money,true) . '--' .'一级分销金额-'."\r\n", FILE_APPEND);

                    // 分成流水添加
                    $tc_money = $tc_first_money * 100;
                    $res = $bill_obj::write($first['id'], $tc_money, '一级分销奖励', $type);
                    if($res['errCode']) throw new Exception($res['msg']);

                    $mobile = $model->where(array('id'=>$first['id']))->getField('mobile');

                    if(empty($mobile)) throw new Exception('推荐人电话获取异常');

                    $alias = $typemsg.$mobile;

//                    PubLogic::pushMessage($typemsg, $alias, '您已获得分销提成：'.round(($tc_money/100), 2).'元', ['state'=>3]);
                }

                //二级分销
                if(is_array($first) && $first['is_invite'] == 1){
                    switch ($first['invite_type']) {
                        case 1://司机
                            $model = M('driver');
                            $two = $model->where(['invite_code'=>$first['invite_from_code']])->field('id,income')->find();

                            $type = 1;
                            $typemsg = 'driver';
                            break;
                        case 2://乘客
                            $model = M('user');

                            $two = $model->where(['invite_code'=>$first['invite_from_code']])->field('id,income')->find();
                            $type = 2;
                            $typemsg = 'user';
                            break;
                        default:
                            $two = '';
                            break;
                    }
                    $two_rate = floatval($set['two_rate'])/100;
                    if(is_array($two)){
                        $tc_two_money = $comm_money * $two_rate;

                        $tj_u_info_two['income'] = floatval($two['income']) + $tc_two_money;
                        if($model->where(['id'=>$two['id']])->save($tj_u_info_two) === false) throw new Exception('系统繁忙');

                        // 分成流水添加
                        $tc_money_two = $tc_two_money * 100;

                        file_put_contents('order1.log', var_export($tc_two_money,true) . '--' .'二级分销金额-'."\r\n", FILE_APPEND);

                        $res = $bill_obj::write($two['id'], $tc_money_two, '二级分销奖励', $type);
                        if($res['errCode']) throw new Exception($res['msg']);

                        $mobile = $model->where(array('id'=>$two['id']))->getField('mobile');

                        if(empty($mobile)) throw new Exception('推荐人电话获取异常');

                        $alias = $typemsg.$mobile;

//                        PubLogic::pushMessage($typemsg, $alias, '您已获得分销提成：'.round(($tc_money_two/100), 2).'元', ['state'=>3]);
                    }
                }

                    $sy_money = $sy_money - $company_get_money - $company_sales_money - $tc_first_money - $tc_two_money;
            }
                unset($model);
            //9. 平台提成
            $pt_income = floatval($set['income']) + $sy_money;

            file_put_contents('order1.log', var_export($pt_income,true) . '--' .'平台提成-'."\r\n", FILE_APPEND);

            if(M('set')->where(array('id'=>1))->setField('income', $pt_income) === false) throw new Exception('系统繁忙');
        
        }

        return true;
    }
    


    /**
     * 提现
     * @param $id 对象ID
     * @param $type 1：司机，2：乘客，3：公司
     * @param $money 提现金额
     * @return bool
     */
    static public function withdraw($id, $money, $type)
    {
        switch ($type)
        {
            case 1: $model = M('driver'); break;
            case 2: $model = M('user'); break;
            case 3: $model = M('company'); break;
            default: throw new Exception('类型异常');
        }

        $time = time();
        $time = date('Y-m-d',$time);
        $week = get_week($time);
        if ($week !== '星期二') {
            throw new Exception('只能星期二提现');
        }


        if(empty($money)) throw new Exception('请填写正确的提现金额');

        //1. 验证对象信息，可提现余额
        $info = $model->field('id, income, spend')->where(array('id'=>$id))->find();
        if(empty($info)) throw new Exception('信息异常');

        $income = floatval($info['income']); // 总收入
        $spend = floatval($info['spend']); // 总提现
        $blances = $income - $spend ? : 0; // 可提现余额
        $blance = round($blances,2);
        $money = floatval($money); // 待提现金额
        if( $money > $blance ) throw new Exception('余额不足');

        //2. 添加提现总金额
        $info['spend'] = $spend + $money;
        if($model->save($info) === false) throw new Exception('系统异常');

        //3. 流水写入
        $bill_obj = new \Common\Controller\BillController();
        $money = -($money * 100);
        $res = $bill_obj::write($id, $money, '现金提现', $type);
        if($res['errCode']) throw new Exception($res['msg']);

        return true;
    }
}