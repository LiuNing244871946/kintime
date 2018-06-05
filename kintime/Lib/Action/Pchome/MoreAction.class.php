<?php



class MoreAction extends CommonAction {


    public function index() {
        $hs = D('Houseworksetting');
        $workTypes = D('Housework')->getCfg();
        $hslist = $hs->select();
        $list = array();
        foreach ($workTypes as $k => $val) {
            foreach ($hslist as $kk => $v) {
                if ($k == $v['id']) {
                    $list[$k] = $v;
                }
            }
            $list[$k]['t'] = $val;
        }
        $this->assign('list', $list);
        $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
        $this->assign('elecates',D('Ele')->getEleCate());
        $this->display(); // 输出模板
    }

}
