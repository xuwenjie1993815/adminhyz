<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/6
 * Time: 11:31
 */

namespace Home\Model;

use Think\Exception;

class ServiceModel extends \Think\Model
{
    protected $_validate = array(
        array('driver_id', 'checkDriverId', '司机信息异常', 1, 'callback'),

        array('link_phone', 'require', '联系电话缺失', 1),


        array('start_city', 'require', '出发城市缺失', 1),
        array('start_city', 'checkCity', '出发城市信息异常', 1, 'callback'),

        array('start_point', 'require', '出发站点缺失', 1),
        array('start_point', 'checkSite', '出发站点信息异常', 1, 'callback'),

        array('purpose_city', 'require', '目的城市缺失', 1),
        array('purpose_city', 'checkCity', '目的城市信息异常', 1, 'callback'),

        array('purpose_point', 'require', '目的站点缺失', 1),
        array('purpose_point', 'checkSite', '目的站点信息异常', 1, 'callback'),

        array('is_yy', 'require', '预约类型缺失', 1),
        
        array('price', 'require', '人头单价缺失', 1),
    );

    // 验证司机信息
    protected function checkDriverId($driver_id)
    {
        if(M('driver')->where(array('id'=>$driver_id))->count()) return true;

        return false;
    }

    // 验证城市信息
    protected function checkCity($city_id)
    {
        if(M('region')->where(array('pid'=>0, 'id'=>$city_id))->count()) return true;
        
        return false;
    }

    // 验证站点信息
    protected function checkSite($site_id)
    {
        if(M('region')->where(array('pid'=>array('neq', 0), 'id'=>$site_id))->count()) return true;

        return false;
    }

    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('order_sn', 'getCode', 1, 'callback'),
    );
    
    protected function getCode()
    {
        return makeOrder_sn();
    }

    public function setTypeRelation($type)
    {
        switch ($type)
        {
            case 1:
                break;
            
            case 2:
                $this->_validate[]=array('departur_time','require','请填写定时发车时间',1);
                break;
            
            default:
                throw new Exception('发车类型缺失');
        }
    }
    
    public function setYyRelation()
    {
        $this->_validate[]=array('departur_time','require','请填写发车时间',1);
    }
}