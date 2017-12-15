<?php

namespace Home\Controller;

use Think\Exception;

class IndexController extends \Base\Controller\BaseController
{
    public function index()
    {
        if(IS_POST)
        {
            
            try
            {
                $this->ajaxReturn($this->getMenu());
            }

            catch (\Exception $e)
            {
                $this->ajaxReturn(array('status'=>0, 'msg'=>$e->getMessage()));
            }
        }

        if(IS_GET)
        {
            $d = date('H');
            if($d>=0 and $d<8){
                $dates = '零晨好';
            }elseif($d>=8 and $d<12){
                $dates = '上午好';
            }elseif($d>=12 and $d<16){
                $dates = '下午好';
            }else{
                $dates = '晚上好';
            }
            $this->assign('dates',$dates);
            $this->assign('username', session('adminInfo.nick_name'));
            $this->display();
        }
    }




    private function getMenu(){
        $menuModel=D('Base/Menu');
        $menuModel->setRelationFields('node','id,title as text,name, sort');
        $menuModel->setRelationCondition('node','ismenu=1');
        $data=$menuModel->field('id,pid,name as text,url')->order('sort asc')->relation(true)->select();
        
        foreach($data as $key=>$val)
        {
            foreach($val['node'] as $k=>$v)
            {
                $val['node'][$k]['url']=U($v['name'],'','html',true);
                $val['node'][$k]['name']=  str_replace('/', '_', strtolower($v['name']));
            }

            $val['node'] = $this->maoPaoByTwo($val['node'], 'sort');

            $data[$key]['children']=$val['node'];
            unset($data[$key]['node']);
        }
        return array_to_tree($data);
    }


    private function maoPaoByTwo($arr, $field, $type='asc')
    {
        //  参数验证
        if(!is_array($arr) || empty($arr))
        {
            return false;
        }

        $type = strtolower($type);

        if($type != 'asc' && $type != 'desc')
        {
            return false;
        }

        if(empty($field))
        {
            return false;
        }

        $count = count($arr);

        // 外层循环，数组冒泡轮数
        for($i=1; $i<$count; $i++)
        {
            // 内层循环，每次冒泡需要比较的次数
            for($j=0; $j<$count-$i; $j++)
            {
                $one = $type == 'asc' ? $arr[$j][$field] : $arr[$j+1][$field];
                $two = $type == 'asc' ? $arr[$j+1][$field] : $arr[$j][$field];

                if($one > $two)
                {
                    $tmp = $arr[$j];
                    $arr[$j] = $arr[$j+1];
                    $arr[$j+1] = $tmp;
                }
            }
            unset($one, $two, $tmp);
        }

        return $arr;
    }
    /**
     * 退出登陆
     */
    public function Logout(){
        session('adminInfo',NULL);
        $data = array('state'=>1);
        $this->ajaxReturn($data);
    }
    
    /**
     * @后台首页
     */
    public function Mainframe(){
        $time=date("Y-m-d 00:00:00");
        $news=strtotime($time);

        $data['company'] = M('company')->count();
        $data['cnums'] = M('company')->where("create_time > $news")->count();
        
        $data['driver'] = M('driver')->count();
        $data['dnums'] = M('driver')->where("create_time > $news")->count();
        
        $data['user'] = M('user')->count();
        $data['unums'] = M('user')->where("ctime > $news")->count();
        
        $data['order'] = M('order')->count();
        $data['onums'] = M('order')->where("order_time > $news")->count();
        
        // $pay1 = M('order')->field("sum(money) as je")->where("status=3")->find();
        // $pay2 = M('order')->field("sum(money) as je")->where("status=3 and create_time > $news")->find();

        $data['pay'] = $pay1['je'];
        $data['pnums'] = $pay1['je'];
        
        $this->assign('data',$data);
        $this->display();
    }
}