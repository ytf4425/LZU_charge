<?php
/**
 * Created by PhpStorm.
 * User: Tommy Yu'
 * Date: 2019/4/18
 * Time: 12:53
 * @param $remote_server
 * @param $post_string
 * @return bool|string
 */

$username = $_REQUEST['username'];
$password = $_REQUEST['password'];
$amount = $_REQUEST['amount'];       //金额

function request_by_curl_post($remote_server, $post_string)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);              //POST
    curl_setopt($ch, CURLOPT_URL, $remote_server);      //url
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);   //POST信息
    curl_setopt($ch, CURLOPT_HEADER, true);     //获取响应头
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//接受跳转
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function request_by_curl_post_without_header($remote_server, $post_string, $cookie)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);              //POST
    curl_setopt($ch, CURLOPT_URL, $remote_server);      //url
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);   //POST信息
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);          //设置cookie
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//接受跳转
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function request_by_curl_get($remote_server, $cookie)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $remote_server);      //url
    curl_setopt($ch, CURLOPT_HEADER, true);     //获取响应头
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);          //设置cookie
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出
    //curl_setopt($ch,CURLOPT_USERAGENT,$ua);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function request_by_curl_get_header($remote_server, $cookie, $ua)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $remote_server);      //url
    curl_setopt($ch, CURLOPT_HEADER, true);     //获取响应头
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);          //设置cookie
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch,  CURLOPT_FOLLOWLOCATION, 1);
    $data = curl_exec($ch);
    $Headers = curl_getinfo($ch);
    curl_close($ch);
    return $Headers['url'];
}

$content = request_by_curl_post("http://my.lzu.edu.cn/userPasswordValidate.portal", "Login.Token1=$username&Login.Token2=$password&goto=http%3A%2F%2Fmy.lzu.edu.cn%2FloginSuccess.portal&gotoOnFail=http%3A%2F%2Fmy.lzu.edu.cn%2FloginFailure.portal");
//echo $content;
preg_match('/Set-Cookie:(.*);/iU', $content, $str);
$cookie = $str[1];
$content = request_by_curl_get("https://ecard.lzu.edu.cn/", $cookie);
preg_match('/Set-Cookie:(.*);/iU', $content, $str);
$cookie = $cookie . ';' . $str[1];
$content = request_by_curl_get("https://ecard.lzu.edu.cn/lzulogin", $cookie);
$content = request_by_curl_get("https://ecard.lzu.edu.cn/", $cookie);

//以下使用simple_html_dom来解析html获取校园卡cardAccNum
include 'simple_html_dom.php';
$html = new simple_html_dom();
$html->load($content);
$cardAccNum = $html->find('input[id=cardAccNum]', 0);
//echo $content;
$cardAccNum = $cardAccNum->attr['value'];
$html->clear();
//解析结束
$content = request_by_curl_post_without_header("https://ecard.lzu.edu.cn/scanRecharge/scanRechargeOnly", "amount=$amount&cardAccNum=$cardAccNum&eWalletId=1&productName=%E9%97%A8%E6%88%B7%E7%BD%91%E7%AB%99%E6%89%AB%E7%A0%81%E5%85%85%E5%80%BC&transInterfaceType=5", $cookie);
$chargeUrl = json_decode($content)->qrCodeUrl;
$content = request_by_curl_get_header($chargeUrl, '', 'Mozilla/5.0 (Linux; Android 9; MIX 2S Build/PKQ1.180729.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/73.0.3683.90 Mobile Safari/537.36 AlipayChannelId/5136 NebulaSDK/1.8.100112 Nebula AlipayDefined(nt:WIFI,ws:393|0|2.75,ac:sp) AliApp(AP/10.1.20.568) AlipayClient/10.1.20.568 Language/zh-Hans useStatusBar/true');

//echo $content;
header("Location: $content");