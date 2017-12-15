<?php

namespace Home\Logic;

use Think\Exception;
use Base\Logic\PubLogic;

class DisfoLogic {

    /**
     * 订单分成（依据提成比例）
     * @param $order_id 乘客订单ID
     * @return bool
     */
    static public function commissionByInviteRelation($order_id) {
        $bill_obj = new \Common\Controller\BillController();

        //1. 查询订单信息
        $order_info = M('order')->where(['id' => $order_id])->field('id, user_id, service_id, money,order_sn')->find();

        file_put_contents('order.log', var_export($order_info, true) . '--' . '订单信息-' . "\r\n", FILE_APPEND);

        if (empty($order_info))
            throw new Exception('订单信息异常');

        //3. 获取提成比例
        $set = M('set')->field('driver_rate, one_rate, two_rate, three_rate, terrace_rate, income,company_rate')->find();

        file_put_contents('order.log', var_export($set, true) . '--' . '提成比例-' . "\r\n", FILE_APPEND);

        if (empty($set))
            throw new Exception('系统配置异常');

        //4. 获取司机信息
        $driver_id = M('service')->where(array('id' => $order_info['service_id']))->getField('driver_id');

        file_put_contents('order.log', var_export($driver_id, true) . '--' . '司机信息000-' . "\r\n", FILE_APPEND);

        $invite_code_driver = M('driver')->where(array('id' => $driver_id))->getField('invite_code');

        file_put_contents('order.log', var_export($invite_code_driver, true) . '--' . '司机信息111-' . "\r\n", FILE_APPEND);

        $driver_info = M('driver')->field('id, income')->where(array('id' => $driver_id))->find();

        if (empty($driver_info))
            throw new Exception('司机信息异常');

        file_put_contents('order.log', var_export($driver_info, true) . '--' . '司机信息-' . "\r\n", FILE_APPEND);

        //5. 获取各项金额
        $order_money = floatval($order_info['money']); // 订单金额

        $driver_rate = floatval($set['driver_rate']); // 司机提成比例
        $driver_get_money = ($order_money * $driver_rate) / 100; // 司机所得金额
        $sy_money = $comm_money = $order_money - $driver_get_money; // 待抽成金额,剩余金额 初始相等

        file_put_contents('order.log', var_export($driver_get_money, true) . '--' . '司机所得金额-' . "\r\n", FILE_APPEND);

        //6. 司机收入添加,流水记录添加
        $driver_info['income'] = floatval($driver_info['income']) + $driver_get_money;

        if (M('driver')->save($driver_info) === false)
            throw new Exception('系统异常');

        $user_info = M('user')->field('id, income')->where(array('invite_code' => $invite_code_driver))->find();
        file_put_contents('order.log', var_export($user_info, true) . '--' . '司机对应乘客信息-' . "\r\n", FILE_APPEND);
        if ($user_info) {
            if (M('user')->where(array('invite_code' => $invite_code_driver))->setField('income', $driver_info['income']) === false) {
                throw new Exception('统一乘客信息失败');
            }
        }

        // 添加司机入账流水
        $driver_get_money = $driver_get_money * 100;

        $res = $bill_obj::write($driver_info['id'], $driver_get_money, '快车服务', 1, $order_info['order_sn'], $invite_code_driver);

        if ($res['errCode'])
            throw new Exception($res['msg']);

        //获取司机挂靠公司信息
        file_put_contents('order.log', var_export('1111', true) . '--' . '提成比例0000-' . "\r\n", FILE_APPEND);

        if ($set['company_rate'] != 0.00) {
            file_put_contents('order.log', var_export('222', true) . '--' . '提成比例2222-' . "\r\n", FILE_APPEND);
            $company_id = M('driver_info')->where(array('id' => $driver_id))->getField('company_id');
            $invite_code_company = M('company')->where(['id' => $company_id])->getField('invite_code');
            if (empty($company_id))
                throw new Exception('所属挂靠公司信息异常');

            file_put_contents('order.log', var_export($company_id, true) . '--' . '司机挂靠公司信息-' . "\r\n", FILE_APPEND);

            //挂靠公司分成(车)
            $company_rate = $set['company_rate'];
            $company_get_money = ($sy_money * $company_rate) / 100;

            file_put_contents('order.log', var_export($company_get_money, true) . '--' . '司机挂靠公司所得金额-' . "\r\n", FILE_APPEND);

            $res = $bill_obj::write($company_id, $company_get_money * 100, '公司挂靠', 3, $order_info['order_sn'], $invite_code_company);
            if ($res['errCode'])
                throw new Exception($res['msg']);

            $company_income = M('company')->where(array('id' => $company_id))->getField('income');
            $company_money['income'] = $company_income + $company_get_money;
            if (M('company')->where(array('id' => $company_id))->save($company_money) === false)
                throw new Exception('挂靠公司分成失败');
        }


        //2. 验证该用户是否被推荐
        $user_info = M('user')->where(['id' => $order_info['user_id']])->field('id, is_invite,invite_type,invite_from_code,company_id,invite_code,invite_code_old')->find();

        file_put_contents('order.log', var_export($user_info, true) . '--' . '用户信息-' . "\r\n", FILE_APPEND);

        if (empty($user_info))
            throw new Exception('用户信息异常');

        file_put_contents('order.log', var_export(['111', '222'], true) . '--' . '111-' . "\r\n", FILE_APPEND);

        //公司分销
        if ($set['three_rate'] != 0.00) {

            if ($user_info['company_id']) {
                file_put_contents('order.log', var_export(['111', '222'], true) . '--' . '222-' . "\r\n", FILE_APPEND);
                $company_income_first = M('company')->where(['id' => $user_info['company_id']])->getField('income');
                $company_invite_code_first = M('company')->where(['id' => $user_info['company_id']])->getField('invite_code');
                //租赁公司提成
                $company_sales_rate = $set['three_rate'];

                $company_sales_money = ($sy_money * $company_sales_rate) / 100;

                file_put_contents('order.log', var_export($company_sales_money, true) . '--' . '租赁公司提成-' . "\r\n", FILE_APPEND);

                $res = $bill_obj::write($user_info['company_id'], $company_sales_money * 100, '租赁公司提成', 3, $order_info['order_sn'], $company_invite_code_first);
                if ($res['errCode'])
                    throw new Exception($res['msg']);

                $company_income_end['income'] = $company_income_first + $company_sales_money;
                if (M('company')->where(array('id' => $user_info['company_id']))->save($company_income_end) === false)
                    throw new Exception('租赁公司提成失败');
            }
        }


        // 有推荐人
        if ($user_info['is_invite']) {
            file_put_contents('order.log', var_export(['111', '222'], true) . '--' . '444-' . "\r\n", FILE_APPEND);
            //7. 获取该用户的邀请人信息
            $invite_info = DisfoLogic::distriRelation($user_info['invite_code'], $user_info['invite_code_old']);
            file_put_contents('order.log', var_export(['111', '222'], true) . '--' . '555-' . "\r\n", FILE_APPEND);
            if (empty($invite_info['first']))
                throw new Exception('推荐人信息异常');

            if (empty($set['one_rate']))
                throw new Exception('一级分销百分比不能为0');

            $first = $invite_info['first']; //一级邀请人信息
            $two = $invite_info['two']; //二级邀请人信息

            $first_rate = floatval($set['one_rate']) / 100;
            //一级分销
            if (is_array($first) && $first['type'] != 3) {
                file_put_contents('order.log', var_export($first, true) . '--' . '一级分销信息-' . "\r\n", FILE_APPEND);
                $tc_first_money = $comm_money * $first_rate;

                $tj_u_info['income'] = floatval($first['income']) + $tc_first_money;

                $type = $first['type'];
                switch ($type) {
                    case 1://只注册司机
                        $invite_info_code = M('driver')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('driver')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;
                    case 2://只注册乘客
                        $invite_info_code = M('user')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('user')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;

                    case 3://只注册乘客

                        $invite_info_code = M('company')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('company')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;


                    case 4://司机乘客都有信息
                        $invite_info_code = M('driver')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('driver')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        if (M('user')->where(['invite_code' => $first['invite_code']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;

                    default:
                        throw new Exception('一级邀请人类型异常');
                        break;
                }
                file_put_contents('order.log', var_export($tc_first_money, true) . '--' . '一级分销金额-' . "\r\n", FILE_APPEND);

                // 分成流水添加
                $tc_money = $tc_first_money * 100;
                $res = $bill_obj::write($first['id'], $tc_money, '一级分销奖励', $type, $order_info['order_sn'], $invite_info_code);

                if ($res['errCode'])
                    throw new Exception($res['msg']);
            }

            //二级分销
            if ($set['two_rate'] != 0.00) {

                file_put_contents('order.log', var_export($two, true) . '--' . '二级分销信息-' . "\r\n", FILE_APPEND);

                if (is_array($two) && $first['is_invite'] == 1 && $two['type'] != 3) {

                    $two_rate = floatval($set['two_rate']) / 100;
                    $tc_two_money = $comm_money * $two_rate;
                    $tj_u_info_two['income'] = floatval($two['income']) + $tc_two_money;

                    $type = $two['type'];
                    switch ($type) {
                        case 1://只注册司机
                            $invite_info_code_two = M('driver')->where(['id' => $two['id']])->getField('invite_code');
                            //$invite_info_code_two = M('driver')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('driver')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;
                        case 2://只注册乘客
                            $invite_info_code_two = M('user')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('user')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;

                        case 3://公司
                            $invite_info_code = M('company')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('company')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新一级邀请人余额失败');
                            break;

                        case 4://司机乘客都有信息
                            $invite_info_code_two = M('driver')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('driver')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            if (M('user')->where(['invite_code' => $two['invite_code']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;

                        default:
                            throw new Exception('二级邀请人类型异常');
                            break;
                    }

                    // 分成流水添加
                    $tc_money_two = $tc_two_money * 100;

                    file_put_contents('order.log', var_export($tc_two_money, true) . '--' . '二级分销金额-' . "\r\n", FILE_APPEND);

                    $res = $bill_obj::write($two['id'], $tc_money_two, '二级分销奖励', $type, $order_info['order_sn'], $invite_info_code_two);
                    if ($res['errCode'])
                        throw new Exception($res['msg']);
                }
            }


            $sy_money = $sy_money - $company_get_money - $company_sales_money - $tc_first_money - $tc_two_money;
        }
        file_put_contents('order.log', var_export($sy_money, true) . '--' . '剩余金额-' . "\r\n", FILE_APPEND);
        //9. 平台提成
        $pt_income = floatval($set['income']) + $sy_money;

        file_put_contents('order.log', var_export($sy_money, true) . '--' . '平台提成-' . "\r\n", FILE_APPEND);

        $res = $bill_obj::platform($sy_money * 100, '平台收入', $order_info['order_sn']);
        if ($res['errCode'])
            throw new Exception($res['msg']);

        if (M('set')->where(array('id' => 1))->setField('income', $pt_income) === false)
            throw new Exception('系统繁忙');

        return true;
    }

    /**
     * 现付订单，司机回款（分成）
     * @param $money
     * @param $service_id 司机订单表id(service表)
     * @return bool
     */
    static public function commissionByHkd($service_id) {
        $service_model = new \Home\Model\ServiceModel();

        //1. 获取行程单信息
        $service_info = $service_model->field('id, status, dj_status,driver_id')->where(array('id' => $service_id))->find();

        $company_id = M('driver_info')->where(['id' => $service_info['driver_id']])->getField('company_id');

        $company_invite_code = M('company')->where(['id' => $company_id])->getField('invite_code');

        file_put_contents('order1.log', var_export($service_info, true) . '--' . '判断回款-' . "\r\n", FILE_APPEND);

        if (empty($service_info))
            throw new Exception('行程单信息异常');
        if ($service_info['status'] != 3)
            throw new Exception('行程单状态异常');
        if ($service_info['dj_status'] != 1)
            throw new Exception('回款状态异常');
        if (empty($company_id))
            throw new Exception('司机挂靠公司信息异常');

        //2. 获取未付款订单信息
        $order_model = new \Home\Model\OrderModel();
        $user_model = new \Home\Model\UserModel();

        $orders = $order_model->where(array('pay_mothod' => 3, 'status' => 3, 'service_id' => $service_id))->field('id, user_id, money,order_sn')->select();

        file_put_contents('order1.log', var_export($orders, true) . '--' . '判断回款order-' . "\r\n", FILE_APPEND);
        if (empty($orders))
            throw new Exception('现付订单信息异常');

        //3. 按比例分成
        $set = M('set')->field('one_rate, two_rate, three_rate,company_rate,income,driver_rate')->find();

        $bill_obj = new \Common\Controller\BillController();

        foreach ($orders as $k => $v) {
            // 获取待分成金额
            $sy_money = $comm_money = $money = floatval($v['money']) - ( floatval($v['money']) * (floatval($set['driver_rate']) / 100));


            //4. 获取司机信息
            $driver_id = M('service')->where(array('id' => $service_id))->getField('driver_id');
            $invite_code_driver = M('driver')->where(array('id' => $driver_id))->getField('invite_code');
            $driver_info = M('driver')->field('id, income')->where(array('id' => $driver_id))->find();

            if (empty($driver_info))
                throw new Exception('司机信息异常');

            file_put_contents('order1.log', var_export($driver_info, true) . '--' . '司机信息-' . "\r\n", FILE_APPEND);

            //5. 获取各项金额
            $driver_get_money = (( floatval($v['money']) * floatval($set['driver_rate'])) / 100); // 司机所得金额

            file_put_contents('order1.log', var_export($driver_get_money, true) . '--' . '司机所得金额-' . "\r\n", FILE_APPEND);

            //6. 司机收入添加,流水记录添加
            $driver_info['income'] = floatval($driver_info['income']) + $driver_get_money;

            if (M('driver')->save($driver_info) === false)
                throw new Exception('系统异常');

            $user_info = M('user')->field('id, income')->where(array('invite_code' => $invite_code_driver))->find();
            file_put_contents('order.log', var_export($user_info, true) . '--' . '司机对应乘客信息-' . "\r\n", FILE_APPEND);
            if ($user_info) {
                if (M('user')->where(array('invite_code' => $invite_code_driver))->setField('income', $driver_info['income']) === false) {
                    throw new Exception('统一乘客信息失败');
                }
            }
            $res = $bill_obj::write($driver_info['id'], $driver_get_money * 100, '快车服务', 1, $v['order_sn'], $invite_code_driver);

            if ($money <= 0)
                continue;

            //挂靠公司分成(车)
            if ($set['company_rate'] != 0.00) {

                $company_rate = $set['company_rate'];
                $company_get_money = ($sy_money * $company_rate) / 100;

                file_put_contents('order1.log', var_export($company_get_money, true) . '--' . '司机挂靠公司所得金额-' . "\r\n", FILE_APPEND);

                $res = $bill_obj::write($company_id, $company_get_money * 100, '公司挂靠', 3, $v['order_sn'], $company_invite_code);

                file_put_contents('order1.log', var_export($res, true) . '--' . '司机挂靠公司所得金额00000-' . "\r\n", FILE_APPEND);

                if ($res['errCode'])
                    throw new Exception($res['msg']);

                $company_income = M('company')->where(array('id' => $company_id))->getField('income');
                $company_money['income'] = $company_income + $company_get_money;
                if (M('company')->where(array('id' => $company_id))->save($company_money) === false)
                    throw new Exception('公司分成失败');
            }




            //2. 验证该用户是否被推荐
            $user_info = M('user')->where(['id' => $v['user_id']])->field('id,invite_code, is_invite,invite_type,invite_from_code,company_id,invite_code_old')->find();

            file_put_contents('order1.log', var_export($user_info, true) . '--' . '用户信息000-' . "\r\n", FILE_APPEND);

            $company_invite_code_yq = M('company')->where(['id' => $user_info['company_id']])->getField('invite_code');
            file_put_contents('order1.log', var_export($company_invite_code_yq, true) . '--' . '用户信息111-' . "\r\n", FILE_APPEND);
            //file_put_contents('order1.log', var_export($user_info, true) . '--' . '用户信息-' . "\r\n", FILE_APPEND);

            if (empty($user_info))
                throw new Exception('用户信息异常');

            //公司分销
            if ($set['three_rate'] != 0.00) {

                if ($user_info['company_id']) {
                    $company_income_first = M('company')->where(['id' => $user_info['company_id']])->getField('income');
                    //租赁公司提成
                    $company_sales_rate = $set['three_rate'];

                    $company_sales_money = ($sy_money * $company_sales_rate) / 100;

                    file_put_contents('order1.log', var_export($company_sales_money, true) . '--' . '分销公司提成-' . "\r\n", FILE_APPEND);

                    $res = $bill_obj::write($user_info['company_id'], $company_sales_money * 100, '租赁公司提成', 3, $v['order_sn'], $company_invite_code_yq);
                    if ($res['errCode'])
                        throw new Exception($res['msg']);

                    $company_income_end['income'] = $company_income_first + $company_sales_money;
                    if (M('company')->where(array('id' => $user_info['company_id']))->save($company_income_end) === false)
                        throw new Exception('租赁公司提成失败');
                }
            }


            // 有推荐人
            if ($user_info['is_invite']) {
                //7. 获取该用户的上级推荐人
                $invite_info = DisfoLogic::distriRelation($user_info['invite_code'], $user_info['invite_code_old']);

                if (empty($invite_info['first']))
                    throw new Exception('推荐人信息异常');

                if (empty($set['one_rate']))
                    throw new Exception('一级分销百分比不能为0');

                $first = $invite_info['first']; //一级邀请人信息
                $two = $invite_info['two']; //二级邀请人信息

                $first_rate = floatval($set['one_rate']) / 100;

                //一级分销
                if (is_array($first) && $first['type'] != 3) {
                    file_put_contents('order1.log', var_export($first, true) . '--' . '一级分销信息-' . "\r\n", FILE_APPEND);
                    $tc_first_money = $comm_money * $first_rate;

                    $tj_u_info['income'] = floatval($first['income']) + $tc_first_money;

                    $type = $first['type'];
                    switch ($type) {
                        case 1://只注册司机
                            $first_invite = M('driver')->where(['id' => $first['id']])->getField('invite_code');
                            if (M('driver')->where(['id' => $first['id']])->save($tj_u_info) === false)
                                throw new Exception('更新一级邀请人余额失败');
                            break;
                        case 2://只注册乘客
                            $first_invite = M('user')->where(['id' => $first['id']])->getField('invite_code');
                            if (M('user')->where(['id' => $first['id']])->save($tj_u_info) === false)
                                throw new Exception('更新一级邀请人余额失败');
                            break;

                        case 3://只注册乘客
                            $first_invite = M('company')->where(['id' => $first['id']])->getField('invite_code');
                            if (M('company')->where(['id' => $first['id']])->save($tj_u_info) === false)
                                throw new Exception('更新一级邀请人余额失败');
                            break;

                        case 4://司机乘客都有信息
                            $first_invite = M('driver')->where(['id' => $first['id']])->getField('invite_code');
                            if (M('driver')->where(['id' => $first['id']])->save($tj_u_info) === false)
                                throw new Exception('更新一级邀请人余额失败');
                            if (M('user')->where(['invite_code' => $first['invite_code']])->save($tj_u_info) === false)
                                throw new Exception('更新一级邀请人余额失败');
                            break;

                        default:
                            throw new Exception('一级邀请人类型异常');
                            break;
                    }
                    file_put_contents('order1.log', var_export($tc_first_money, true) . '--' . '一级分销金额-' . "\r\n", FILE_APPEND);

                    // 分成流水添加
                    $tc_money = $tc_first_money * 100;
                    $res = $bill_obj::write($first['id'], $tc_money, '一级分销奖励', $type, $v['order_sn'], $first_invite);
                    if ($res['errCode'])
                        throw new Exception($res['msg']);
                }

                //二级分销
                if ($set['two_rate'] != 0.00) {
                    file_put_contents('order1.log', var_export($two, true) . '--' . '2级分销信息-' . "\r\n", FILE_APPEND);
                    if (is_array($two) && $first['is_invite'] == 1 && $two['type'] != 3) {
                        $two_rate = floatval($set['two_rate']) / 100;
                        $tc_two_money = $comm_money * $two_rate;
                        $tj_u_info_two['income'] = floatval($two['income']) + $tc_two_money;

                        $type = $first['type'];
                        switch ($type) {
                            case 1://只注册司机
                                $two_invite = M('driver')->where(['id' => $two['id']])->getField('invite_code');
                                if (M('driver')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                    throw new Exception('更新二级邀请人余额失败');
                                break;
                            case 2://只注册乘客
                                $two_invite = M('user')->where(['id' => $two['id']])->getField('invite_code');
                                if (M('user')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                    throw new Exception('更新二级邀请人余额失败');
                                break;

                            case 3://公司
                                $two_invite = M('company')->where(['id' => $two['id']])->getField('invite_code');
                                if (M('company')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                    throw new Exception('更新二级邀请人余额失败');
                                break;

                            case 4://司机乘客都有信息
                                $two_invite = M('driver')->where(['id' => $two['id']])->getField('invite_code');
                                if (M('driver')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                    throw new Exception('更新二级邀请人余额失败');
                                if (M('user')->where(['invite_code' => $two['invite_code']])->save($tj_u_info_two) === false)
                                    throw new Exception('更新二级邀请人余额失败');
                                break;

                            default:
                                throw new Exception('二级邀请人类型异常');
                                break;
                        }
                        // 分成流水添加
                        $tc_money_two = $tc_two_money * 100;

                        file_put_contents('order1.log', var_export($tc_two_money, true) . '--' . '二级分销金额-' . "\r\n", FILE_APPEND);

                        $res = $bill_obj::write($two['id'], $tc_money_two, '二级分销奖励', $type, $v['order_sn'], $two_invite);
                        if ($res['errCode'])
                            throw new Exception($res['msg']);
                    }
                }
            }
            $sy_money = $sy_money - $company_get_money - $company_sales_money - $tc_first_money - $tc_two_money;
            unset($model);
            //9. 平台提成
            $pt_income = floatval($set['income']) + $sy_money;

            file_put_contents('order1.log', var_export($sy_money, true) . '--' . '平台提成-' . "\r\n", FILE_APPEND);

            $res = $bill_obj::platform($sy_money * 100, '平台收入', $v['order_sn']);

            if (M('set')->where(array('id' => 1))->setField('income', $pt_income) === false)
                throw new Exception('系统繁忙');
        }

        return true;
    }

    /**
     * 专车订单分成（依据提成比例）
     * @param $order_id 订单ID
     * @return bool
     */
    public function VipcommissionByInviteRelation($pay) {

        $bill_obj = new \Common\Controller\BillController();

        //1. 查询专车订单信息
        $order_info = M('vipcar')->where(['order_sn' => $pay['order_sn']])->field('id, uid, driverid, pay,money')->find();

        file_put_contents('vip.log', var_export($order_info, true) . '--' . '专车订单信息-' . "\r\n", FILE_APPEND);

        if (empty($order_info))
            throw new Exception('订单信息异常');

        //3. 获取提成比例
        $set = M('set')->field('driver_rate, one_rate, two_rate, three_rate, terrace_rate, income,company_rate')->find();

        file_put_contents('vip.log', var_export($set, true) . '--' . '提成比例-' . "\r\n", FILE_APPEND);

        if (empty($set))
            throw new Exception('系统配置异常');

        //4. 获取司机信息
        $driver_id = $order_info['driverid'];
        $driver_info = M('driver')->field('id, income,invite_code')->where(array('id' => $driver_id))->find();
        if (empty($driver_info))
            throw new Exception('司机信息异常');

        file_put_contents('vip.log', var_export($driver_info, true) . '--' . '专车司机信息-' . "\r\n", FILE_APPEND);

        //5. 获取各项金额
        $order_money = floatval($order_info['money']); // 订单金额

        $driver_rate = floatval($set['driver_rate']); // 司机提成比例
        $driver_get_money = ($order_money * $driver_rate) / 100; // 司机所得金额
        $sy_money = $comm_money = $order_money - $driver_get_money; // 待抽成金额,剩余金额 初始相等

        file_put_contents('vip.log', var_export($driver_get_money, true) . '--' . '专车司机所得金额-' . "\r\n", FILE_APPEND);

        //6. 司机收入添加,流水记录添加
        $driver_info['income'] = floatval($driver_info['income']) + $driver_get_money;
        if (M('driver')->save($driver_info) === false)
            throw new Exception('系统异常');

        $user_info = M('user')->field('id, income')->where(array('invite_code' => $driver_info['invite_code']))->find();
        file_put_contents('order.log', var_export($user_info, true) . '--' . '司机对应乘客信息-' . "\r\n", FILE_APPEND);
        if ($user_info) {
            if (M('user')->where(array('invite_code' =>  $driver_info['invite_code']))->setField('income', $driver_info['income']) === false) {
                throw new Exception('统一乘客信息失败');
            }
        }

        // 添加司机入账流水
        $driver_get_money = $driver_get_money * 100;

        $res = $bill_obj::write($driver_info['id'], $driver_get_money, '专车服务', 1, $pay['order_sn'], $driver_info['invite_code']);

        if ($res['errCode'])
            throw new Exception($res['msg']);

        //获取公司信息
        $company_id = M('driver_info')->where(array('id' => $driver_id))->getField('company_id');
        $company_code = M('company')->where(['id' => $company_id])->getField('invite_code');
        if (empty($company_id))
            throw new Exception('所属公司信息异常');

        file_put_contents('vip.log', var_export($company_id, true) . '--' . '司机分成公司信息-' . "\r\n", FILE_APPEND);

        //挂靠公司分成(车)
        if ($set['company_rate'] != 0.00) {

            $company_rate = $set['company_rate'];
            $company_get_money = ($sy_money * $company_rate) / 100;

            file_put_contents('vip.log', var_export($company_get_money, true) . '--' . '司机分成公司所得金额-' . "\r\n", FILE_APPEND);

            $res = $bill_obj::write($company_id, $company_get_money * 100, '公司挂靠', 3, $pay['order_sn'], $company_code);
            if ($res['errCode'])
                throw new Exception($res['msg']);

            $company_income = M('company')->where(array('id' => $company_id))->getField('income');
            $company_money['income'] = $company_income + $company_get_money;
            if (M('company')->where(array('id' => $company_id))->save($company_money) === false)
                throw new Exception('公司分成失败');
        }


        //2. 验证该用户是否被推荐
        $user_info = M('user')->where(['id' => $order_info['uid']])->field('id, is_invite,invite_type,invite_from_code,company_id,invite_code,invite_code_old')->find();
        $company_code_tc = M('company')->where(['id' => $user_info['company_id']])->getField('invite_code');

        file_put_contents('vip.log', var_export($user_info, true) . '--' . '专车用户信息-' . "\r\n", FILE_APPEND);

        if (empty($user_info))
            throw new Exception('用户信息异常');

        //公司分销
        if ($set['three_rate'] != 0.00) {

            if ($user_info['company_id']) {
                $company_income_first = M('company')->where(['id' => $user_info['company_id']])->getField('income');
                //租赁公司提成
                $company_sales_rate = $set['three_rate'];

                $company_sales_money = ($sy_money * $company_sales_rate) / 100;

                file_put_contents('vip.log', var_export($company_sales_money, true) . '--' . '租赁公司提成-' . "\r\n", FILE_APPEND);

                $res = $bill_obj::write($user_info['company_id'], $company_sales_money * 100, '租赁公司提成', 3, $pay['order_sn'], $company_code_tc);
                if ($res['errCode'])
                    throw new Exception($res['msg']);

                $company_income_end['income'] = $company_income_first + $company_sales_money;
                if (M('company')->where(array('id' => $user_info['company_id']))->save($company_income_end) === false)
                    throw new Exception('租赁公司提成失败');
            }
        }


        // 有推荐人
        if ($user_info['is_invite']) {
            //7. 获取该用户的上级推荐人
            $invite_info = DisfoLogic::distriRelation($user_info['invite_code'], $user_info['invite_code_old']);

            if (empty($invite_info['first']))
                throw new Exception('推荐人信息异常');


            if (empty($set['one_rate']))
                throw new Exception('一级分销百分比不能为0');

            $first = $invite_info['first']; //一级邀请人信息
            $two = $invite_info['two']; //二级邀请人信息

            $first_rate = floatval($set['one_rate']) / 100;
            //一级分销
            $type = $first['type'];
            file_put_contents('vip.log', var_export($invite_info, true) . '--' . '一级分销信息-' . "\r\n", FILE_APPEND);
            if (is_array($first) && $first['type'] != 3) {
                file_put_contents('vip.log', var_export($first, true) . '--' . '一级分销信息-' . "\r\n", FILE_APPEND);
                $tc_first_money = $comm_money * $first_rate;

                $tj_u_info['income'] = floatval($first['income']) + $tc_first_money;
                file_put_contents('vip.log', var_export($tc_first_money, true) . '--' . '一级分销金额-' . "\r\n", FILE_APPEND);

                file_put_contents('vip.log', var_export($type, true) . '--' . '邀请码44-' . "\r\n", FILE_APPEND);

                switch ($type) {
                    case 1://只注册司机
                        $model = M('driver');
                        $first_code = M('driver')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('driver')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;
                    case 2://只注册乘客
                        $model = M('user');
                        $first_code = M('user')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('user')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;
                    case 3://公司
                        $model = M('company');
                        $first_code = M('company')->where(['id' => $first['id']])->getField('invite_code');

                        file_put_contents('vip.log', var_export($first_code, true) . '--' . '邀请码00-' . "\r\n", FILE_APPEND);
                        file_put_contents('vip.log', var_export(M('company')->where(['id' => $first['id']])->find(), true) . '--' . '邀请码11-' . "\r\n", FILE_APPEND);
                        file_put_contents('vip.log', var_export($tj_u_info, true) . '--' . '邀请码22-' . "\r\n", FILE_APPEND);
                        $result_first = M('company')->where(['id' => $first['id']])->save($tj_u_info);
                        file_put_contents('vip.log', var_export($result_first, true) . '--' . '邀请码33-' . "\r\n", FILE_APPEND);

                        if ($result_first === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;
                    case 4://司机乘客都有信息
                        $model = M('driver');
                        $first_code = M('driver')->where(['id' => $first['id']])->getField('invite_code');
                        if (M('driver')->where(['id' => $first['id']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        if (M('user')->where(['invite_code' => $first['invite_code']])->save($tj_u_info) === false)
                            throw new Exception('更新一级邀请人余额失败');
                        break;
                    default:
                        throw new Exception('一级邀请人类型异常');
                        break;
                }

                // 分成流水添加
                $tc_money = $tc_first_money * 100;
                $res = $bill_obj::write($first['id'], $tc_money, '一级分销奖励', $type, $pay['order_sn'], $first_code);

                file_put_contents('vip.log', var_export($res, true) . '--' . '一级分销奖励111-' . "\r\n", FILE_APPEND);

                if ($res['errCode'])
                    throw new Exception($res['msg']);

                if ($type == 3) {//link_phone
                    $mobile = $model->where(array('id' => $first['id']))->getField('link_phone');
                } else {
                    $mobile = $model->where(array('id' => $first['id']))->getField('mobile');
                }

                file_put_contents('vip.log', var_export($mobile, true) . '--' . '平台流水111手机号码-' . "\r\n", FILE_APPEND);

                if (empty($mobile))
                    throw new Exception('推荐人电话获取异常');

                $alias = $type . $mobile;
            }
            //二级分销
            if ($set['two_rate'] != 0.00) {

                file_put_contents('vip.log', var_export($two, true) . '--' . '2级分销信息-' . "\r\n", FILE_APPEND);

                if (is_array($two) && $first['is_invite'] == 1 && $two['type'] != 3) {
                    $two_rate = floatval($set['two_rate']) / 100;
                    $tc_two_money = $comm_money * $two_rate;
                    $tj_u_info_two['income'] = floatval($two['income']) + $tc_two_money;

                    $type = $first['type'];
                    switch ($type) {
                        case 1://只注册司机
                            $model = M('driver');
                            $two_code = M('driver')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('driver')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;
                        case 2://只注册乘客
                            $model = M('user');
                            $two_code = M('user')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('user')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;

                        case 3://公司
                            $two_code = M('company')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('company')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;

                        case 4://司机乘客都有信息
                            $model = M('driver');
                            $two_code = M('user')->where(['id' => $two['id']])->getField('invite_code');
                            if (M('driver')->where(['id' => $two['id']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            if (M('user')->where(['invite_code' => $two['invite_code']])->save($tj_u_info_two) === false)
                                throw new Exception('更新二级邀请人余额失败');
                            break;

                        default:
                            throw new Exception('二级邀请人类型异常');
                            break;
                    }
                    // 分成流水添加
                    $tc_money_two = $tc_two_money * 100;

                    file_put_contents('order.log', var_export($tc_two_money, true) . '--' . '二级分销金额-' . "\r\n", FILE_APPEND);

                    $res = $bill_obj::write($two['id'], $tc_money_two, '二级分销奖励', $type, $pay['order_sn'], $two_code);
                    if ($res['errCode'])
                        throw new Exception($res['msg']);
                }
            }


            $sy_money = $sy_money - $company_get_money - $company_sales_money - $tc_first_money - $tc_two_money;
        }
        file_put_contents('vip.log', var_export($sy_money, true) . '--' . '剩余金额-' . "\r\n", FILE_APPEND);
        //9. 平台提成
        $pt_income = floatval($set['income']) + $sy_money;

        file_put_contents('vip.log', var_export($sy_money, true) . '--' . '平台提成-' . "\r\n", FILE_APPEND);

        $res = $bill_obj::platform($sy_money * 100, '平台收入', $pay['order_sn']);

        file_put_contents('platform.log', var_export($res, true) . '--' . '平台流水333-' . "\r\n", FILE_APPEND);

        if (M('set')->where(array('id' => 1))->setField('income', $pt_income) === false)
            throw new Exception('系统繁忙');

        return true;
    }

    /**
     * 查询邀请关系
     * @$invite_code 被邀请人邀请码
     * @return string title 标题
     * @author string content 内容
     */
    static public function distriRelation($invite_code, $invite_code_old) {
        file_put_contents('invite.log', var_export($invite_code, true) . '--' . 'test-' . "\r\n", FILE_APPEND);
        $invite_info = M('invite')->where(['beinvite_code' => $invite_code])->field('invite_code_first,invite_code_two')->find();

        $invite_info_old = M('invite')->where(['beinvite_code' => $invite_code_old])->field('invite_code_first,invite_code_two')->find();
        file_put_contents('invite.log', var_export($invite_info, true) . '--' . '分销信息111-' . "\r\n", FILE_APPEND);
        file_put_contents('invite.log', var_export($invite_info_old, true) . '--' . '分销信息222-' . "\r\n", FILE_APPEND);

        if (empty($invite_info) && !empty($invite_info_old)) {
            $invite_info = $invite_info_old;
        }
        file_put_contents('invite.log', var_export($invite_info, true) . '--' . '分销信息333-' . "\r\n", FILE_APPEND);

        //file_put_contents('invite.log', var_export($invite_info, true) . '--' . '分销信息-' . "\r\n", FILE_APPEND);

        $first = array();
        if (!empty($invite_info)) {//有推荐人信息
            //一级推荐人信息
            $first_driver = M('driver')->where(['invite_code' => $invite_info['invite_code_first']])->field('id,is_invite,income,invite_code')->find();
            file_put_contents('invite.log', var_export($first_driver, true) . '--' . '一级分销司机信息-' . "\r\n", FILE_APPEND);
            $first_user = M('user')->where(['invite_code' => $invite_info['invite_code_first']])->field('id,is_invite,income,invite_code')->find();
            file_put_contents('invite.log', var_export($first_user, true) . '--' . '一级分销乘客信息-' . "\r\n", FILE_APPEND);

            $first_company = M('company')->where(['invite_code' => $invite_info['invite_code_first']])->field('id,income,invite_code')->find();
            file_put_contents('invite.log', var_export($first_company, true) . '--' . '一级分销公司信息-' . "\r\n", FILE_APPEND);

            if (empty($first_company)) {
                if (empty($first_driver) && empty($first_user)) {
                    throw new Exception('一级邀请人信息异常');
                } else if (empty($first_driver) && !empty($first_user)) {
                    $first = $first_user;
                    $first['type'] = 2;
                } else if (!empty($first_driver) && empty($first_user)) {
                    $first = $first_driver;
                    $first['type'] = 1;
                } else {
                    $first = $first_driver;
                    $first['type'] = 4;
                }
            } else {
                $first = $first_company;
                $first['type'] = 3;
                $first['is_invite'] = 0;
            }


            file_put_contents('invite.log', var_export($first, true) . '--' . '一级分销信息-' . "\r\n", FILE_APPEND);
            //二级分销人信息
            $two = array();
            if ($invite_info['invite_code_two']) {
                $two_driver = M('driver')->where(['invite_code' => $invite_info['invite_code_two']])->field('id,is_invite,income,invite_code')->find();
                file_put_contents('invite.log', var_export($two_driver, true) . '--' . '2级分销司机信息-' . "\r\n", FILE_APPEND);
                $two_user = M('user')->where(['invite_code' => $invite_info['invite_code_two']])->field('id,is_invite,income,invite_code')->find();
                file_put_contents('invite.log', var_export($two_user, true) . '--' . '2级分销乘客信息-' . "\r\n", FILE_APPEND);

                $two_company = M('company')->where(['invite_code' => $invite_info['invite_code_two']])->field('id,income,invite_code')->find();
                file_put_contents('invite.log', var_export($two_company, true) . '--' . '2级分销公司信息-' . "\r\n", FILE_APPEND);


                if (empty($two_company)) {

                    if (empty($two_driver) && empty($two_user)) {
                        throw new Exception('二级邀请人信息异常');
                    } else if (empty($two_driver) && !empty($two_user)) {
                        $two = $two_user;
                        $two['type'] = 2;
                    } else if (!empty($two_driver) && empty($two_user)) {
                        $two = $two_driver;
                        $two['type'] = 1;
                    } else {
                        $two = $two_driver;
                        $two['type'] = 4;
                    }
                } else {
                    $two = $two_company;
                    $two['type'] = 3;
                }
            }
        }
        file_put_contents('invite.log', var_export($two, true) . '--' . '2级分销信息-' . "\r\n", FILE_APPEND);
        return ['first' => $first, 'two' => $two];
    }

}
