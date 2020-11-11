<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class WxController extends Controller
{
    public function Token(){
        $echostr = request()->get('echostr','');
        if($this->checkSignature() && !empty($echostr)){
            echo $echostr;
        }
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = "Token";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    //获取access_token
    public function getAccessToken(){
        $key = 'wx:access_token';
        $token = Redis::get($key);
        if($token){
            echo "有缓存";
        }else{
            echo "无缓存";
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
        $response = file_get_contents($url);
        
        $data = json_decode($response,true);
        
        $token = $data['access_token'];
        Redis::set($key,$token);
        Redis::expire($key,3600);
        
        }
        return $token;
    }

        public function wxEvent()
        {
            //echo __METHOD__;die;
            $signature = request()->get("signature");
            $timestamp = request()->get("timestamp");
            $nonce = request()->get("nonce");
            
            $token = "Token";
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );
            //关注成功恢复
            if( $tmpStr == $signature ){
                $xml_data=file_get_contents('php://input');
                file_put_contents('wx_event.log',$xml_data);
                $data = simplexml_load_string($xml_data);
                if($data->MsgType=='event'){
                    if($data->Event=='subscribe'){
                            $Content ="关注成功";
                            $result = $this->infocode($data,$Content);
                            return $result;
                    }
                    //回复天气
            $arr=['天气','天气。','天气,'];
            if($data->Content==$arr[array_rand($arr)]){
                $Content = $this->weather();
                $result = $this->infocodl($data,$Content);
                return $result;
            }
            }else{
                echo "";
            }
            //被动回复消息
        if( $tmpStr == $signature ){
            $xml_data=file_get_contents('php://input');
            file_put_contents('wx_event.log',$xml_data);
            $data = simplexml_load_string($xml_data);
            if($data->MsgType=='text'){
                    $array=['你好呀','祝你今天运气爆棚','王慧❤','嘿嘿嘿'];
                    $Content =$array[array_rand($array)];
                    $result = $this->infocode($data,$Content);
                    return $result;
            }
        }
        }
    }
    //封装回复信息
    public function infocode($data,$Content){
        $ToUserName=$data->FromUserName;
        $FromUserName=$data->ToUserName;
        $CreateTime=time();
        $MsgType="text";
            $xml="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Event><![CDATA[%s]]></Event>
                <Content><![CDATA[".$Content."]]></Content>
            </xml>";
            echo sprintf($xml,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
    }
    public function guzzle2(){
        $access_token = $this->getAccessToken();
        //dd($access_token);
        $type = 'image';
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
        $client = new Client();
        $response = $client->request('POST',$url,[
                'verify'=>false,
                'multipart'=>[
                    [
                        'name'  =>  'media',
                        'contents'  =>  fopen('34444.jpg','r')
                    ],
                ]
            ]);
            $data = $response->getBody();
            echo $data;
    }
    public function createMenu(){
        $menu = '{
            "button": [
                {
                    "type": "view",
                    "name": "天气",
                    "url": ""
                },
                {
                    "name": "打卡",
                    "sub_button": [
                        {
                            "type": "view",
                            "name": "百度", 
                            "url": "http://www.baidu.com"
                        },
                        {
                            "type": "click",
                            "name": "查看积分",
                            "key": "V1001_GOOD"
                        }]
                }]
        }';
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $client = new Client();
        $respones = $client->request('post',$url,['verify'=>false,'body'=>$menu]);
        $data = $respones->getBody();
        echo $data;
    }
    public function weather(){
        $key = '8fe30e0a6d5a49928dda4e399d37fd1c';
        $url = 'https://devapi.qweather.com/v7/weather/now?key='.$key.'&location=101010100&gzip=n';
        $red = $this->curl($url);
        $red = json_decode($red,true);
        $rea = $red['now'];
        $rea = implode(',',$rea);
        return $rea;
    }
    //调用接口方法
    public function curl($url,$header="",$content=[]){
        $ch = curl_init(); //初始化CURL句柄
        if(substr($url,0,5)=="https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //字符串类型打印
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        if(!empty($header)){
            curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
        }
        if($content){
            curl_setopt ($ch, CURLOPT_POST,true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,$content);
        }
        //执行
        $output = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        //关闭
        curl_close($ch);
        return $output;
    }
    
}