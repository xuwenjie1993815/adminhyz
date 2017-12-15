<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/7
 * Time: 16:09
 */

namespace Home\Logic;

use Think\Exception;

class SetLogic {

    static public function saveSetData($data) {
//        dump($data);exit;
        if (!is_numeric($data['driver_rate']) || $data['driver_rate'] < 0 || $data['driver_rate'] > 100) {
            throw new Exception('请填写正确的司机提成比例，0 ~ 100 之间');
        }

        if (!is_numeric($data['one_rate']) || !is_numeric($data['two_rate']) || !is_numeric($data['three_rate'])) {
            throw new Exception('请填写正确的抽成比例');
        }

        $rate = floatval($data['one_rate']) + floatval($data['two_rate']) + floatval($data['three_rate']);

        if ($rate < 0 || $rate > 100) {
            throw new Exception('请填写正确的提成比例');
        }

        if ($data['z_integral'] == "") {
            throw new Exception('请填写正确的积分');
        }

        if ($data['d_money'] > 5 || $data['x_integral'] < 1000) {
            throw new Exception('请填写正确的消费积分比例');
        }

        if ($data['zf_money'] < 50 || $data['zs_integral'] < 500) {
            throw new Exception('请填写正确的支付积分比例');
        }




        $data['id'] = 1;
        $data['terrace_rate'] = 100 - $rate;
        if (M('set')->save($data) === false)
            throw new Exception('系统繁忙');

        return true;
    }

}
