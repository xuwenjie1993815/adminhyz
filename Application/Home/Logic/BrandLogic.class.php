<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-12
 * Time: 14:47
 */

namespace Home\Logic;


use Home\Model\BrandModel;
use Think\Exception;


class BrandLogic
{
    static public function brand($model,$where) {
        $list = $model->field($field)->where($where)->select();
        
        $order_first = [];
        
        foreach ($list as $key => $value) {
            $first = mb_substr($value['brand_name'], 0,1);
            $s1 = iconv('UTF-8','gb2312', $first);

            if (ord($first)>128) { 
                $asc=ord($s1)*256+ord($s1)-65536;  
                if($asc>=-20319 and $asc<=-20284)$str = "A";  
                if($asc>=-20283 and $asc<=-19776)$str = "B";  
                if($asc>=-19775 and $asc<=-19219)$str = "C";  
                if($asc>=-19218 and $asc<=-18711)$str = "D";  
                if($asc>=-18710 and $asc<=-18527)$str = "E";  
                if($asc>=-18526 and $asc<=-18240)$str = "F";  
                if($asc>=-18239 and $asc<=-17923)$str = "G";  
                if($asc>=-17922 and $asc<=-17418)$str = "I";               
                if($asc>=-17417 and $asc<=-16475)$str = "J";               
                if($asc>=-16474 and $asc<=-16213)$str = "K";               
                if($asc>=-16212 and $asc<=-15641)$str = "L";               
                if($asc>=-15640 and $asc<=-15166)$str = "M";               
                if($asc>=-15165 and $asc<=-14923)$str = "N";               
                if($asc>=-14922 and $asc<=-14915)$str = "O";               
                if($asc>=-14914 and $asc<=-14631)$str = "P";               
                if($asc>=-14630 and $asc<=-14150)$str = "Q";               
                if($asc>=-14149 and $asc<=-14091)$str = "R";               
                if($asc>=-14090 and $asc<=-13319)$str = "S";               
                if($asc>=-13318 and $asc<=-12839)$str = "T";               
                if($asc>=-12838 and $asc<=-12557)$str = "W";               
                if($asc>=-12556 and $asc<=-11848)$str = "X";               
                if($asc>=-11847 and $asc<=-11056)$str = "Y";               
                if($asc>=-11055 and $asc<=-10247)$str = "Z";  
            }
            $list[$key]['first'] = $str;
            array_push($order_first, $str);
        }
        array_multisort($order_first,SORT_ASC,$list);
        return $list;
    }
    /**
     * @brand_name 添加品牌
     */
    static public function addCompany($data)
    {
        $brand_model = new BrandModel();
        
        $name = $data['brand_name'];
        if(M('brand')->where(['brand_name'=>['like',"%$name%"]])->find()) throw new Exception('该品牌已经存在');
        
        if(!$brand_model->create($data)) throw new Exception($brand_model->getError());

        $brand_model->add();

        unset($brand_model);



        return true;
    }


    static public function editBrand($data)
    {
        $brand_model = new BrandModel();
        
        $name = $data['brand_name'];
        if(M('brand')->where(['brand_name'=>['like',"%$name%"],'id'=>['neq',$data['id']]])->find()) throw new Exception('该品牌已经存在');

        if(!$brand_model->create($data)) throw new Exception($brand_model->getError());

        if($brand_model->save() === false) throw new Exception('系统繁忙');

        unset($brand_model);

        return true;
    }

    static public function indexBrand($data)
    {
        $brand_model = new BrandModel();


        if(!$brand_model->create($data)) throw new Exception($brand_model->getError());

        if($brand_model->select() === false) throw new Exception('系统繁忙');

        unset($brand_model);

        return true;
    }


}