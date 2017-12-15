<?php

namespace Base\Model;

/**
 * Description of MenuModel
 *
 * @author Chengwei Wang
 */
class MenuModel extends RelationModel
{
     public $patchValidate = false;
    
    //定义数据表字段名称映射
    public $_field=array(
            'id'=>'菜单ID',
            'pid'=>'父级ID',
            'name'=>'菜单名称',
            'sort'=>'排序',
        );
    
    
    
    //自动验证配置
    protected $_validate=array(        
        
        array('name','require','菜单名称必须！',1,'regex',1),  // 在新增的时候验证
        
        array('name','','该菜单已经存在！',2,'unique',1), // 在新增的时候验证
    );
    
    
    protected $_link=array(
    
            'node'=>array(
                'mapping_type'=>self::HAS_MANY,
                'class_name'  =>'node',
                'foreign_key' =>'groupid',
                'mapping_fields'=>'id,title'
            )
        );
}
