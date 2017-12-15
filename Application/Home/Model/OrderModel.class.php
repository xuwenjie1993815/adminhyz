<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/12
 * Time: 18:13
 */

namespace Home\Model;


class OrderModel extends \Think\Model
{
    protected $_validate = array(
        array('user_id', 'require', '用户信息缺失', 1),

        array('seat_num', 'require', '座位信息缺失', 1),

        array('money', 'require', '订单金额缺失', 1),
    );

    protected $_auto = array(
        array('create_time', 'time', 1, 'function')
    );


}