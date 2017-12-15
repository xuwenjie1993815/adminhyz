<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-13
 * Time: 14:42
 */

namespace Home\Logic;


use Home\Model\ColourModel;
use Think\Exception;

class ColoursLogic
{

    /**
     * @brand_name 添加颜色
     */
    static public function addCompany($data)
    {
        $colours_model = new ColourModel();


        if(!$colours_model->create($data)) throw new Exception($colours_model->getError());

        $colours_model->add();

        unset($colours_model);



        return true;
    }


    static public function indexBrand($data)
    {
        $colours_model = new ColourModel();


        if(!$colours_model->create($data)) throw new Exception($colours_model->getError());

        if($colours_model->select() === false) throw new Exception('系统繁忙');

        unset($colours_model);

        return true;
    }


}