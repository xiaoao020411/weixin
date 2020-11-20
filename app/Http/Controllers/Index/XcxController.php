<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class XcxController extends Controller
{
    public function test(){
        $goods_info = [
            'goods_id'  => 1313,
            'goods_name'    =>  'iphone',
            'price' =>  14.2
        ];
        echo json_encode($goods_info);
    }
    public function login(){
        $code = request()->get('code');
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_APPSEC').'&js_code='.$code.'&grant_type=authorization_code';
        $response = json_decode(file_get_contents($url),true);
        if(isset($data['errcode'])){
            //错误
            $data = [
                'error' =>  50001,
                'msg'   =>  '登陆失败',
            ];
        }else{
            $token = sha1($response['openid'] . $response['session_key'].mt_rand(0,999999));
            //保存token
            $redis_key = 'xcx_token:'.$token;
            Redis::set($redis_key,time());
            //设置过期时间
            Redis::expire($redis_key,7200);
            DB::table('xcx_user')->insert($response);
            $data = [
                'error' =>  0,
                'msg'   =>  'ok',
                $response = [
                    'token' =>  $token
                ]
            ];
        }
        return $data;
    }
    public function goods(){
        $pagesize = request()->size;
        $data = DB::table('p_goods')->limit(10)->paginate($pagesize);
        return $data;
    }
    public function list(){
        $id = request()->get('goods_id');
        $data = DB::table('p_goods')->where('goods_id',$id)->get();
        return $data;
    }
    public function home(){
        return view('xcx.home');
    }
}
