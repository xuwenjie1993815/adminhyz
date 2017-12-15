<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-13
 * Time: 9:06
 */

namespace Home\Logic;


use Home\Model\TypeModel;
use Think\Exception;

class TypeLogic
{
    /**
     * @brand_name 添加类型
     */
    static public function addCompany($data)
    {
        $type_model = new TypeModel();
        
        $name = $data['brand_type'];
        if($type_model->where(['brand_type'=>['like',"%$name%"]])->find()) throw new Exception('该类型已经存在');
        
        if(!$type_model->create($data)) throw new Exception($type_model->getError());

        $type_model->add();

        unset($type_model);



        return true;
    }


    static public function editType($data)
    {
        $type_model = new TypeModel();
        
        $name = $data['brand_type'];
        $id = $type_model->where(['brand_type'=>['like',"%$name%"],'id'=>['neq',$data['id']]])->getField('id');
        
        if($id) throw new Exception('该类型已经存在');
        

        if(!$type_model->create($data)) throw new Exception($type_model->getError());

        if($type_model->save() === false) throw new Exception('系统繁忙');

        unset($type_model);

        return true;
    }

    static public function indexBrand($data)
    {
        $type_model = new TypeModel();


        if(!$type_model->create($data)) throw new Exception($type_model->getError());

        if($type_model->select() === false) throw new Exception('系统繁忙');

        unset($type_model);

        return true;
    }

}