<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-14
 * Time: 17:01
 */

namespace Home\Logic;


use Home\Model\AdvertModel;
use Think\Exception;

class AdvertLogic
{

    /**
     * @brand_name 添加广告位
     */
    static public function addCompany($data)
    {
        $advert_model = new AdvertModel();


        if(!$advert_model->create($data)) throw new Exception($advert_model->getError());

        $advert_model->add();

        unset($advert_model);



        return true;
    }


    static public function editAdvert($data)
    {
        $avert_model = new AdvertModel();


        if(!$avert_model->create($data)) throw new Exception($avert_model->getError());

        if($avert_model->save() === false) throw new Exception('系统繁忙');

        unset($avert_model);

        return true;
    }

}