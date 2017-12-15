<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/6
 * Time: 11:18
 */

namespace Home\Logic;

use Think\Exception;
use Home\Logic\InviteLogic;

class ServiceLogic {

    /**
     * 服务添加
     * @param $data
     * @return bool
     * @throws Exception
     */
    static public function addService($data) {
        $service_model = new \Home\Model\ServiceModel();

        $driver_id = $data['driver_id'];

        // 验证司机状态
        $driver_info = M('driver')->field('status, is_audit')->find($driver_id);

        if (!$driver_info['status'])
            throw new Exception('账号已被禁用，请联系管理员');

        if ($driver_info['is_audit'] != 1)
            throw new Exception('该账号尚未通过审核，请联系管理员');

        //1. 验证信息
        // 是否有未回款的现付订单
        if (M('service')->where(array('driver_id' => $driver_id, 'status' => 3, 'dj_status' => array('in', '1,2,-1')))->count())
            throw new Exception('您有未回款的现付订单');

        // 是否预约发车
        if ($data['is_yy']) {
            if (empty($data['yy_time']))
                throw new Exception('请选择预约发车时间');

            $data['type'] = 2; // 定时发车
            $data['departur_time'] = strtotime($data['yy_time']);
            unset($data['yy_time']);
            $data['departur_day'] = date('Ymd', $data['departur_time']);
            $h = date('H', $data['departur_time']);
            if ($h < 12) {
                $data['departur_period'] = 1;
            } else {
                $data['departur_period'] = 2;
            }
            $service_model->setYyRelation();

            // 验证预约时间是否为当天
            if (date('ymd', $data['departur_time']) == date('ymd'))
                throw new Exception('预约发车时间不能为下单当天');

            if ($service_model->where(array('driver_id' => $driver_id, 'status' => array('in', '1,2'), 'is_yy' => 1))->count())
                throw new Exception('当前司机有预约订单进行中');
        }
        else {
            // 定时发车
            if ($data['type'] == 2) {
                if (empty($data['last_start_time']))
                    throw new Exception('请选择发车时间');
                $data['departur_time'] = strtotime($data['last_start_time']);
                if (time() > $data['departur_time'])
                    throw new Exception('发车时间不能晚于当前时间');
                $data['departur_day'] = date('Ymd', $data['departur_time']);
                if ($data['departur_day'] != date('Ymd'))
                    throw new Exception('发车时间异常');

                $h = date('H', $data['departur_time']);
                if ($h < 12) {
                    $data['departur_period'] = 1;
                } else {
                    $data['departur_period'] = 2;
                }
            } else if ($data['type'] == 1) {
                $data['departur_day'] = date('Ymd');
            }

            $service_model->setTypeRelation($data['type']);
            if (isset($data['yy_time']))
                unset($data['yy_time']);

            // 验证是否有当天的预约订单
            $yy_time = $service_model->where(array('driver_id' => $driver_id, 'status' => array('in', '1,2'), 'is_yy' => 1))->field('departur_time');

            if (!empty($yy_time) && date('ymd', $yy_time) == date('ymd'))
                throw new Exception('今天已有订单存在');

            if ($service_model->where(array('driver_id' => $driver_id, 'status' => array('in', '1,2'), 'is_yy' => 0))->count())
                throw new Exception('您有订单进行中');
        }

        // 获取座位数
        $data['block'] = M('driver_info')->where(array('id' => $driver_id))->getField('car_load_num');

        if (!$service_model->create($data))
            throw new Exception($service_model->getError());

        //3. 添加信息
        $service_id = $service_model->add();

        unset($service_model);

        return $service_id;
    }

