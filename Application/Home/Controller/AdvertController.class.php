<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-14
 * Time: 16:42
 */

namespace Home\Controller;


use Base\Controller\BaseController;
use Base\Logic\PubLogic;
use Home\Logic\AdvertLogic;
use Think\Exception;

class AdvertController extends BaseController
{

    /**
     * 广告位列表页面
     */
    public function index()
    {
        if (IS_GET) {
            $title = I('title/s', '');

            $map = array();

            if (!empty($name)) $map['$title'] = array('like', "%{$title}%");

            list($data['list'], $data['fpage']) = PubLogic::getListDataByPage(M('Advert'),$map);

            $data['title'] = $name;
//            var_dump($data);exit;

            $this->assign($data)->display();
        }

    }


    /**
     * 添加页面
     */
    public function add()
    {
        if (IS_GET) {
            $data['ref_url'] = $_SERVER['HTTP_REFERER'];

            $this->display();
        }

        if (IS_POST) {

            try {
                $model = M();

                $model->startTrans();

                AdvertLogic::addCompany(I('post.'));


                $model->commit();

                $this->ajaxReturn(returnData(1, '添加广告位成功！'));
            } catch (Exception $e) {
                $model->rollback();

                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
    }


    /**
     * 编辑页面
     */
    public function edit()
    {
        if (IS_GET) {
            //1. 获取当前广告位信息
            $data['info'] = M('Advert')->find(I('get.id', 0));


            if (empty($data['info'])) throw new Exception('数据异常');

            $data['ref_url'] = $_SERVER['HTTP_REFERER'];

            $this->assign($data)->display();
        }

        if (IS_POST) {

            try {
                AdvertLogic::editAdvert(I('post.'));

                $this->ajaxReturn(returnData(1, '修改广告位成功！'));
            } catch (\Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
    }


    public function start()
    {
        try
        {
            $data['status'] = AdvertLogic::userUse(I('post.aid/d', 0));

            $this->ajaxReturn(returnData(1, '操作成功', $data));
        }

        catch (Exception $e)
        {
            $this->ajaxReturn(returnData(0, $e->getMessage()));
        }
    }


}