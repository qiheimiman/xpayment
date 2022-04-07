<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace XPayment\Gateways\Alipay;


use XPayment\Sdk\Alipay\aop\request\AlipayTradeAppPayRequest;
use XPayment\Sdk\Alipay\aop\AopCertClient;
use XPayment\Contracts\IGatewayRequest;
use XPayment\Exceptions\GatewayException;
use XPayment\Helpers\ArrayUtil;

/**
 * @package XPayment\Gateways\Alipay
 * @author  : Xjh
 * @email   : qiheimiman@gmail.com
 * @date    : 2022-04-05
 * @version : 1.0.0
 * @desc    : app支付-证书模式
 **/
class CertificateAppCharge extends AliBaseObject implements IGatewayRequest
{
    // 这个操作是在客户端发起的，服务端只负责组装参数
    const METHOD = 'alipay.trade.app.pay';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {
            $base = parent::getBaseData(self::METHOD);
            return $this->getBizContent($base, $requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 构建请求参数
     * @param array $base 基础参数
     * @param array $requestParams
     * @return mixed
     */
    protected function getBizContent(array $base, array $requestParams)
    {

        $timeoutExp = '';
        $timeExpire = intval($requestParams['time_expire']);
        if (!empty($timeExpire)) {
            $expire                      = floor(($timeExpire - time()) / 60);
            ($expire > 0) && $timeoutExp = $expire . 'm';// 超时时间 统一使用分钟计算
        }


        $aop = new AopCertClient;
        /** 支付宝网关 **/
        $aop->gatewayUrl = $this->gatewayUrl;
        /** 应用id,如何获取请参考：https://opensupport.alipay.com/support/helpcenter/190/201602493024 **/
        $aop->appId = $base['app_id'];
        /** 密钥格式为pkcs1，如何获取私钥请参考：https://opensupport.alipay.com/support/helpcenter/207/201602469554  **/
        $aop->rsaPrivateKey = $base['rsaPrivateKey'];
        /** 应用公钥证书路径，下载后保存位置的绝对路径  **/
        $appCertPath = $base['appCertPath'];
        /** 支付宝公钥证书路径，下载后保存位置的绝对路径 **/
        $alipayCertPath = $base['alipayCertPath'];
        /** 支付宝根证书路径，下载后保存位置的绝对路径 **/
        $rootCertPath = $base['rootCertPath'];
        /** 设置签名类型 **/
        $aop->signType= $base['sign_type'];
        /** 设置请求格式，固定值json **/
        $aop->format = "json";
        /** 设置编码格式 **/
        $aop->charset= "utf-8";
        /** 调用getPublicKey从支付宝公钥证书中提取公钥 **/
        $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
        /** 是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内 **/
        $aop->isCheckAlipayPublicCert = false;
        /** 调用getCertSN获取证书序列号 **/
        $aop->appCertSN = $aop->getCertSN($appCertPath);
        /** 调用getRootCertSN获取支付宝根证书序列号 **/
        $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);
        /** 实例化具体API对应的request类，类名称和接口名称对应，当前调用接口名称：alipay.trade.app.pay **/
        $request = new  AlipayTradeAppPayRequest ();

        $bizContent = [
            'timeout_express' => $timeoutExp,
            'total_amount'    => $requestParams['amount'] ?? '',
            'product_code'    => $requestParams['product_code'] ?? '',
            'body'            => $requestParams['body'] ?? '',
            'subject'         => $requestParams['subject'] ?? '',
            'out_trade_no'    => $requestParams['trade_no'] ?? '',
            'time_expire'     => $timeExpire ? date('Y-m-d H:i', $timeExpire) : '',
            'goods_type'      => $requestParams['goods_type'] ?? '',
            'promo_params'    => $requestParams['promo_params'] ?? '',
            'passback_params' => urlencode($requestParams['return_params'] ?? ''),
            'extend_params'   => $requestParams['extend_params'] ?? '',
            // 使用禁用列表
            //'enable_pay_channels' => '',
            'store_id'             => $requestParams['store_id'] ?? '',
            'specified_channel'    => $requestParams['specified_channel'] ?? 'pcredit', //支付宝原因，当前仅支持 pcredit
            'disable_pay_channels' => implode(self::$config->get('limit_pay', ''), ','),
            'ext_user_info'        => $requestParams['ext_user_info'] ?? '',
            'business_params'      => $requestParams['business_params'] ?? '',
        ];

        $json = json_encode($bizContent);
        /** 设置业务参数 **/
        $request->setBizContent($json);
        $request->setNotifyUrl($base['notify_url']);
        $result = $aop->sdkExecute ($request);
        return $result;
    }
}
