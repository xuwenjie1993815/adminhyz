<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-14
 * Time: 17:18
 */

namespace Home\Logic;


use Home\Model\AdvertListModel;
use Home\Model\AdvertModel;
use Home\Model\PictureModel;
use Think\Exception;
use Think\Upload;

class PictureLogic
{

    /**
     * @brand_name 添加图片
     */
    static public function addCompany($data)
    {
        $picture_model = M('advert_list');
        //实例化上传文件类
        $upload = new Upload(C('URL_UPLOAD'));
        $result = $upload->upload();
        if ($result === false) {
            $picture_model->error('文件上传出错'.$upload->getError());
            exit;
        }
        $imgPath = C('URL_UPLOAD.rootPath').$result['imgurl']['savepath'].$result['imgurl']['savename'];
        $imgPath=substr($imgPath,1);
        $data['imgurl']=$imgPath;
        if(!$picture_model->create($data)) throw new Exception($picture_model->getError());
        $picture_model->add();
        unset($picture_model);
        return true;
    }




    static public function userUse($aid)
    {
//        $picture_model = new PictureModel();

        //1. 验证图片信息
        $picture_info = M('advert_list')->field('aid, status')->where(array('id'=>$aid))->find();

        if(empty($picture_info)) throw new Exception('图片信息异常');

        $picture_info['status'] = $picture_info['status'] ? 0 : 1;

        //2. 修改启用状态
        if((M('advert_list')->where(array('id'=>$aid))->save($picture_info)) === false) throw new Exception('系统繁忙');

        return $picture_info['status'];
    }


    static public function editBrand($data)
    {
        $model = new AdvertListModel();
        
        //实例化上传文件类
        $upload = new Upload(C('URL_UPLOAD'));
        $result = $upload->upload();
        if ($result === false) {
            $picture_model->error('文件上传出错'.$upload->getError());
            exit;
        }
        $imgPath = C('URL_UPLOAD.rootPath').$result['imgurl']['savepath'].$result['imgurl']['savename'];
        $imgPath=substr($imgPath,1);
        $data['imgurl']=$imgPath;
        
        if(!($model->create($data))) throw new Exception( $model->getError());
        
        if(($model->where(array('id'=>$data['id']))->save($data)) === false) throw new Exception('系统繁忙');


        return true;
    }


}