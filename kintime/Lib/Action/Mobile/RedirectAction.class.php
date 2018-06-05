<?php



class RedirectAction extends CommonAction {

    public function index() {
        if($func_id = $this->_param('func_id')){
            $function = D('Function')->find($func_id);
            if(false !== strpos($function['url'], 'http://') || false !== strpos($function['url'], 'https://') ){
                header("Location:" . htmlspecialchars_decode($function['url']));die;
            }else{
                header("Location:" . U($function['url']));die;
            }
        }else{
            $this->error(404);
        }
    }

}