    static public function updateStatus($service_id, $type) {
        $service_model = new \Home\Model\ServiceModel();
        $order_model = new \Home\Model\OrderModel();

        //1. 获取该服务单信息
        $service_info = $service_model
                ->field('id, status, driver_id,start_city, start_point, purpose_city, purpose_point')
                ->where(array('id' => $service_id))
                ->find();

        if (empty($service_info))
            throw new Exception('行程信息异常');

        switch ($type) {
            case 1: // 发车

                if ($service_info['status'] != 1)
                    throw new Exception('行程状态异常');

                // 验证是否有未确认的订单
                if (M('order')->where(array('service_id' => $service_info['id'], 'status' => 0))->count())
                    throw new Exception('您有未确认的订单');
                // 修改订单状态
                if (!M('order')->where(array('service_id' => $service_info['id'], 'status' => 2))->setField('status', 4))
                    throw new Exception('系统繁忙1');
                if (!M('service')->where(array('id' => $service_info['id']))->setField('start_time', time()))
                    throw new Exception('系统繁忙1');
                $service_info['status'] = 2;

                break;

            case 2: // 到站

                if ($service_info['status'] != 2)
                    throw new Exception('行程状态异常');
                $service_info['status'] = 3;
                // 修改订单状态
                $sql = "update cjkc_order set status='3' where service_id='" . $service_info['id'] . "' and status='1'";

                if (!$order_model->where(array('service_id' => $service_info['id'], 'status' => 1))->setField('status', 3)) {
                    throw new Exception('系统繁忙1---1');
                }

                $orders = M('order')->alias('a')
                        ->where(array('a.service_id' => $service_info['id'], 'a.status' => 3))
                        ->field('a.id, a.pay_mothod,b.mobile')
                        ->join('__USER__ as b on a.user_id=b.id')
                        ->select();

                file_put_contents('order.log', var_export($orders, true) . '--' . '订单信息原始-' . "\r\n", FILE_APPEND);

                if (!empty($orders)) {
                    $is_dj = 0;
                    // 订单分成
                    $invite_logic = new \Home\Logic\DisfoLogic();
                    foreach ($orders as $k => $v) {
                        if ($v['pay_mothod'] == 3) {
                            $is_dj = 1;
//                            continue;
                        } else {
                            try {
                                $invite_logic->commissionByInviteRelation($v['id']);
                            } catch (\Exception $e) {
                                file_put_contents('error.log', var_export($e->getMessage(), true) . '--' . '回款信息-' . "\r\n", FILE_APPEND);
                                throw new Exception($e->getMessage());
                                exit;
                            }
                        }
                    }
                }
                $drivermobile = M('driver')->where(array('id' => $service_info['driver_id']))->getField('mobile');

                try {
                    foreach ($orders as $val) {
                        \Base\Logic\PubLogic::pushMessage('user', 'user' . $val['mobile'], '订单已完成，赶快去评论吧', array('state' => 7, 'service_id' => $service_id));
                    }
                } catch (Think\Exception $e) {
                    throw new Exception($e->getMessage());
                }
                if (!M('service')->where(array('id' => $service_info['id']))->setField('stop_time', time())) {
                    throw new Exception('系统繁忙1');
                }

                break;

            case 3: // 司机取消行程

                if ($service_info['status'] != 1)
                    throw new Exception('行程状态异常');

                // 获取已付款，非现金支付

                break;

            default:
                throw new Exception('类型异常');
        }

        //3. 修改状态

        F('s3$service_info', $service_info);


        if ($is_dj) {
            // 行程单 待回款状态
            $service_info['dj_status'] = 1;
            try {
                $invite_logic->commissionByHkd($service_info['id']);
            } catch (\Exception $e) {
                file_put_contents('error.log', var_export($e->getMessage(), true) . '--' . '回款信息-' . "\r\n", FILE_APPEND);
                throw new Exception($e->getMessage());
                exit;
            }

            // 创建回款单
            list($order_sn, $money) = ServiceLogic::addDkOrder($service_id);
        } else {
            $service_info['dj_status'] = 0;
        }
        if ($service_model->save($service_info) === false)
            throw new Exception('系统繁忙2');

        $service_info['start_city'] = M('region')->where(array('id' => $service_info['start_city']))->getField('name');
        $service_info['start_point'] = M('region')->where(array('id' => $service_info['start_point']))->getField('name');
        $service_info['purpose_city'] = M('region')->where(array('id' => $service_info['purpose_city']))->getField('name');
        $service_info['purpose_point'] = M('region')->where(array('id' => $service_info['purpose_point']))->getField('name');
        $service_model->commit();
        return $service_info;
    }

