<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/5
 * Time: 17:41
 */

namespace Home\Logic;

use Base\Logic\PubLogic;
use Think\Exception;

class RegionLogic
{
    /**
     * @name 添加站定信息
     * @param $pid 城市ID
     * @param $name 站点名称
     * @return bool
     * @throws Exception
     */
    static public function addRegion($pid, $name, $location)
    {
        $region_model = M('region');


        //1. 验证父级地区信息不存在
        if(empty($region_model->find($pid))) throw new Exception('父级地区信息异常');
        
        if(empty($name)) throw new Exception('请填写站点名称');

        //2. 添加记录
        list($data['x'], $data['y']) = explode(',', $location);
        $data['pid'] = $pid;
        $data['name'] = $name;
        if(!$region_model->add($data)) throw new Exception('系统繁忙');
        
        unset($region_model);
        return true;
    }


    /**
     * @name 删除地区信息
     * @param $id 站点ID
     * @param $type 1：删除城市，2：删除站点
     * @return bool
     * @throws Exception
     */
    static public function delRegion($id, $type)
    {
        $region_model = M('region');

        switch ($type)
        {
            case 1:

                // 获取城市信息
                if(empty($region_model->where(array('id'=>$id, 'pid'=>0))->find())) throw new Exception('城市信息异常');
                
                // 验证该城市有无站点信息
                if($region_model->where(array('pid'=>$id))->count()) throw new Exception('该城市有站定信息，无法删除');
                
                break;

            case 2:

                // 获取站点信息
                if(empty($region_model->where(array('id'=>$id, 'pid'=>array('neq', 0)))->find())) throw new Exception('站点信息异常');
                
                break;

            default:
                throw new Exception('删除类型异常');
        }

        if(!$region_model->delete($id)) throw new Exception('系统繁忙');
        
        unset($region_model);
        
        return true;
    }


    /**
     * 修改地区启用状态
     * @param $region_id
     * @return int
     * @throws Exception
     */
    static public function useRegion($region_id)
    {
        $region_model = M('region');

        //1. 获取当前状态
        $region_info = $region_model->field('id, is_use')->where(array('id'=>$region_id))->find();

        if(empty($region_info)) throw new Exception('地区信息异常');

        $region_info['is_use'] = $region_info['is_use'] ? 0 : 1;

        if($region_model->save($region_info) === false) throw new Exception('系统繁忙');

        return $region_info['is_use'];
    }


    /******************************************* getData ********************************************************/

    /**
     * 获取指定城市信息
     * @param $city_name 城市名称
     * @return mixed
     * @throws Exception
     */
    static public function getAppointCity($city_name)
    {
        $region_model = M('region');

        $region_info = $region_model->where(array('name'=>array('like', "%{$city_name}%")))->field('id, name')->find();
        
        if(empty($region_info)) throw new Exception('该城市信息不存在，请联系后台工作人员');
        
        return $region_info;
    }
    
}