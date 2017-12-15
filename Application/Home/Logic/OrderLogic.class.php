<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/12
 * Time: 17:46
 */

namespace Home\Logic;


use Think\Exception;

class OrderLogic
{
    static $n = 0;

    /**
     * 添加订单
     * @param $user_id 乘客ID
     * @param int $service_id 服务单ID
     * @param $remark 备注信息
     * @param $seat_num 需求座位数
     * @return mixed
     * @throws Exception
     */
    static public function addOrder($user_id, $service_id=0, $remark, $seat_num)
    {
        if(self::$n > 100) throw new Exception('系统繁忙，请稍后再试');
        
        $order_model = new \Home\Model\OrderModel();
        $service_model = new \Home\Model\ServiceModel();

        //1. 验证用户是否有未完成订单
        if($order_model->where(array('user_id'=>$user_id, 'status'=>array('in', '0,1,2')))->count()) throw new Exception('您有进行中的订单');

        //2. 获取服务单信息，验证相关信息
        $service_info = $service_model->where(array('id'=>$service_id))->field('id, price, block, status, version')->find();

        if(empty($service_info)) throw new Exception('服务单信息异常');

        if($service_info['status'] != 1) throw new Exception('服务单状态异常');

        //if(empty($remark)) throw new Exception('备注信息不能为空');

        if($seat_num == 0) throw new Exception('座位数信息异常');

        if((int)$service_info['block'] < (int)$seat_num) throw new Exception('可用座位数不够');
       
        //3. 添加订单信息
        $data['money'] = floatval($seat_num * $service_info['price']);
        $data['user_id'] = $user_id;
        $data['service_id'] = $service_id;
        $data['seat_num'] = $seat_num;
        $data['remark'] = $remark;
        $data['order_sn'] = makeOrder_sn();

        if(!$order_model->create($data)) throw new Exception('创建数据失败');

        $order_id = $order_model->add();

        //4. 修改服务单剩余座位信息
        $service['block'] = intval($service_info['block']) - intval($seat_num);
        $service['version'] = intval($service_info['version']) + 1;

        if($service_model->where(array('id'=>$service_info['id'], 'version'=>$service_info['version']))->save($service) === false)
        {
            self::$n++;

            unset($order_model, $service_model);
            
            self::addOrder($user_id, $service_id, $remark, $seat_num);
        }
        
        return $data['order_sn'];
    }


    /**
     * 支付逻辑
     * @param $type 支付方式：微信:wechat;支付宝：alipay; 现金：cash
     * @param $order_id 订单ID
     * @param $user_id 乘客ID
     * @return bool
     * @throws Exception
     */
    static public function pay($type, $order_sn, $user_id,$useintegral=0)
    {

        //1. 获取订单信息
        $order_model = new \Home\Model\OrderModel();

        $order_info = $order_model->where(array('user_id'=>$user_id, 'order_sn'=>$order_sn))->field('id, status, money, seat_num, service_id, remark')->find();
        
        file_put_contents('pay.log', var_export($order_info,true) . '--' .'支付订单信息-'."\r\n", FILE_APPEND);
        
        if(empty($order_info)) throw new Exception('订单信息异常');

        if($order_info['status'] != 4) throw new Exception('订单状态异常');

        $payOBJ=new \Common\Controller\PayController();
        $orders['type'] = 1;
        $orders['uid']=$user_id;
        //2. 选择支付方式
        switch ($type)
        {
            // 线上支付
            case 'alipay':
                $orders['order_sn'] = $order_sn;
                $orders['money'] = floatval($order_info['money']) * 100;
                return $payOBJ->pay($orders, $type,$useintegral);
                break;
            case 'wechat':
                $orders['order_sn'] = $order_sn;
                $orders['money'] = floatval($order_info['money']) * 100;
                return $payOBJ->pay($orders, $type,$useintegral);
                break;

            // 现金支付
            case 'cash': 
      
                $data['pay_mothod'] = 3;
                $data['id'] = $order_info['id'];
                $data['status'] =1;

                //3. 修改订单状态
                if($order_model->save($data) === false) throw new Exception('系统繁忙，请稍后再试');

                //4. 获取始发站点信息
                $service_info = M('service')->where(array('id'=>$order_info['service_id']))->field('driver_id, start_city, start_point')->find();

                $city = M('region')->where(array('id'=>$service_info['start_city']))->getField('name') ? : '';
                $point = M('region')->where(array('id'=>$service_info['start_point']))->getField('name') ? : '';
                $order_info['start_point'] = $city.$point;

                //5. 获取司机电话
                $driver_mobile = M('driver')->where(array('id'=>$service_info['driver_id']))->getField('mobile');
                
                if(empty($driver_mobile)) throw new Exception('司机电话异常');

                return [$order_info['id'], $order_info['seat_num'], $order_info['start_point'], $driver_mobile, $order_info['remark']];
                break;

            default:
                throw new Exception('支付方式异常');
        }

    }


