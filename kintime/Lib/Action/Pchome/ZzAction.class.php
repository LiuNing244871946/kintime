<?php



class ZzAction extends CommonAction {

    public function index(){
      //  preg_match('/\<h1\ class\=\"h1\"\>[\s\S]+\<\/h1\>/','sdfsdfsdfsdfsdf<h1 class="h1">zzzzzzzzzzzzzzzzzz</h1>sdfsdfsdfsdfsdfsf',$arr);
            // print_r($arr);die;
        
        $url = "http://www.ijh.cc"; 
        $str = file_get_contents($url);
        //如果出现中文乱码使用下面代码 
        //$str = iconv("gb2312", "utf-8",$str); 

        //preg_match_all('/\<div\ class\=\"function_cont\"\>\<ul\>[\s\S]+\<\/ul\>\<\/div\>/i',$str,$arr);
        preg_match_all('/\<div\ class\=\"function_cont\"\>\<ul\>[\s\S]+\<\/ul\>\<\/div\>/i',$str,$arr);
        //print_r($arr);die;
        
         /*$str = "<ul>
            <li>234234</li>
            <li>345345</li>
            <li>456456</li>
            <li>657567</li>
            <li>456456</li>
            </ul>";
         
          preg_match('/\<ul\>[\s]+(<li>([^<]+)\<\/li>)+[\s]+\<\/ul\>/i',$str,$arr);
          print_r($arr);die;*/
        
        for($i=1;$i<1000000;$i++){
          echo '<img src="http://192.168.1.122/l.php">';  
        }
        
    }
  
}