    /**
     * 修改行程状态
     * @param $service_id
     * @return bool
     * @throws Exception
     */
    static public function updateStatus_old($service_id, $type) {
        $service_model = new \Home\Model\ServiceModel();
        $order_model = new \Home\Model\OrderModel();

        //1. 获取该服务单信息
        $service_info = $service_model
                ->field('id, status, driver_id,start_city, start_point, purpose_city, purpose_point')
                ->where(array('id' => $service_id))
                ->find();

        if (empty($service_info))
            throw new Exception('行程信息异常');

        switch ($type) {
            case 1: // 发车

                if ($service_info['status'] != 1)
                    throw new Exception('行程状态异常');

                // 验证是否有未确认的订单
                if (M('order')->where(array('service_id' => $service_info['id'], 'status' => 0))->count())
                    throw new Exception('您有未确认的订单');
                // 修改订单状态
                if (!M('order')->where(array('service_id' => $service_info['id'], 'status' => 2))->setField('status', 4))
                    throw new Exception('系统繁忙1');
                if (!M('service')->where(array('id' => $service_info['id']))->setField('start_time', time()))
                    throw new Exception('系统繁忙1');
                $service_info['status'] = 2;

                break;

            case 2: // 到站

                if ($service_info['status'] != 2)
                    throw new Exception('行程状态异常');
                $service_info['status'] = 3;
                // 修改订单状态
                $sql = "update cjkc_order set status='3' where service_id='" . $service_info['id'] . "' and status='1'";

                if (!$order_model->where(array('service_id' => $service_info['id'], 'status' => 1))->setField('status', 3)) {
                    throw new Exception('系统繁忙1---1');
                }

                $orders = M('order')->alias('a')
                        ->where(array('a.service_id' => $service_info['id'], 'a.status' => 3))
                        ->field('a.id, a.pay_mothod,b.mobile')
                        ->join('__USER__ as b on a.user_id=b.id')
                        ->select();

                file_put_contents('order.log', var_export($orders, true) . '--' . '订单信息原始-' . "\r\n", FILE_APPEND);

                if (!empty($orders)) {
                    $is_dj = 0;
                    // 订单分成
                    foreach ($orders as $k => $v) {
                        if ($v['pay_mothod'] == 3) {
                            $is_dj = 1;
                            continue;
                        }
                        //InviteLogic::commissionByInviteRelation($v['id']);
                    }

                    if ($is_dj) {
                        // 行程单 待回款状态
                        $service_info['dj_status'] = 1;

                        // 创建回款单
                        list($order_sn, $money) = ServiceLogic::addDkOrder($service_id);
                    }
                }
                $drivermobile = M('driver')->where(array('id' => $service_info['driver_id']))->getField('mobile');
//                if(!empty($drivermobile)){
//                    \Base\Logic\PubLogic::pushMessage('driver','driver'.$drivermobile, '订单已完成，赶快去评论吧', array('state'=>7,'service_id'=>$service_id));
//                }

                try {
                    foreach ($orders as $val) {
                        \Base\Logic\PubLogic::pushMessage('user', 'user' . $val['mobile'], '订单已完成，赶快去评论吧', array('state' => 7, 'service_id' => $service_id));
                    }
                } catch (Think\Exception $e) {
                    
                }
                if (!M('service')->where(array('id' => $service_info['id']))->setField('stop_time', time()))
                    throw new Exception('系统繁忙1');


                break;

            case 3: // 司机取消行程

                if ($service_info['status'] != 1)
                    throw new Exception('行程状态异常');

                // 获取已付款，非现金支付

                break;

            default:
                throw new Exception('类型异常');
        }

        //3. 修改状态
        F('s3$service_info', $service_info);
        if ($service_model->save($service_info) === false)
            throw new Exception('系统繁忙2');

        $service_info['start_city'] = M('region')->where(array('id' => $service_info['start_city']))->getField('name');
        $service_info['start_point'] = M('region')->where(array('id' => $service_info['start_point']))->getField('name');
        $service_info['purpose_city'] = M('region')->where(array('id' => $service_info['purpose_city']))->getField('name');
        $service_info['purpose_point'] = M('region')->where(array('id' => $service_info['purpose_point']))->getField('name');

        return $service_info;
    }

