<?php



class PostAction extends CommonAction {

    public function main() {
        if (IS_AJAX) {
            $list = D('Community')->where(array('city_id' => $this->city_id))->select();
            if ($list) {
                $this->ajaxReturn(array('status' => 'success', 'list' => $list));
            }
        } else {
            $this->display(); // 输出模板
        }
    }

    public function index() {
       /* $name = htmlspecialchars($_POST['name']);
        $detail = D('Community')->where(array('city_id' => $this->city_id))->find();
        if (!empty($detail)) {
            $this->assign('detail', $detail);
        }*/
        $post = D('Post');
        import('ORG.Util.Page'); // 导入分页类
        $maps = array('closed' => 0, 'audit' => 1);
        /*if($community_id = (int) $this->_param('community_id')){
            $map['community_id'] = $community_id; 
            $this->assign('community_id',$community_id);
        }else{
            $map['community_id'] = $detail['community_id'];
            $this->assign('community_id',$detail['community_id']);
        }*/
       /* $news = D('Communitynews')->order(array('news_id'=>'desc'))->where(array('community_id'=>$map['community_id'],'audit'=>1,'closed'=>0))->find();
        $this->assign('news',$news);*/
        $city_id = $this->city_id;
        $maps['city_id']=array('in',array(0,$city_id));
        $count = $post->where($maps)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case 'new':
                $orderby = array('post_id' => 'desc');
                break;
            case 'hot':
                $orderby = array('reply_num' => 'desc','views'=>'desc','zan_num'=>'desc');
                break;
            default:
                $orderby = array('last_time' => 'desc');
                break;
        }
        $this->assign('order', $order);
        $list = $post->where($maps)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $post_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['user_id'])) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
            $post_ids[$val['post_id']] = $val['post_id'];
        }
        if (!empty($post_ids)) {
            $this->assign('pics', D('Postpics')->where(array('post_id' => array('IN', $post_ids)))->select());
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function detail($post_id) {
        $post_id = (int) $post_id;
        if(empty($post_id)){
            $this->error('帖子不存在');
        }
        if (!$detail = D('Post')->find($post_id)) {
            $this->error('该帖不存在');
        }
        if ($detail['audit'] != 1 || $detail['closed'] != 0) {
            $this->error('该帖不合法或已被删除');
        }
        $pics = D('Postpics')->where(array('post_id' => $post_id))->limit(3)->select();
        $news = D('Communitynews')->order(array('news_id'=>'desc'))->where(array('community_id'=>$detail['community_id'],'audit'=>1,'closed'=>0))->find();
        $this->assign('news',$news);
        $user = D('Users')->find($detail['user_id']);
        if (!$res = D('Postzan')->where(array('create_ip' => get_client_ip(), 'post_id' => $post_id))->find()) {
            $detail['is_zan'] = 0;
        } else {
            $detail['is_zan'] = 1;
        }
        $map = array();
        $config = D('Setting')->fetchAll();
        if($config.postaudit){
            $map['audit'] = 1;
        }
        $map['post_id'] = $post_id;
        $list = D('Postreply')->order(array('create_time' => 'asc'))->where($map)->select();
        $user_ids = array();
        $a = 2;
        foreach ($list as $k => $val) {
            if (!empty($val['user_id'])) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
            $list[$k]['floor'] = $a;
            $a++;
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('a', $a - 2);
        $this->assign('list', $list);
        $this->assign('user', $user);
        $this->assign('pics', $pics);
        $this->assign('detail', $detail);
        $this->display();
    }

    public function fabu($community_id = null) {
        if (empty($this->uid)) {
            $this->baoSuccess('您还没有登录，不能发帖！', U('passport/login'));
        }
        $community_id = (int) $community_id;
        if (empty($community_id)) {
             $community_id = 0;
            //$this->error('该小区不存在');
        }
        if (!$detail = D('Community')->find($community_id)) {
           // $this->error('没有该小区');
            //die;
        }
        if ($detail['closed']) {
            //$this->error('该小区已经被删除');
            //die;
        }
        $city_id = $this->city_id;
        $obj = D('Post');
        if ($this->isPost()) {
            $data['title'] = htmlspecialchars($_POST['title']);
            if (empty($data['title'])) {
                $this->baoError('标题不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['title'])) {
                $this->baoAlert('标题含有敏感词：' . $words);
            }
            $data['details'] = htmlspecialchars($_POST['details']);
            if (empty($data['details'])) {
                $this->baoError('标题不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['details'])) {
                $this->baoAlert('详情含有敏感词：' . $words);
            }
			if($post=$obj->where(array('title'=>$data['title'],'details'=>$data['details']))->select()){
				$this->baoError('不能重复提交');	
			}
            $data['audit'] = $this->_CONFIG['site']['postaudit'];
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $data['community_id'] = $community_id;
            $data['user_id'] = $this->uid;
            $data['last_time'] = NOW_TIME;
            $data['city_id'] = $city_id;
            if ($post_id = $obj->add($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val))
                        $local[] = $val;
                }
                if (!empty($local)) {
                    D('Postpics')->upload($post_id, $local);
                }
                $this->baoSuccess('恭喜您发帖成功!', U('post/index'));
            }
            $this->baoError('发帖失败');
        } else {
            $this->assign('community_id', $community_id);
            $this->display();
        }
    }

    public function reply() {
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status'=>'login'));
        }
        if (IS_AJAX) {
            $data['contents'] = htmlspecialchars($_POST['contents']);
            if (empty($data['contents'])) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复内容不能为空'));
            }
            if ($words = D('Sensitive')->checkWords($data['contents'])) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '标题含有敏感词：' . $words));
            }
            $data['post_id'] = (int) $_POST['post_id'];
            if (empty($data['post_id'])) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复的帖子不存在'));
            }
            $data['user_id'] = $this->uid;
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $data['audit'] = 0;
            if(D('Postreply')->where(array('user_id'=>$this->uid,'contents'=>$data['contents']))->select()){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请不要重复回复！'));
            }
            if (D('Postreply')->add($data)) {
                D('Post')->updateCount($data['post_id'], 'reply_num');
                D('Post')->save(array('post_id' => $data['post_id'], 'last_id' => $this->uid, 'last_time' => $data['create_time']));
                $this->ajaxReturn(array('status' => 'success', 'msg' => '回复成功'));
            } else {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复失败'));
            }
        }
        
        $this->display();
    }

}