    /**
     * 司机确认订单（司机接单）
     * @param $driver_id
     * @param $order_id
     * @return mixed
     * @throws Exception
     */
    static public function confirmOrder($driver_id, $order_id)
    {
        $order_model = new \Home\Model\OrderModel();
        $service_model = new \Home\Model\ServiceModel();
        $user_model = new \Home\Model\UserModel();

        //1. 获取该订单信息
        $order_info = $order_model->field('id, user_id, service_id, status, order_sn')->where(array('id'=>$order_id))->find();

        if(empty($order_info)) throw new Exception('订单信息异常');

        if(intval($order_info['status']) != 0) throw new Exception('订单状态异常');

        //2. 验证服务单信息
        $service_status = $service_model->where(array('id'=>$order_info['service_id'], 'driver_id'=>$driver_id))->getDbFields('status');

        if(intval($service_status) != 1) throw new Exception('服务单状态异常');

        //3. 修改订单状态
        $order_info['status'] = 2;
        if(!$order_model->save($order_info)) throw new Exception('系统繁忙');
        
        //4. 获取用户手机
        $mobile = $user_model->where(array('id'=>$order_info['user_id']))->getField('mobile');
        
        $arr = \Base\Logic\PubLogic::pushMessage('user', 'user'.$mobile, '司机已确认订单，请准时乘车', array('state'=>4));
        \Think\Log::record("errorlog :".  var_export($arr,true));
        unset($order_model, $service_model);
        
        return array($order_info['order_sn'], $mobile);
    }
	/**
     * 司机取消乘客订单
     * @param $driver_id 司机ID
     * @param $order_sn 乘客订单编号
     * @return bool
     * @throws Exception
     */
    static public function cancelOrderByDriver($driver_id, $order_sn,$service_id)
    {
        $order_model = new \Home\Model\OrderModel();
        $service_model = new \Home\Model\ServiceModel();

        $map['order_sn'] = $order_sn;
        $map['service_id'] = $service_id;

        //1. 获取订单信息
//        $order_info = $service_model->field('id, user_id, order_sn, status, seat_num, service_id, pay_mothod')->where($map)->find();
        $order_info = $order_model->field('id, user_id, order_sn, status, seat_num, service_id, pay_mothod')->where($map)->find();

        if(empty($order_info)) throw new Exception('订单信息异常');

        //2. 验证订单状态in_array($order_info['status'], [1,2]
        if(!in_array($order_info['status'], [0,1,2])) throw new Exception('您的订单已发车');

        // 若订单状态为已支付，且不为现金支付，则调用退款流程
        if($order_info['status'] == 1 && $order_info['pay_mothod'] != 3)
        {
            $refund_obj = new \Common\Controller\RefundController();
            $res = $refund_obj->refund($order_info['order_sn'],1,null,$user_id);
            if(!$res['state']) throw new Exception($res['msg']);
        }

        //3. 修改订单状态为已取消
        $order_info['status'] = -1;

        if($order_model->save($order_info) === false) throw new Exception('系统繁忙');

        //4. 修改行程余坐信息
        if(!$service_model->where(array('id'=>$order_info['service_id']))->setInc('block', $order_info['seat_num'])) throw new Exception('系统繁忙');

        //5. 获取乘客电话
        $user_mobile = M('user')->where(array('id'=>$order_info['user_id']))->getField('mobile');

        if(empty($user_mobile)) throw new Exception('乘客电话获取异常');
        
        unset($order_model, $service_model);
        
        return $user_mobile;
    }

