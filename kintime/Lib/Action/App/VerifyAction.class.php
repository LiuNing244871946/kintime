<?php

class  VerifyAction extends CommonAction{
    
    public function index(){
        import('ORG.Util.Image');
        ob_end_clean();
        Image::buildImageVerify(4,2,'png',60,30);
    }
    
    
    
}