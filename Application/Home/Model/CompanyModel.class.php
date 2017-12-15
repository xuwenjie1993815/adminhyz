<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/5
 * Time: 10:56
 */

namespace Home\Model;


class CompanyModel extends \Think\Model
{
    protected $_validate = array(
        array('invite_name', 'require', '请填写公司名称'),
        array('link_man', 'require', '请填写联系人'),
        array('link_phone', 'require', '请填写联系电话'),
        array('address', 'require', '请填写公司地址'),
        array('standard_person', 'number', '请填写正确的达标人数'),
        array('standard_money', 'number', '请填写正确的达标流水金额'),
        array('reward_money', 'number', '请填写正确的奖励金额'),
        array('reward_person', 'number', '请填写正确的奖励人数'),
    );

    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('code', 'getCode', 1, 'callback')
    );


    protected function getCode()
    {
        $max_id = $this->max('id');

        $code = $max_id ? 10000000 + $max_id : 10000000;
        return $code;
    }
}