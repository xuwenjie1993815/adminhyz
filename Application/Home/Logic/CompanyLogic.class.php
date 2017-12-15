<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/5
 * Time: 11:22
 */

namespace Home\Logic;

use Think\Exception;
use Home\Logic\InviteLogic;

class CompanyLogic
{
    /**
     * @name 添加租车公司
     * @param $data array('name'=>xxxxx, 'link_man'=>xxxxx, 'link_phone'=>xxxxx, 'address'=>xxxxx)
     * @return bool
     * @throws Exception
     */
    static public function addCompany($data)
    {
        $company_model = new \Home\Model\CompanyModel();

        if($company_model->where(['link_phone'=>$data['link_phone']])->find()) throw new Exception('手机号码已存在，请重新输入唯一手机号码');
        
        if(!preg_match('/^[0-9]{15,21}$/i',$data['blank'])) throw new Exception('请填写正确的银行卡号');

        if(!$company_model->create($data)) throw new Exception($company_model->getError());

        $company_id = $company_model->add();
        
        unset($company_model);

        InviteLogic::createInviteCode($company_id, 3);

        return true;
    }


    /**
     * @name 编辑租车公司信息
     * @param $data array('id'=>xxxxx, 'name'=>xxxxx, 'link_man'=>xxxxx, 'link_phone'=>xxxxx, 'address'=>xxxxx)
     * @return bool
     * @throws Exception
     */
    static public function editCompany($data)
    {
        $company_model = new \Home\Model\CompanyModel();
        
        $count = $company_model->where(['link_phone'=>$data['link_phone']])->find();
        
        if($count && $count['id'] != $data['id']) throw new Exception('手机号码已存在，请重新输入唯一手机号码');
        
        if(!preg_match('/^[0-9]{15,21}$/i',$data['blank'])) throw new Exception('请填写正确的银行卡号');

        if(!$company_model->create($data)) throw new Exception($company_model->getError());

        if($company_model->save() === false) throw new Exception('系统繁忙');

        unset($company_model);

        return true;
    }
}