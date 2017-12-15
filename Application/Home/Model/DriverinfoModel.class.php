<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/4
 * Time: 15:19
 */

namespace Home\Model;

use Think\Model;

class DriverinfoModel extends Model
{
    protected $tableName = 'driver_info';

    protected $_validate = array(
        array('fdj_num','require','发动机号码缺失！', 1),
        array('company_id','require','所属公司ID缺失！', 1),
        array('license_sn','require','车牌号码缺失！', 1),
        array('car_load_num','require','可载人数缺失！', 1),
        array('car_reg_time','require','车辆注册时间缺失！', 1),
        array('car_engine','require','车辆发动机排量缺失！', 1),
        array('car_price','require','购车价格缺失！', 1),
        array('type','require','司机类型缺失！', 1),
        array('license_pic_zm','require','驾驶证正面图片缺失！', 1),
        array('license_pic_fm','require','驾驶证反面图片缺失！', 1),
        array('xsz_zm','require','行驶证正面图片缺失！', 1),
        array('xsz_fm','require','行驶证反面图片缺失！', 1),
    );

//    protected $_auto = array(
//        array('true_name', 'getTrueName', 1, 'callback')
//    );

    protected function getTrueName()
    {
        return 'CJKC'.mt_rand(1000,9999);
    }


    public function updateFields()
    {
        $fields = array('head_pic', 'true_name');

        return $fields;
    }

}