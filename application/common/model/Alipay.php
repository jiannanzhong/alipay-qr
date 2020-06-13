<?php

namespace app\common\model;

use think\Log;

require __DIR__ . '/../../../alipay/aop/request/AlipayTradePrecreateRequest.php';

class Alipay
{
    public static function getConfig()
    {
        return [
            'alipayCertPublicKey' => file_get_contents(__DIR__ . '/../../../alipay/config/alipayCertPublicKey_RSA2.crt'),
            'alipayRootCert' => file_get_contents(__DIR__ . '/../../../alipay/config/alipayRootCert.crt'),
            'appCertPublicKey' => file_get_contents(__DIR__ . '/../../../alipay/config/appCertPublicKey.crt'),
            'rsaPrivateKey' => file_get_contents(__DIR__ . '/../../../alipay/config/rsaPrivateKey'),
            'appId' => file_get_contents(__DIR__ . '/../../../alipay/config/appId'),
            'notifyUrl' => file_get_contents(__DIR__ . '/../../../alipay/config/notifyUrl'),
            'sellerId' => file_get_contents(__DIR__ . '/../../../alipay/config/sellerId'),
            'gatewayUrl' => 'https://openapi.alipay.com/gateway.do',
        ];
    }

    public static function getAopClient($config = null)
    {
        if ($config == null) {
            $config = self::getConfig();
        }
        $aop = new MyAopCertClient();

        $aop->gatewayUrl = $config['gatewayUrl'];
        $aop->appId = $config['appId'];
        $aop->rsaPrivateKey = $config['rsaPrivateKey'];
        $aop->alipayrsaPublicKey = $aop->myGetPublicKey($config['alipayCertPublicKey']);//调用getPublicKey从支付宝公钥证书中提取公钥
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        $aop->isCheckAlipayPublicCert = true;//是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->appCertSN = $aop->myGetCertSN($config['appCertPublicKey']);//调用getCertSN获取证书序列号
        $aop->alipayRootCertSN = $aop->myGetRootCertSN($config['alipayRootCert']);//调用getRootCertSN获取支付宝根证书序列号
        return $aop;
    }

    public static function payByQr($amount)
    {
        $config = self::getConfig();
        $aop = self::getAopClient($config);
        $goodsName = '测试商品';
        $orderNo = GUIDHelper::getGUID32();
        $timeout = '5m';
        $price = $amount;

        $bizContent = "{\"body\":\"$goodsName\","
            . "\"subject\": \"$goodsName\","
            . "\"out_trade_no\": \"$orderNo\","
            . "\"timeout_express\": \"$timeout\","
            . "\"total_amount\": \"$price\","
            . "\"store_id\": \"GDZS_01\","
            . "\"terminal_id\": \"3\","
            . "\"product_code\":\"FACE_TO_FACE_PAYMENT\""
            . "}";

        $request = new \AlipayTradePrecreateRequest();
        $request->setBizContent($bizContent);
        $request->setNotifyUrl($config['notifyUrl']);

        // 首先调用支付api
//        $response = $this->aopclientRequestExecute($request, NULL, $req->getAppAuthToken());
        $response = $aop->execute($request);
        if (isset($response->alipay_trade_precreate_response->code) && $response->alipay_trade_precreate_response->code === '10000') {
            return $response->alipay_trade_precreate_response->qr_code;
        } else {
            return ['data' => $response];
        }
    }

    public static function checkSign(&$post, $body)
    {
        $config = self::getConfig();
        if (isset($post['fund_bill_list'])) {
            $post['fund_bill_list'] = str_replace('\\', '', $post['fund_bill_list']);
        }

        $aop = self::getAopClient();
        try {
            $flag = $aop->rsaCheckV2($post, NULL, "RSA2");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Log::alert($msg);
            return ['code' => 1, 'msg' => $msg];
        }
        if ($flag) {//|| true) {    //验签成功
            if (!isset($body['out_trade_no'])) {
                $msg = 'notify without out_trade_no';
                Log::alert($msg);
                return ['code' => 1, 'msg' => $msg];
            }

            if ($body['seller_id'] != $config['sellerId']) { //商家id不匹配
                $msg = "seller_id incorrect, ${body['seller_id']} != ${config['sellerId']}";
                Log::alert($msg);
                return ['code' => 1, 'msg' => $msg];
            }

            if ($body['app_id'] != $config['appId']) { //应用id不匹配
                $msg = "app_id incorrect, ${body['app_id']} != ${config['appId']}";
                Log::alert($msg);
                return ['code' => 1, 'msg' => $msg];
            }

            Log::info('check sign success, time is ' . time());
            return ['code' => 0, 'msg' => 'success'];
        } else { //验签失败
            $msg = 'check sign failed';
            Log::alert($msg);
            return ['code' => 1, 'msg' => $msg];
        }
    }
}