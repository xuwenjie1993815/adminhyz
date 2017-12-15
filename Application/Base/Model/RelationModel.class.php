<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Base\Model;

/**
 * Description of RelationModel
 *
 * @author wei
 */
class RelationModel extends \Think\Model\RelationModel{
    /**
     * @name  设置关联关系开启配置
     */
    
    public function setRelation($relation=array(),$use=true){
        if(empty($relation)){
            return ;
        }
        
        if(is_string($relation)){
            $relation=  explode(',', $relation);
        }
        
        foreach($this->_link as $key=>$val){
            if($use){
                if(!in_array($key, $relation)){
                    unset($this->_link[$key]);
                }
            }else{
                if(in_array($key, $relation)){
                    unset($this->_link[$key]);
                }
            }
        }
        return ;
    }
    
    /**
     * @name  设置关联关系查询字段
     */
    public function setRelationFields($relation,$fields){
        if(!isset($this->_link[$relation])){
            return false;
        }
        
        if(is_array($fields)){
            $fields=  implode(',', $fields);
        }
        
        $this->_link[$relation]['mapping_fields']=$fields;
        
        return true;
    }
    
    
    /**
     * @name 设置关联模型查询条件
     * @param $relation 关联关系定义名称（键名）
     * @param $where string 关联查询筛选条件 
     */
    
    public function setRelationCondition($relation,$where){
        $this->_link[$relation]['condition']=$where;
    }
}