    /**
     * 创建行程现付回款单
     * @param $service_id 行程ID
     * @return mixed
     * @throws Exception
     */
    static public function addDkOrder($service_id) {
        //1. 验证行程单信息
        $service_info = M('service')->where(array('id' => $service_id))->field('id, dj_status')->find();

        if (empty($service_info))
            throw new Exception('行程单信息异常');

        //2. 获取行程中的现付订单信息
        $moneys = M('order')->where(array('service_id' => $service_id, 'status' => 3, 'pay_mothod' => 3))->getField('money', true);

        if (empty($moneys))
            throw new Exception('现付订单信息异常');

        $data['money'] = 0;

        $driver_rate = M('set')->where(array('id' => 1))->getField('driver_rate');

        //3. 添加行程现付回款单
        foreach ($moneys as $k => $v)
            $data['money'] += floatval($v);

        $data['money'] = $data['money'] * (1 - floatval($driver_rate) / 100);
        $data['server_id'] = $service_id;
        $data['order_sn'] = makeOrder_sn();
        $data['create_time'] = NOW_TIME;
        if (!M('service_dj')->add($data))
            throw new Exception('系统繁忙4');

        return [$data['order_sn'], $data['money']];
    }

    /**
     * 行程评分
     * @param $service_id 行程ID
     * @param $user_id 乘客ID
     * @param $score 分数
     * @return bool
     * @throws Exception
     */
    static public function comment($service_id, $user_id, $score, $type = 1, $contents = '') {
        $score = intval($score);

        //1. 验证行程信息
        $service_info = M('service')->where(array('id' => $service_id, 'status' => 3))->field('id, driver_id')->find();
        if (empty($service_info))
            throw new Exception('行程信息异常');
        $driver_id = $service_info['driver_id'];

        //2. 验证该用户是否具有评分资格
        $order_id = M('order')->where(array('user_id' => $user_id, 'service_id' => $service_id, 'status' => 3))->getField('id');
        if (!$order_id)
            throw new Exception('没有评分资格');

        $comment_model = M('comment');

        if ($comment_model->where(array('service_id' => $service_id, 'order_id' => $order_id, 'type' => $type))->count())
            throw new Exception('请勿重复评分');

        //3. 添加评分信息
        if (!$score)
            throw new Exception('请选择分数');
        $data['service_id'] = $service_id;
        $data['driver_id'] = $driver_id;
        $data['order_id'] = $order_id;
        $data['user_id'] = $user_id;
        $data['score'] = $score;
        $data['create_time'] = NOW_TIME;
        $data['type'] = $type;
        $data['contents'] = $contents;
        if (!$comment_model->add($data))
            throw new Exception('系统繁忙');

        return true;
    }

    /*     * ************************************************ getDta **************************************************** */

    static public function getOrdersByService($service_id) {
        $orders = M('order')->alias('a')->join('__USER__ AS b ON a.user_id = b.id', 'INNER')
                        ->where(array('a.service_id' => $service_id, 'a.status' => 3))
                        ->field('a.id AS order_id, a.seat_num, b.head_pic, b.nick_name,b.invite_name,b.head_pic')->select();

        if (!empty($orders)) {
            foreach ($orders as $k => $v) {
                $orders[$k]['head_pic'] = $v['head_pic'] ? getPicUrl($v['head_pic']) : '';
            }
        }

        return $orders;
    }

