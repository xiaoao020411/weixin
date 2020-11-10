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
                    "type": "click",
                    "name": "日常签到",
                    "key": "V1001_TODAY_MUSIC"
                },
                {
                    "name": "打卡",
                    "sub_button": [
                        {
                            "type": "view",
                            "name": "huizi", 
                            "url": "http://www.csazam.top/huizi"
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
    public function curl($url,$menu){
        //1.关闭
        $ch = curl_init();
        //2.设置
        curl_setopt($ch,CURLOPT_URL,$url);//提交地址
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);//返回值
        curl_setopt($ch,CURLOPT_POST,1);//post提交方式
        curl_setopt($ch,CURLOPT_POSTFIELDS,$menu);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        //curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
        $output = curl_exec($ch);
        //4.关闭
        curl_close($ch);
//        dump($output);
        return $output;

    }

    
}