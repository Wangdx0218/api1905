<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
class TestController extends Controller
{
    public function test()
    {
        echo '<pre>';print_r($_SERVER);echo '</pre>';
    }
    /**
     * 用户注册
     */
    public function reg(Request $request)
    {
        echo '<pre>';print_r($request->input());echo '</pre>';
        //验证用户名 验证email 验证手机号
        $pass1 = $request->input('pass1');
        $pass2 = $request->input('pass2');
        if($pass1 != $pass2){
            die("两次输入的密码不一致");
        }
        $password = password_hash($pass1,PASSWORD_BCRYPT);
        $data = [
            'email'         => $request->input('email'),
            'name'          => $request->input('name'),
            'password'      => $password,
            'mobile'        => $request->input('mobile'),
            'last_login'    => time(),
            'last_ip'       => $_SERVER['REMOTE_ADDR'],     //获取远程IP
        ];
        $uid = UserModel::insertGetId($data);
        var_dump($uid);
    }
    /**
     * 用户登录接口
     * @param Request $request
     * @return array
     */
    public function login(Request $request)
    {
        $name = $request->input('name');
        $pass = $request->input('pass');
        $u = UserModel::where(['name'=>$name])->first();
        if($u){
            //验证密码
            if( password_verify($pass,$u->password) ){
                // 登录成功
                //echo '登录成功';
                //生成token
                $token = Str::random(32);
                $response = [
                    'errno' => 0,
                    'msg'   => 'ok',
                    'data'  => [
                        'token' => $token
                    ]
                ];
            }else{
                $response = [
                    'errno' => 400003,
                    'msg'   => '密码不正确'
                ];
            }
        }else{
            $response = [
                'errno' => 400004,
                'msg'   => '用户不存在'
            ];
        }
        return $response;
    }
    /**
     * 获取用户列表
     * 2020年1月2日16:32:07
     */
    public function userList()
    {
        $user_token = $_SERVER['HTTP_TOKEN'];
        echo 'user_token: '.$user_token;echo '</br>';
        $current_url = $_SERVER['REQUEST_URI'];
        echo "当前URL: ".$current_url;echo '<hr>';
        //echo '<pre>';print_r($_SERVER);echo '</pre>';
        //$url = $_SERVER[''] . $_SERVER[''];
        $redis_key = 'str:count:u:'.$user_token.':url:'.md5($current_url);
        echo 'redis key: '.$redis_key;echo '</br>';
        $count = Redis::get($redis_key);        //获取接口的访问次数
        echo "接口的访问次数： ".$count;echo '</br>';
        if($count >= 5){
            echo "请不要频繁访问此接口，访问次数已到上限，请稍后再试";
            Redis::expire($redis_key,3600);
            die;
        }
        $count = Redis::incr($redis_key);
        echo 'count: '.$count;
    }

    public function postman()
    {
        echo __METHOD__;
    }

    public function postman()
    {
        //获取用户标识
        $token = $_SERVER['HTTP_TOKEN'];
        // 当前url
        $request_uri = $_SERVER['REQUEST_URI'];

        $url_hash = md5($token . $request_uri);


        //echo 'url_hash: ' .  $url_hash;echo '</br>';
        $key = 'count:url:'.$url_hash;
        //echo 'Key: '.$key;echo '</br>';

        //检查 次数是否已经超过限制
        $count = Redis::get($key);
        echo "当前接口访问次数为：". $count;echo '</br>';

        if($count >= 5){
            $time = 10;     // 时间秒
            echo "请勿频繁请求接口, $time 秒后重试";
            Redis::expire($key,$time);
            die;
        }


        // 访问数 +1
        $count = Redis::incr($key);
        echo 'count: '.$count;

    }

    public function md5()
    {
        $data = "Wangdxvm";      //要发送的数据
        $key = "1998";           //计算签名的key

        //计算签名  MD5($data . $key)
//        $signature = 'sdlfkjsldfkjsfd';
        $signature = md5($data . $key);

        echo "待发送的数据：" . $data;echo '</br>';
        echo "签名：" . $signature;echo '</br>';

        //发送数据
        $url = "http://passport.1905.com/test/check?data=" . $data . '&signature=' . $signature;
        echo $url;echo '<hr>';

        $response = file_get_contents($url);
        echo $response;
    }

    public function sign()
    {
        $key = "1998";          // 签名使用key发送端与接收端使用同一个key计算签名

        //待签名的数据
        $order_info = [
            "order_id"          => 'LN_' . mt_rand(111111,999999),
            "order_amount"      => mt_rand(111,999),
            "uid"               => 112233,
            "add_time"          => time(),
        ];

        $data_json = json_encode($order_info);

        //计算签名
        $sign = md5($data_json.$key);

        // post 表单（form-data）发送数据
        $client = new Client();
        $url = 'http://passport.1905.com/test/check1';
        $response = $client->request("POST",$url,[
            "form_params"   => [
                "data"  => $data_json,
                "sign"  => $sign
            ]
        ]);

        //接收服务器端响应的数据
        $response_data = $response->getBody();
        echo $response_data;

    }

    public function md52()
    {
        //待签名数据
        $qian="wang";
        echo "原始数据 ：".$qian;echo '</br>';
        //计算签名
        $path=storage_path('keys/app_priv');
        $pkeyid=openssl_pkey_get_private("file://".$path);

        openssl_sign($qian,$signature,$pkeyid);
        openssl_free_key($pkeyid);
        //var_dump($signature);
        echo "原数据签名后 ：".$signature;echo '</br>';

        //base64编码
        $sign_str=base64_encode($signature);
        echo "base64_encode后数据 ：".$sign_str;echo '<hr>';

        $url="http://passport.1905.com/test/check2?".'qian='.$qian.'&sign='.urlencode($sign_str);
        //echo $url;
        $response=file_get_contents($url);
        echo $response;

    }

    public function encrypt()
    {
        $data = "helloworld";

        echo "原文：" . $data;echo '</br>';
        $method = 'AES-256-CBC';
        $key = 'wang';
        $iv = 'WUSD8796IDjhkchd';

        $enc_data = openssl_encrypt($data,$method,$key,OPENSSL_RAW_DATA,$iv);
        echo "加密后密文：" . $enc_data;echo '</br>';
        echo '<hr>';
        echo '解密：';echo '<br>';

        //发送加密数据
        $url = "http://passport.1905.com/decrypt?data=".urlencode(base64_encode($enc_data));
        echo $url;echo '<br>';
        $response = file_get_contents($url);
        echo $response;
    }

    public function rsa()
    {
        $priv_key = file_get_contents(storage_path('keys/app_priv'));

        $data = "helloworld";
        echo "待加密数据：" . $data;echo '</br>';

        openssl_private_encrypt($data,$enc_data,$priv_key);
//        var_dump($enc_data);

        echo '<hr>';
        // 将密文 发送至 对方
        $base64_encode_str = base64_encode($enc_data);
        echo '<pre>';print_r($base64_encode_str);echo '</pre>';echo '<hr>';
        $url = 'http://passport.1905.com/rsadescypt?data='.urlencode($base64_encode_str);
        echo $url;
        $response = file_get_contents($url);    //发送请求
        echo $response;
    }
}