    static public function getList($map = array(), $size = 20) {
        $field = '*';

        $model = M('service');

        $count = $model->alias('a')->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')->where($map)->count();

        // 开启分页类
        $page = new \Think\Page($count, $size);

        // 获取分页显示
        $fpage = $count > $size ? $page->Show() : '';

        $list = $model->alias('a')->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')
                ->join('__DRIVER__ as c on c.id=a.driver_id')
                ->where($map)
                ->field('a.*, b.true_name, c.invite_name,b.license_sn, b.head_pic')
                ->limit("{$page->firstRow}, {$page->listRows}")
                ->order('a.id desc')
                ->select();

        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['departur_day'] = date('Y-m-d', strtotime($v['departur_day']));
                $v['start_city'] = M('region')->where(['id' => $v['start_city']])->getField('name');
                $v['start_point'] = M('region')->where(['id' => $v['start_point']])->getField('name');
                $v['purpose_city'] = M('region')->where(['id' => $v['purpose_city']])->getField('name');
                $v['purpose_point'] = M('region')->where(['id' => $v['purpose_point']])->getField('name');
            }
        }
        $all = $model->alias('a')->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')
                        ->join('__DRIVER__ as c on c.id=a.driver_id')
                        ->where($map)->field('a.status')->select();

        return array($list, $fpage, $count, $all);
    }

    /**
     * 获取服务列表信息ByApi
     * @param $map 条件数组
     * @param $ord 排序字符
     * @param $size 查询数量
     * @param $page 页面
     * @return array
     */
    static public function getServiceList($map, $ord, $size, $page) {

        $model = M('service');

        $field = 'a.id, a.order_sn, a.driver_id, a.start_city, a.start_point, a.purpose_city, a.purpose_point, a.type,a.new_frompoint,a.ser_fromlng,a.ser_fromlat,a.new_topoint,a.ser_tolng,a.ser_tolat';
        $field .= ' ,a.departur_time, a.is_yy, a.price, a.block, b.true_name, b.license_sn, b.head_pic, b.driver_id,b.type_id,b.car_id,b.colour_id,brand.brand_name,type.brand_type,color.colours,car.car_type';

        $map['a.status'] = 1;
        $map['a.block'] = array('neq', 0);
        $map['a.departur_day'] = date('Ymd'); // 获取当天订单

        $count = $model->alias('a')->where($map)->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')
                        ->join('__BRAND__ as brand on b.driver_id = brand.id', 'INNER')
                        ->join('__TYPE__ as type on b.type_id = type.id', 'INNER')
                        ->join('__COLOUR__ as color on b.colour_id = color.id', 'INNER')
                        ->join('__CAR__ as car on b.car_id = car.id', 'INNER')->count();

        $offset = ($page * $size) - $size;

        $list = $model->alias('a')
                ->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')
                ->join('__BRAND__ as brand on b.driver_id = brand.id', 'LEFT')
                ->join('__TYPE__ as type on b.type_id = type.id', 'LEFT')
                ->join('__COLOUR__ as color on b.colour_id = color.id', 'LEFT')
                ->join('__CAR__ as car on b.car_id = car.id', 'LEFT')
                ->where($map)
                ->field($field)
                ->order($ord)
                ->limit($offset . ',' . $size)
                ->select();


        if (!empty($list)) {
            $region_model = M('region');

            foreach ($list as $k => &$v) {
                $v['departur_time'] = $v['departur_time'] ? date('Y-m-d H:i:s', $v['departur_time']) : '-';
                $v['head_pic'] = $v['head_pic'] ? getPicUrl($v['head_pic']) : getPicUrl('./Public/default/images/ic_default_avator.png');
                $v['start_city'] = $region_model->where(array('id' => $v['start_city']))->getField('name');
                $v['start_point'] = $region_model->where(array('id' => $v['start_point']))->getField('name');
                $v['purpose_city'] = $region_model->where(array('id' => $v['purpose_city']))->getField('name');
                $v['purpose_point'] = $region_model->where(array('id' => $v['purpose_point']))->getField('name');
            }
        }

        $pageTotal = ceil($count / $size);

        return array($list, intval($count), $page, $pageTotal);
    }

    /**
     * 获取司机服务订单信息
     * @param $service_id
     * @return mixed
     * @throws Exception
     */
    static public function getServiceByOrder($service_id) {
        $service_model = new \Home\Model\ServiceModel();

        $field = 'a.id as service_id, a.start_point, a.purpose_point, a.type, a.block, a.departur_time';
        $field .= ' ,b.license_sn, b.car_load_num';

        $info = $service_model->alias('a')->join('__DRIVER_INFO__ as b on a.driver_id = b.id', 'INNER')
                ->field($field)
                ->where(array('a.id' => $service_id))
                ->find();

        unset($service_model);

        if (empty($info))
            throw new Exception('服务信息异常');

        $region_model = M('region');

        $start_point = $region_model->field('x,y')->find($info['start_point']);
        $info['start_point_x'] = $start_point['x'];
        $info['start_point_y'] = $start_point['y'];

        $purpose_point = $region_model->field('x,y')->find($info['purpose_point']);
        $info['purpose_point_x'] = $purpose_point['x'];
        $info['purpose_point_y'] = $purpose_point['y'];

        $info['departur_time'] = !empty($info['departur_time']) ? date('Y-m-d H:i', $info['departur_time']) : null;

        unset($region_model, $info['start_point'], $info['purpose_point']);

        return $info;
    }

    /**
     * 获取司机未完结的行程ID
     * @param $driver_id
     * @return mixed
     */
    static public function getServiceByCurrentDay($driver_id, $is_yy = 1) {
        $service_model = new \Home\Model\ServiceModel();

        // 验证司机信息
        if (!M('driver')->where(array('id' => $driver_id))->count())
            throw new Exception('司机信息异常');

        $map['driver_id'] = $driver_id;
        $map['status'] = array('in', '1,2');
        $map['is_yy'] = $is_yy;

        $service_info = $service_model->where($map)->field('id, status AS depart_status')->find();

        unset($service_model);

        return array($service_info['id'], (int) $service_info['depart_status']);
    }

    /**
     * 获取行程详情
     * @param $service_id
     * @return mixed
     */
    static public function getServiceInfo($service_id) {
        $service_model = new \Home\Model\ServiceModel();

        $field = 'a.id, a.dj_status, a.order_sn, a.link_phone, a.remark, a.start_city, a.start_point, a.purpose_city, a.purpose_point, a.type';
        $field .= ' ,a.is_yy, a.price, a.block, a.status, a.departur_time, a.create_time, b.license_sn, b.car_load_num,a.stop_time,a.start_time';

        //获取司机行程信息
        $info = $service_model->alias('a')->join('__DRIVER_INFO__ AS b ON a.driver_id = b.id', 'INNER')
                ->where(array('a.id' => $service_id))
                ->field($field)
                ->find();

        if (empty($info))
            throw new Exception('行程信息异常');

        $info['start_city'] = M('region')->where(array('id' => $info['start_city']))->getField('name') ? : '-';
        $info['purpose_city'] = M('region')->where(array('id' => $info['purpose_city']))->getField('name') ? : '-';
        $info['start_point'] = M('region')->where(array('id' => $info['start_point']))->getField('name') ? : '-';
        $info['purpose_point'] = M('region')->where(array('id' => $info['purpose_point']))->getField('name') ? : '-';
        $info['license_sn'] = $info['license_sn'];

        $order_model = new \Home\Model\OrderModel();

        $info['order_list'] = $order_model->alias('a')->join('__USER__ as b ON a.user_id = b.id', 'INNER')
                ->join('__PAY__ as c on a.order_sn=c.order_sn', 'LEFT')
                ->where(array('a.service_id' => $info['id']))
                ->order('a.create_time')
                ->field('c.pay_time,a.id,a.order_sn, a.seat_num, a.money, a.pay_mothod, a.status, a.create_time, a.remark, b.mobile, b.nick_name')
                ->select();

        return $info;
    }

    /**
     * 获取司机未付款的汇款单信息
     * @param $dirver_id 司机ID
     * @return mixed
     * @throws Exception
     */
    static public function getDriverHkdInfo($dirver_id) {
        //1. 验证司机信息
        if (!M('driver')->where(['id' => $dirver_id])->count())
            throw new Exception('司机信息异常');

        //2. 获取该司机未打款的汇款单
        $info = M('service')->alias('a')
                ->join('__DRIVER__ AS b ON a.driver_id = b.id', 'INNER')
                ->join('__SERVICE_DJ__ AS c ON a.id = c.server_id', 'INNER')
                ->where(array('b.id' => $dirver_id, 'c.status' => 0))
                ->field('c.id AS order_id, c.money, c.order_sn, c.server_id')
                ->find();

        if (!empty($info)) {
            foreach ($info as $k => $v) {
                $info[$k]['order_id'] = intval($v['order_id']);
                $info[$k]['server_id'] = intval($v['order_id']);
                $info[$k]['money'] = floatval($v['money']);
            }
        }

        return $info;
    }

}
