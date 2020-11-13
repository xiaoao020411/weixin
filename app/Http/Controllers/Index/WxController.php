<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Model\WxModel;
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
        }else{
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
                        $openid = $data->FromUserName;
                        $info = WxModel::where(['openid'=>$openid])->first();
                        // var_dump($info);die;
                        if($info){
                            $Content = "欢迎再次关注 现在时间是：".date('Y-m-d H:i:s');
                        }else{
                            $userInfo = $this->getWxUserInfo($openid);
                            unset($userInfo['remark']);
                            unset($userInfo['groupid']);
                            unset($userInfo['tagid_list']);
                            unset($userInfo['subscribe_scene']);
                            unset($userInfo['qr_scene']);
                            unset($userInfo['qr_scene_str']);
                            unset($userInfo['subscribe']);
                            WxModel::insertGetId($userInfo);
                            $Content ="关注成功 现在时间是：".date('Y-m-d H:i:s');
                        }
                        
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
    //自定义菜单
    public function createMenu(){
        $menu = '{
            "button": [
                {
                    "name": "发图", 
                    "sub_button": [
                        {
                            "type": "pic_sysphoto", 
                            "name": "系统拍照发图", 
                            "key": "rselfmenu_1_0", 
                           "sub_button": [ ]
                         }, 
                        {
                            "type": "pic_photo_or_album", 
                            "name": "拍照或者相册发图", 
                            "key": "rselfmenu_1_1", 
                            "sub_button": [ ]
                        }, 
                        {
                            "type": "pic_weixin", 
                            "name": "微信相册发图", 
                            "key": "rselfmenu_1_2", 
                            "sub_button": [ ]
                        }
                    ]
                },
                {
                    "type": "view",
                    "name": "商城",
                    "url": "http://wanghui.csazam.top/"
                },
                {
                    "type": "click",
                    "name": "天气",
                    "key": "WEATHER"
                }]
        }';
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $client = new Client();
        $respones = $client->request('post',$url,['verify'=>false,'body'=>$menu]);
        $data = $respones->getBody();
        echo $data;
    }
    //天气接口
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
    //获取用户基本信息
    public function getWxUserInfo($openid){
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $client = new Client();
        $respones = $client->request('GET',$url,['verify'=>false]);
        return json_decode($respones->getBody(),true);
    }
}