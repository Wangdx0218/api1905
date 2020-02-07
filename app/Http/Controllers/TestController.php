<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function alipay()
    {
        $ali_gateway = 'https://openapi.alipaydev.com/gateway.do'; //支付网关
        //公共请求参数
        $appid = '2016101100661129';
        $method =  'alipay.trade.page.pay';
        $charset =  'utf-8';
        $signtype = 'RSA2';
        $sign = '';
        $timestamp = date('Y-m-d H:i:s');
        $version = '1.0';
        $return_url = 'http://1905api.comcto.com/test/alipay/return';
        $notify_url = 'http://1905api.comcto.com/test/alipay/notify';
        $biz_content = '';

        // 请求参数
        $out_trade_no = time() . rand(1111,9999);       //商户订单号
        $product_code = 'FAST_INSTANT_TRADE_PAY';
        $total_amount = 0.01;
        $subject = '测试订单' . $out_trade_no;

        $request_pram = [
            'out_trade_no' => $out_trade_no,
            'product_code' => $product_code,
            'total_amount' => $total_amount,
            'subject'      => $subject
        ];

        $param = [
            'app_id'        => $appid,
            'method'        => $method,
            'charset'       => $charset,
            'sign_type'     => $signtype,
            'timestamp'     => $timestamp,
            'version'       => $version,
            'return_url'    => $return_url,
            'notify_url'    => $notify_url,
            'biz_content'   => json_encode($request_pram)
        ];

        //echo '<pre>';print_r($_POST);echo '</pre>';

        //  字典序排序
        ksort($param);
        //echo '<pre>';print_r($_POST);echo '</pre>';

        //  2 拼接    key1-value1&key2-value2...
        $str = "";
        foreach ($param as $k=>$v)
        {
            $str .= $k . '=' . $v . '&';
        }

        $str = rtrim($str,'&');
        //echo 'str: '.$str;die;

        // 3、计算签名   https://docs.open.alipay.com/291/106118
        $key = storage_path('keys/app_priv');
        $priKey = file_get_contents($key);
        $res = openssl_get_privatekey($priKey);
        var_dump($res);echo '</br>';
        //var_dump($res);echo '</br>';
        openssl_sign($str, $sign, $res, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        $param['sign'] = $sign;

         // 4 urlencode
        $param_str = '?';
        foreach($param as $k=>$v){
            $param_str .= $k.'='.urlencode($v) . '&';
        }
        $param_str = rtrim($param_str,'&');
        $url = $ali_gateway . $param_str;
        //发送GET请求
        //echo $url;die;
        header("Location:".$url);
    }

    /**
     * 验证签名
     */
    public function sign1()
    {
        echo '<pre>';print_r($_GET);echo '</pre>';

        $sign = $_GET['sign'];      //base64签名
        unset($_GET['sign']);
        //字典序排序
        ksort($_GET);
        echo '<pre>';print_r($_GET);echo '</pre>';

        //  拼接  待签名 字符串
        $str = "";
        foreach ($_GET as $k=>$v)
        {
            $str .= $k . '=' . $v . '&';
        }

        $str = rtrim($str,'&');
        echo $str;echo '<hr>';

        //使用公钥验签
        $pub_key = file_get_contents(storage_path('keys/pubkey2'));
        $status = openssl_verify($str,base64_decode($sign),$pub_key,OPENSSL_ALGO_SHA256);
        var_dump($status);

        if ($status)    //验证通过
        {
            echo "success";
        }else{
            echo "验签失败";
        }
    }

    public  function sign2()
    {
        // 接收参数
        echo '<pre>';print_r($_GET);echo '</pre>';

        // 保存sign
        $ign = $_GET['sign'];
        echo "发送端的签名：". $sign1;echo '</br>';
        unset($_GET['sign']);

        ksort($_GET);

        echo '<pre>';print_r($_GET);echo '</pre>';
        //拼接待签名字符串
        $str = "";
        foreach ($_GET as $k=>$v)
        {
            $str .= $k. '=' . $v . '&';
        }

        $str = rtrim($str);
        echo "待签名字符串：". $str;

        //计算签名
        $sign2 = sha1($str . $sign_token);
        echo '</br>';
        echo "接收端计算的签名：" . $sign2;

        echo '</br>';
        if ($sign1 === $sign2){
            echo "验签成功";
        }else{
            echo "验签失败";
        }
    }
}
