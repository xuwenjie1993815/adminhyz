<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Home\Controller;

use Think\Exception;

use Home\Logic\RabcLogic;

/**
 * Description of RabcController
 *
 * @author Administrator
 */
class RabcController extends \Base\Controller\BaseController{
    //put your code here
    public function MenuList(){
        $data['list'] = M('menu')->order('sort')->select();
        
        $this->assign($data)->display();
    }

      
    
    /**
     * @删除菜单
     */
    public function MenuDel(){
        $id = I('post.id');
        $count = M('node')->where(array('groupid'=>$id))->count();
        if($count>=1){
            $this->ajaxReturn(array('status'=>0,'msg'=>'该菜单下有子菜单，不能删除！'));
        }else{
            if(M('menu')->where(array('id'=>$id))->delete()){
                $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功！'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败请重试！'));
            }
        }
    }
    
    /**
     * @编辑菜单
     */
    public function MenuEdit(){
        $data['info'] = M('menu')->where(array('id'=>I('get.id')))->find();
        $this->assign($data)->display();
    }
    
    /**
     * @新增菜单
     */
    public function MenuAdd(){
        $this->display();
    }

    /**
     * @新增或修改菜单 
     */
    public function MenuUpdate(){
        $id = I('post.id');
        $name = I('post.name');
        $url = I('post.url');
        $sort = empty(I('post.sort'))?0:I('post.sort');
        
        //新增
        if(empty($id)){
            $array = array(
                'name'=>$name,
                'sort'=>$sort,
                'pid'=>0,
                'url'=>$url
            );
            
            if(M('menu')->add($array)){
                $this->ajaxReturn(array('status'=>1,'msg'=>'菜单新增成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'菜单新增失败请重试'));
            }
        }else{
            if(M('menu')->where(array('id'=>$id))->save(array('name'=>$name,'sort'=>$sort,'url'=>$url))){
                $this->ajaxReturn(array('status'=>1,'msg'=>'菜单修改成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'菜单修改失败请重试'));
            }
        }
    }
/*----------------------以上为菜单管理---------------------------------*/
        /**
     * @节点列表
     */
    public function NodeList(){
        $data=array();
        
        $Menu = M('menu')->order('sort')->select();
        foreach ($Menu as $group){
            $data['list'][] =array(
                'id'=>$group['id'],
                'title'=>$group['name'],
                'name'=>'',
                'groupname'=>'顶部菜单',
                'ismenu'=>'是',
                'sort'=>$group['sort'],
                'opt'=>0
            );
            $node = M('node')->where(array('groupid'=>$group['id']))->order('sort')->select();
            foreach ($node as $mu){
                $data['list'][] =array(
                    'id'=>$mu['id'],
                    'title'=>"　　".$mu['title'],
                    'name'=>$mu['name'],
                    'groupname'=>$group['name'],
                    'ismenu'=>($mu['ismenu']==1)?"是":"否",
                    'sort'=>$mu['sort'],
                    'opt'=>1
                );
            }
        }

        $this->assign($data)->display();
    }
        
    /**
     * @编辑菜单NODE
     */
    
    /**
     * @新增或修改菜单 NODE
     */
    public function NodeUpdate(){
        if(IS_POST){
            $title = I('post.title');
            $name = I('post.name');
            $groupid = I('post.groupid');
            $ismenu = I('post.ismenu');
            $sort = empty(I('post.sort'))?0:I('post.sort');
            
            $where['name'] = ['like',"%$name%"];
            //新增
            if(empty($node)){
                $array = array(
                    'title'=>$title,
                    'name'=>$name,
                    'groupid'=>$groupid,
                    'ismenu'=>$ismenu,
                    'sort'=>$sort
                );

                if(M('node')->add($array)){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'新增成功'));
                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>'新增失败请重试'));
                }
            }else{
                if(M('node')->where(array('id'=>$id))->save(array('title'=>$title,'name'=>$name,'groupid'=>$groupid,'ismenu'=>$ismenu,'sort'=>$sort))){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'修改成功'));
                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>'修改失败请重试'));
                }
            }
        }else{
            
            //归属菜单
            $menu = M('menu')->field('id,name')->select();
            
            $this->assign('menu', $menu);
            
            $this->display('NodeEdit');
        }
        
    }
    public function NodeEdit(){
        if(IS_POST){
            $id = I('post.id');
            $title = I('post.title');
            $name = I('post.name');
            $groupid = I('post.groupid');
            $ismenu = I('post.ismenu');
            $sort = empty(I('post.sort'))?0:I('post.sort');
            
            //编辑
            if(M('node')->where(array('id'=>$id))->save(array('title'=>$title,'name'=>$name,'groupid'=>$groupid,'ismenu'=>$ismenu,'sort'=>$sort))){
                $this->ajaxReturn(array('status'=>1,'msg'=>'修改成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'修改失败请重试'));
            }
        }else{
            $id = I('id');
            //归属菜单
            $node = M('node')->where(['id'=>$id])->find();
            
            //归属菜单
            $menu = M('menu')->field('id,name')->select();
            
            $this->assign('menu', $menu);
            $this->assign('node', $node);
            $this->assign('id', $id);
            
            $this->display('NodeUpdate');
        }
        
    }
    public function NodeDel() {
        //节点id
        $id = I('id');
        
        if(M('node')->where(array('id'=>$id))->delete()){
            $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功！'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败请重试！'));
        }
    }
    /*----------------------以上为节点管理---------------------------------*/
    public function roleadmin() {
        $list = M('role')->order('id desc')->select();
        
        $this->assign('list',$list);
        $this->display();
    }
    /**
     * 角色下登录用户
     */
    public function adminList() {
        $id = I('id');
        
        $list = M('admin')->alias('a')->join('__ADMIN_NODE__ as b on a.id=b.adminid')->field('a.id,a.account,a.nick_name')->where(['b.nodeid'=>$id])->select();
        
        $this->assign('list', $list);
        $this->display();
    }
    /**
     * 删除登录用户
     */
    public function adminDel() {
        $id = I('post.id');
        
        $model = M('admin');
        $model->startTrans();
        
        if(!$model->where(array('id'=>$id))->delete()){
            $model->rollback();
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除信息失败请重试！'));
        }
        
        if(!M('admin_node')->where(array('adminid'=>$id))->delete()){
            $model->rollback();
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败请重试！'));
        }
        
        $model->commit();
        $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功！'));
    }
	/**
     * 角色下登录用户
     */
    public function repassword() {
        if (IS_POST) {
            $id = I('post.id');
            $pwd = md5(I('post.pwd'));
            
            $model = M('admin');
            $model->startTrans();
            
            if ($model->where(array('id' => $id))->getField('pwd') == $pwd) {
                $model->rollback();
                $this->ajaxReturn(array('status' => 0, 'msg' => '不能和原密码一致'));
            }
            if (!$model->where(array('id' => $id))->find()) {
                $model->rollback();
                $this->ajaxReturn(array('status' => 0, 'msg' => '用户信息异常'));
            }

            if (!$model->where(array('id' => $id))->save(['pwd'=>$pwd])) {
                $model->rollback();
                $this->ajaxReturn(array('status' => 0, 'msg' => '密码修改失败'));
            }

            $model->commit();
            $this->ajaxReturn(array('status' => 1, 'msg' => '密码修改成功！'));
        } else {
            $id = I('id');
            $this->assign('id',$id);
            $this->display();
        }
    }
    public function roleList() {
        $id = I('role_id');
        $data=array();
        
        $node_list = M('role_node')->where(['roleid'=>$id])->getField('nodeid',true);
        
        $Menu = M('menu')->order('sort')->select();
        
        
        foreach ($Menu as $group){
            
            $node = M('node')->where(array('groupid'=>$group['id']))->order('sort,groupid')->select();
            
            $count = count($node);
            
            $i = 0;
            $arr = array();
            foreach ($node as $mu){
                
                
                if(in_array($mu['id'], $node_list)){
                    $arr[] = array(
                        'id'=>$mu['id'],
                        'title'=>"　　".$mu['title'],
                        'name'=>$mu['name'],
                        'groupname'=>$group['name'],
                        'ismenu'=>($mu['ismenu']==1)?"是":"否",
                        'sort'=>$mu['sort'],
                        'opt'=>1,
                        'groupid'=>$mu['groupid'],
                        'checked'=>1
                    );
                        
                    $i ++ ;
                }else{
                    $arr[] = array(
                        'id'=>$mu['id'],
                        'title'=>"　　".$mu['title'],
                        'name'=>$mu['name'],
                        'groupname'=>$group['name'],
                        'ismenu'=>($mu['ismenu']==1)?"是":"否",
                        'sort'=>$mu['sort'],
                        'opt'=>1,
                        'groupid'=>$mu['groupid'],
                        'checked'=>0
                    );
                }
                
            }
            if($count == $i){
                $data['list'][] =array(
                    'id'=>$group['id'],
                    'title'=>$group['name'],
                    'name'=>'',
                    'groupname'=>'顶部菜单',
                    'ismenu'=>'是',
                    'sort'=>$group['sort'],
                    'opt'=>0,
                    'groupid'=>0,
                    'checked'=>1   
                );
            }else{
                $data['list'][] =array(
                    'id'=>$group['id'],
                    'title'=>$group['name'],
                    'name'=>'',
                    'groupname'=>'顶部菜单',
                    'ismenu'=>'是',
                    'sort'=>$group['sort'],
                    'opt'=>0,
                    'groupid'=>0,
                    'checked'=>0   
                );
            }
            $data['list'] =array_merge($data['list'],$arr);
            
        }
        $this->assign('id',$id);
        $this->assign($data);
        $this->display();
    }
	/**
     * 权限设置
     * @menu_id 菜单id
     * @node_id 节点id
     * @role_id 角色id
     */
    public function roleEdit() {
        //菜单
        $menu_id = I('post.menu_id');
        //节点
        $node_id = I('post.node_id');
        
        //登录用户角色
        $admin_role = I('post.role_id');
        
        if($admin_role){
            if(M('role_node')->where(['roleid'=>$admin_role])->find()){
                if(!M('role_node')->where(['roleid'=>$admin_role])->delete()){
                    $this->ajaxReturn(['msg'=>'更新权限失败']);
                }
            }
            foreach ($node_id as $key => $value) {
                if(!M('role_node')->where(['nodeid'=>$value,'roleid'=>$admin_role])->find()){
                    $add['roleid'] = $admin_role;
                    $add['nodeid'] = $value;
                    if(!M('role_node')->add($add)){
                        $this->ajaxReturn(['msg'=>'添加权限失败']);
                    }
                }
                
            }
        }
        
        $this->ajaxReturn(['msg'=>'设置权限成功','status'=>1]);
        
    }

    /**
     * 添加角色
     */
    public function roleAdd() {
        if(IS_AJAX){
            $nick_name = I('post.nick_name');
            
            if(!M('role')->add(['nick_name'=>$nick_name])){
                $this->ajaxReturn(['msg'=>'添加失败']);
            }
            $this->ajaxReturn(['msg'=>'添加成功','status'=>1]);
        }else{
            $this->display();
        }
    }
    /**
     * 添加角色
     */
    public function adminAdd() {
        if(IS_AJAX){
            $data = I('post.');
            $data['pwd'] = md5($data['pwd']);
            
            $model = M('admin');
            $model->startTrans();
            
            //添加admin表
            if(!$model->add($data)){
                $model->rollback();
                $this->ajaxReturn(['msg'=>'添加失败']);
            }
            //添加role_node表
            $role['adminid'] = $model->getLastInsID();
            $role['nodeid'] = $data['role_id'];
            if(!M('admin_node')->add($role)){
                
                $model->rollback();
                $this->ajaxReturn(['msg'=>'添加失败']);
            }
            
            $model->commit();
            $this->ajaxReturn(['msg'=>'添加成功','status'=>1]);
        }else{
            $id = I('id');
            
            $this->assign('id', $id);
            
            $this->display();
        }
    }
    public function roleEditInfo() {
        if(IS_AJAX){
            $nick_name = I('post.nick_name');
            $id = I('post.id');
            
            if(!M('role')->where(['id'=>$id])->save(['nick_name'=>$nick_name])){
                $this->ajaxReturn(['msg'=>'修改失败']);
            }
            $this->ajaxReturn(['msg'=>'修改成功','status'=>1]);
        }else{
            $id = I('id');
            
            $info = M('role')->where(['id'=>$id])->find();
            $this->assign('info', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }
}
