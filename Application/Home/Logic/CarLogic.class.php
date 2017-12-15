<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-13
 * Time: 15:09
 */

namespace Home\Logic;


use Home\Model\CarModel;
use Think\Exception;

class CarLogic
{

    /**
     * @brand_name 添加品牌
     */
    static public function addCompany($data)
    {
        $carModel = new CarModel();


        if(!$carModel->create($data)) throw new Exception($carModel->getError());

        $carModel->add();

        unset($carModel);



        return true;
    }

    /**
     * 显示页面
     * @param $data
     * @return bool
     * @throws Exception
     */
    static public function indexBrand($data)
    {
        $carModel = new CarModel();


        if(!$carModel->create($data)) throw new Exception($carModel->getError());

        if($carModel->select() === false) throw new Exception('系统繁忙');

        unset($carModel);

        return true;
    }


    /**
     * 编辑
     * @param $data
     * @return bool
     * @throws Exception
     */

    static public function editCar($data)
    {
        $car_model = new CarModel();


        if(!$car_model->create($data)) throw new Exception($car_model->getError());

        if($car_model->save() === false) throw new Exception('系统繁忙');

        unset($car_model);

        return true;
    }

}