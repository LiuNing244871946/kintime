<?php

class UpgradeAction extends CommonAction {
    
    public function index(){
        $v = require BASE_PATH.'/version.php';
        $sign = md5(uniqid());
        file_put_contents(BASE_PATH.'/patch.php','<?php echo "'.$sign.'";');
        $url = "http://www.ijh.cc/?patch-check.html&h=".urlencode($_SERVER['HTTP_HOST'])."&v=".urlencode($v).'&k='.C('BAO_KEY').'&pro=3d1ec1e9a57a279b5fdd78545341df8a&sign='.$sign;
        //echo $url;die;
        $data = file_get_contents($url);
        unlink(BASE_PATH.'/patch.php');         
        $data = json_decode($data,true);
        if($data['ret']!=1 &&  $data['ret']!=8){
            $this->error($data['msg'],U('index/main'));
        }
        if($data['ret'] == 1){
            $this->success($data['msg'],U('index/main'));
        }
       
        $this->assign('data',$data);
        $this->display(); 
    }
    
    public function download(){
        $dir = BASE_PATH.'/patch';
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        if(!$patch_name = htmlspecialchars($_GET['patch_name'])){
            if(!$patch_id = (int)$_GET['patch_id']){
                $this->error('请选择要下载的补丁');
            }
            $v = require BASE_PATH.'/version.php';
            $sign = md5(uniqid());
            file_put_contents(BASE_PATH.'/patch.php','<?php echo "'.$sign.'";');
            $url = "http://www.ijh.cc/?patch-download.html&patch_id=".$patch_id."&h=".urlencode($_SERVER['HTTP_HOST'])."&v=".urlencode($v).'&k='.C('BAO_KEY').'&pro=3d1ec1e9a57a279b5fdd78545341df8a&sign='.$sign;
            $data = file_get_contents($url);
            unlink(BASE_PATH.'/patch.php');         
            if(empty($data)){
                $this->error('网络超时，没有办法下载服务器补丁'); 
            }           
            $patch_name = md5(uniqid());
            file_put_contents($dir.'/'.$patch_name.'.zip', $data);
            if(!class_exists('ZipArchive')){
                $this->error('您的服务器暂时不支持ZipArchive类文件');
            }
            $zip = new ZipArchive();
            $res = $zip->open($dir.'/'.$patch_name.'.zip');
            if($res === TRUE){
                 $zip->extractTo($dir.'/'.$patch_name);
                 $zip->close();
            }
            unlink($dir.'/'.$patch_name.'.zip');
        }else{
            if(!is_dir($dir.'/'.$patch_name.'/upgrade')){
                $this->error('请重新再试',U('index/main'));
            }
        }
        
        $checks = $this->_checkdirs($dir.'/'.$patch_name.'/upgrade/',BASE_PATH.'/');
        if(!empty($checks)){
            $this->assign('patch_name',$patch_name);
            $this->assign('files',$checks);
            $this->display();
        }else{
            $this->_mv($dir.'/'.$patch_name.'/upgrade/',BASE_PATH.'/');
         
            if(file_exists($dir.'/'.$patch_name.'/upgrade.sql')){
                $sql = file_get_contents($dir.'/'.$patch_name.'/upgrade.sql');
                if(!empty($sql)){
                    $sql = str_replace('bao_', C('DB_PREFIX'), $sql);
                    $sqls = explode(";\n", $sql);
                    foreach($sqls  as $v){
                        $v = trim($v);
                        if(!empty($v)){
                            M()->query($v);
                        }
                    }
                }
                unlink($dir.'/'.$patch_name.'/upgrade.sql');
            }
            $this->_deldir($dir.'/'.$patch_name);
            $this->success('恭喜您升级完成！',U('index/main'));
        }
    }
    
    private function  _deldir($dir) { 
        //先删除目录下的文件： 
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->_deldir($fullpath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹： 
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    private function _checkdirs($filepath,$showpath =''){
        $dh = opendir($filepath);
        $return = array();
        $local = '';
         while ($file = readdir($dh)) {

             if($file == '.' || $file == '..' )            continue;
             if(is_dir($filepath.$file)){
                 $local = $showpath.$file.'/';
                 $return = array_merge($return, $this->_checkdirs($filepath.$file.'/',$local));
             }else{
                 if(file_exists($showpath.$file)){
                    $a = fopen($showpath.$file, 'a+');
                    if($a == false){
                        $return []  = $showpath.$file;
                    }else{
                        fclose($a);
                    }
                 }else{
                    $a =  fopen($showpath.'helloword.txt', 'w+');
                    if($a == false){
                        $return []  = $showpath;
                    }else{
                        fclose($a);
                        unlink($showpath.'helloword.txt');
                    }
                 }
             }
         }
         closedir($dh);
         return $return;
     }

    
    private function _mv($filepath,$mvpatch){
        $dh = opendir($filepath);
         while ($file = readdir($dh)) {
             if($file == '.' || $file == '..' )            continue;
             if(is_dir($filepath.$file)){
                 if(!is_dir($mvpatch.$file)){
                     mkdir($mvpatch.$file,0755,true);
                 }
                 $this->_mv($filepath.$file.'/',$mvpatch.$file.'/');
             }else{
                 if(file_exists($mvpatch.$file)){
                    rename($mvpatch.$file,$mvpatch.NOW_TIME.$file);
                 }
                 rename($filepath.$file, $mvpatch.$file);
             }
         }
         closedir($dh);
         return true;
     }

    
}