    /**
     * 取消订单
     * @param $user_id 乘客ID
     * @param $order_sn 订单编号
     * @return bool
     * @throws Exception
     */
    static public function cancelOrder($user_id, $order_sn)
    {
        $order_model = new \Home\Model\OrderModel();
        $service_model = new \Home\Model\ServiceModel();

        $map['order_sn'] = $order_sn;
        $map['user_id'] = $user_id;

        //1. 获取订单信息
        $order_info = $order_model->field('id, user_id, order_sn, status, seat_num, service_id, pay_mothod')->where($map)->find();

        if(empty($order_info)) throw new Exception('订单信息异常');

        //2. 验证订单状态
        if(!in_array($order_info['status'], [0,1,2])) throw new Exception('您的订单已发车');

        // 若订单状态为已支付，且不为现金支付，则调用退款流程
        if($order_info['status'] == 1 && $order_info['pay_mothod'] != 3)
        {
            $refund_obj = new \Common\Controller\RefundController();
            $res = $refund_obj->refund($order_info['order_sn'],1,null,$user_id);
            if(!$res['state']) throw new Exception($res['msg']);
        }

        //3. 修改订单状态为已取消
        $order_info['status'] = -1;

        if($order_model->save($order_info) === false) throw new Exception('系统繁忙');

        //4. 修改行程余坐信息
        if(!$service_model->where(array('id'=>$order_info['service_id']))->setInc('block', $order_info['seat_num'])) throw new Exception('系统繁忙');

        //5. 获取乘客电话
        $user_mobile = M('user')->where(array('id'=>$order_info['user_id']))->getField('mobile');

        if(empty($user_mobile)) throw new Exception('乘客电话获取异常');
        
        unset($order_model, $service_model);
        
        return $user_mobile;
    }


    /**
     * 司机拒绝乘客下的单
     * @param $order_id 订单ID
     * @return bool
     */
    static public function refuseOrder($order_id)
    {
        $order_model = new \Home\Model\OrderModel();

        //1. 验证订单信息，相关状态
        $order_info = $order_model->field('id, user_id, service_id, order_sn, seat_num, money, status, pay_mothod')->where(array('id'=>$order_id))->find();
     
        if(empty($order_info)) throw new Exception('订单信息异常');
        if($order_info['status'] != 0) throw new Exception('订单状态异常');

//        //2. 非现金支付的订单 发起退款流程
//        if($order_info['pay_mothod'] != 3)
//        {
//            $refund_obj = new \Common\Controller\RefundController();
//            $res = $refund_obj->refund($order_info['order_sn']);
//            if(!$res['state']) throw new Exception($res['msg']);
//        }

        //3. 修改订单状态
        $order_info['status'] = -2;
        if(!$order_model->save($order_info)) throw new Exception('系统异常');

        //4. 修改行程余坐信息
        if(!M('service')->where(array('id'=>$order_info['service_id']))->setInc('block', $order_info['seat_num'])) throw new Exception('系统繁忙');

        //5. 获取乘客电话
        $user_mobile = M('user')->where(array('id'=>$order_info['user_id']))->getField('mobile');

        if(empty($user_mobile)) throw new Exception('乘客电话获取异常');
        
        return $user_mobile;
    }


    /**************************************** getData ********************************************/


    /**
     * 获取订单信息(支付成功页面)
     * @param $order_id
     * @return mixed
     * @throws Exception
     */
    static public function getOrderInfoByService($order_sn)
    {
        $order_model = new \Home\Model\OrderModel();

        $field = 'a.id AS order_id, a.order_sn, a.seat_num, a.money, a.create_time, a.remark';
        $field .= ' ,b.start_city, b.start_point, b.purpose_city, b.purpose_point, b.type, b.departur_time';
        $field .= ' ,c.mobile, d.license_sn';

        $map['a.order_sn'] = $order_sn;

        $info = $order_model->alias('a')->join('__SERVICE__ as b ON a.service_id = b.id', 'INNER')
                                        ->join('__DRIVER__ as c ON b.driver_id = c.id', 'INNER')
                                        ->join('__DRIVER_INFO__ as d ON b.driver_id = d.id', 'INNER')
                                        ->field($field)
                                        ->where($map)
                                        ->find();
        
        if(empty($info)) throw new Exception('订单信息异常');

        // 处理数据
        $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        $info['departur_time'] = !empty($info['departur_time']) ? date('Y-m-d H:i:s', $info['departur_time']) : null;
        $info['order_id'] = intval($info['order_id']);
        $info['seat_num'] = intval($info['seat_num']);
        $info['type'] = intval($info['type']);
        $info['money'] = floatval($info['money']);

        $info['start_city'] = M('region')->where(array('id'=>$info['start_city']))->getField('name') ? : '未知';
        $info['start_point'] = M('region')->where(array('id'=>$info['start_point']))->getField('name') ? : '未知';
        $info['purpose_city'] = M('region')->where(array('id'=>$info['purpose_city']))->getField('name') ? : '未知';
        $info['purpose_point'] = M('region')->where(array('id'=>$info['purpose_point']))->getField('name') ? : '未知';

        return $info;
    }


