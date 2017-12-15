<?php

namespace Base\Model;


use Think\Exception;

/**
 * Description of AdminModel
 *
 * @author Chengwei Wang
 */
class AdminModel extends RelationModel{
    

    /**
     * 获取管理员信息
     * @return mixed
     */
    public function getData()
    {
        if(!empty(S('admin')))
        {
            $data = S('admin');
        }
        else
        {
            $data = $this->select();

            S('admin', $data);
        }
        return setArrayKey($data, $this->pk);
    }


    /**
     * 获取某个字段值
     * @param $id
     * @param $field
     * @return mixed
     * @throws Exception
     */
    public function getFieldById($id, $field)
    {
        if(empty($id) || empty($field)) throw new Exception('参数错误');

        $data = $this->getData();

        if(empty($val = $data[$id][$field])) throw new Exception('信息获取失败');

        return $val;
    }


    /**
     * 获取某一条记录
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function getInfo($id)
    {
        if(empty($id)) throw new Exception('参数错误');

        $data = $this->getData();

        $val = $data[$id];

        if(empty($val)) throw new Exception('信息获取失败');

        return $val;
    }
}
