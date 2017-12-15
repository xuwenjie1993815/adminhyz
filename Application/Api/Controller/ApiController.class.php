<?php

namespace Api\Controller;

/**
 * Description of ApiController
 *
 * @author Chengwei Wang
 */
class ApiController extends \Think\Controller
{
    protected $params;
    
    /**
     * 接口请求初始化
     */
    function __construct()
    {
        parent::__construct();

        // 初始化接口原始数据
        $params = file_get_contents("php://input");
        // JSON字符转数组
        $this->params = json_decode($params, true);

         \Think\Log::record("errorlog :".__MODULE__.__CONTROLLER__.__ACTION__. "\r\n". var_export($this->params,true));

    }
    
    
    /**
     * 返回响应数据
     */
    protected function _responseData($data)
    {
        header('Content-Type:application/json;charset=UTF-8');
         
		file_put_contents('ceshi.log', var_export($data, true) . '--' . 'ceshi-' . "\r\n", FILE_APPEND);
				file_put_contents('ceshi.log', var_export(json_encode($data), true) . '--' . 'ceshi-' . "\r\n", FILE_APPEND);

        echo json_encode($data);

        exit();
    }
    
    
    /**
     * @name 获取用户ID
     * @param $token 用户的token值
     * @param $role  1:用户;2:司机
     */
    protected function get_user_id($token=null,$role=1){
        
        if(empty($token) && empty($this->params['token'])){
            
            return array('errCode'=>50001,'msg'=>'缺少token参数');
            
        }
        try{
            $model=$role === 1 ? M('user') : M('driver');
            
            $where['token']= empty($token) ? $this->params['token'] : $token;
            
            $userid=$model->where($where)->getField('id');
            
            if(empty($userid)){
                
                return array('errCode'=>50002,'msg'=>'用户不存在');
                
            }else{
                
                return array('errCode'=>0,'msg'=>'ok','userid'=>$userid);
                
            }
            
        } catch (\Think\Exception $e){
            
            return array('errCode'=>50000,'msg'=>'程序异常(获取用户标识失败)');
            
        }
        
        
    }
    
    public function read_config(){
        $data=F('config');
        
        if(!empty($data)){
            return $data;
        }
        
        $data=M('config')->getField('name,value');
        F('config',$data);
        return $data;

    }
}
