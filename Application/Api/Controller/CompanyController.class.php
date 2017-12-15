<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/5
 * Time: 14:26
 */

namespace Api\Controller;

use Think\Exception;


class CompanyController extends \Api\Controller\ApiController
{
    public function getCompanyList()
    {
        try
        {
            //1. 获取租车公司信息
            $data['list'] = M('company')->field('id,invite_name as name')->where(['status'=>0])->select();
            $data['num'] = count($data['list']);

            $this->_responseData(apiFormat(0, 'success', $data));
        }
        
        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(2001, $e->getMessage()));
        }
    }
}