    /**
     * 获取乘客订单列表信息
     * @param $user_id
     * @return array
     */
    static public function getOrderByUser($user_id)
    {
        $order_model = new \Home\Model\OrderModel();

        $map['a.user_id'] = $user_id;

        $field = 'a.order_sn, a.create_time, a.status, b.start_city, b.start_point, b.purpose_city, b.purpose_point';

        $list = $order_model->alias('a')->join('__SERVICE__ as b ON a.service_id = b.id')->where($map)->field($field)->order('a.create_time DESC')->select();

        if(!empty($list))
        {
            $region_model = M('region');

            foreach($list as $k=>&$v)
            {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $v['status'] = orderStatus($v['status']);
                $v['start_city'] = $region_model->where(array('id'=>$v['start_city']))->getField('name') ? : '未知';
                $v['start_point'] = $region_model->where(array('id'=>$v['start_point']))->getField('name') ? : '未知';
                $v['purpose_city'] = $region_model->where(array('id'=>$v['purpose_city']))->getField('name') ? : '未知';
                $v['purpose_point'] = $region_model->where(array('id'=>$v['purpose_point']))->getField('name') ? : '未知';
            }
            
            unset($region_model);
        }

        return array($list, count($list));
    }

    
    /**
     * 获取订单单条信息
     * @param $user_id 乘客ID
     * @param $order_sn 订单编号
     * @param string $field 获取字段
     * @return mixed
     * @throws Exception
     */
    static public function getOrderInfo($user_id, $order_sn, $field='*')
    {
        $order_model = new \Home\Model\OrderModel();

        //1. 获取订单信息
        $order_info = $order_model->alias('a')->join('__USER__ as b on a.user_id = b.id', 'INNER')
                                  ->where(array('a.user_id'=>$user_id, 'a.order_sn'=>$order_sn))
                                  ->field($field)
                                  ->find();

        if(empty($order_info)) throw new Exception('订单信息异常');
        
        unset($order_model);

        return $order_info;
    }


    /**
     * 获取订单列表信息
     * @param string $field
     * @param array $map
     * @return array
     */
    static public function getOrderList($field='*', $map = array(), $size=20)
    {
        $order_model = new \Home\Model\OrderModel();

        $count = $order_model->alias('a')->join('__USER__ as b on a.user_id = b.id', 'INNER')->where($map)->count();

        // 开启分页类
        $page = new \Think\Page($count, $size);

        // 获取分页显示
        $fpage = $count>$size ? $page->Show() : '';

        $list = $order_model->alias('a')->join('__USER__ as b on a.user_id = b.id', 'INNER')
                            ->where($map)
                            ->field($field)
                            ->limit("{$page->firstRow}, {$page->listRows}")
                            ->order('a.id desc')
                            ->select();

        return array($list, $fpage);
    }


    static public function getOrdersByService($service_id)
    {
        $order_model = new \Home\Model\OrderModel();

        if(empty($service_id)) return array(array(), 0);


        $map['a.status'] = array('egt',0);
        $map['a.service_id'] = $service_id;

        $list = $order_model->alias('a')->join('__SERVICE__ as b on a.service_id = b.id', 'INNER')
                                        ->join('__USER__ as c on a.user_id = c.id', 'INNER')
                                        ->where($map)
                                        ->field('a.order_sn,a.id as order_id,(a.seat_num*b.price) as money,a.pay_mothod,a.status,a.remark,b.is_yy, b.start_city, b.start_point, c.mobile,c.head_pic,nick_name,a.seat_num,FROM_UNIXTIME(a.create_time,\'%Y-%m-%d %H:%i:%s\') as createtime')
                                        ->order('a.create_time DESC')
                                        ->select();
        
        foreach($list as $key=>$val){
            $list[$key]['head_pic']='http://'.I('server.SERVER_NAME'). substr($val['head_pic'],1);
        }
        if(!empty($list))
        {
            $region_model = M('region');

            foreach($list as $k=>&$v)
            {
                //$v['status'] = ($v['status'] == 1) ? 0 : 1; // 确认状态
                $v['status']=(int)$v['status'];
                $v['start_city'] = $region_model->where(['id'=>$v['start_city']])->getField('name') ? : '未知';
                $v['start_point'] = $region_model->where(['id'=>$v['start_point']])->getField('name') ? : '未知';
            }
        }
        
        return array($list, count($list));
    }


    /**
     * 获取汇款单信息
     * @param $order_sn
     * @return mixed
     * @throws Exception
     */
    static public function getHkdInfo($order_sn)
    {
        $hkd_info = M('service_dj')->field('id AS order_id, order_sn, money, status')->where(array('order_sn'=>$order_sn))->find();

        if(empty($hkd_info)) throw new Exception('订单信息异常');

        return $hkd_info;
    }
}