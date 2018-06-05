<?php



class PostAction extends CommonAction {

    public function index() {
        $this->assign('nextpage', LinkTo('post/loaddata', array('t' => NOW_TIME, 'p' => '0000')));
        $this->mobile_title = '论坛首页';
        $this->display();
    }

    public function loaddata() {
        $post = D('Post');
        import('ORG.Util.Page'); // 导入分页类
        $map = array( 'audit' => 1, 'closed' => 0);
        $count = $post->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $post->order(array('last_time' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
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
        if (empty($post_id)) {
            $this->error('该帖不存在');
        }
        if (!$detail = D('Post')->find($post_id)) {
            $this->error('该帖不存在');
        }
        if ($detail['audit'] != 1 || $detail['closed'] != 0) {
            $this->error('该帖不合法或已被删除');
        }
        $pics = D('Postpics')->where(array('post_id' => $post_id))->limit(3)->select();

        $user = D('Users')->find($detail['user_id']);
        if (!$res = D('Postzan')->where(array('create_ip' => get_client_ip(), 'post_id' => $post_id))->find()) {
            $detail['is_zan'] = 0;
        } else {
            $detail['is_zan'] = 1;
        }
        $list = D('Postreply')->order(array('create_time' => 'asc'))->where(array('post_id' => $post_id))->select();
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
        $this->mobile_title = '帖子详情';
        $this->display();
    }

    public function fabu() {
        if (empty($this->uid)) {
            AppJump();
        }
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
            $data['user_id'] = $this->uid;
            $data['last_time'] = NOW_TIME;
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
                $this->baoSuccess('恭喜您发帖成功!',U('post/index'),U('post/index'));
            }
            $this->baoError('发帖失败');
        } else {
            $this->mobile_title = '发帖子';
            $this->display();
        }
    }

    public function reply() {
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login', 'msg' => '登录状态失效'));
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
            $data['audit'] = 1;
            if (D('Postreply')->add($data)) {
                D('Post')->updateCount($data['post_id'], 'reply_num');
                D('Post')->save(array('post_id' => $data['post_id'], 'last_ip' => $data['create_ip'], 'last_time' => $data['create_time']));
                $this->ajaxReturn(array('status' => 'success', 'msg' => '回复成功'));
            } else {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复失败'));
            }
        }
    }

    public function zan() {
        if (IS_AJAX) {
            $post_id = (int) $_POST['post_id'];
            if (empty($post_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '要赞的帖子不存在'));
            }
            $user_id = $this->uid;
            if ($res = D('Postzan')->where(array('post_id' => $post_id, 'create_ip' => get_client_ip()))->find()) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '您已经点过赞了'));
            } else {
                if (D('Postzan')->add(array('post_id' => $post_id, 'user_id' => $user_id, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip()))) {
                    D('Post')->updateCount($post_id, 'zan_num');
                    $this->ajaxReturn(array('status' => 'success', 'msg' => '点赞成功'));
                } else {
                    $this->ajaxReturn(array('status' => 'error', 'msg' => '点赞失败'));
                }
            }
        }
    }

}