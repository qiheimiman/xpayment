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

use XPayment\Contracts\IGatewayRequest;
use XPayment\Exceptions\GatewayException;
use XPayment\Helpers\ArrayUtil;
use XPayment\Payment;
use XPayment\Sdk\Alipay\aop\AopCertClient;
use XPayment\Sdk\Alipay\aop\request\AlipayFundTransUniTransferRequest;

/**
 * @package Payment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/31 2:56 PM
 * @version : 1.0.0
 * @desc    : 单笔转账到支付宝账户
 **/
class Transfer extends AliBaseObject implements IGatewayRequest
{
    const METHOD = 'alipay.fund.trans.uni.transfer';


    /**
     * 构建请求参数
     * @param array $base 基础参数
     * @param array $requestParams
     * @return mixed
     */
    protected function getBizContent(array $base, array $requestParams)
    {

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

        $payee_info = [
            'identity'=> $requestParams['identity'], //收款人账号
            'identity_type'=>'ALIPAY_LOGON_ID',
            'name'=>  $requestParams['name'], //收款人姓名
        ];

        $payee_info = json_encode($payee_info);

        $data = [
            'out_biz_no'=> $requestParams['out_biz_no'],
            'trans_amount'=> $requestParams['trans_amount'],
            'product_code'=>'TRANS_ACCOUNT_NO_PWD',
            'biz_scene'=>'DIRECT_TRANSFER',
            'order_title'=> $requestParams['order_title'],//转账业务的标题，用于在支付宝用户的账单里显示。
            'payee_info'=>$payee_info,
            'business_params'=>[
                'payer_show_name_use_alias'=>true,
            ],
        ];

        $json = json_encode($data);
        $request = new AlipayFundTransUniTransferRequest();

        $request->setBizContent($json);

        $responseResult = $aop->execute($request);
        $responseApiName = str_replace(".","_",$request->getApiMethodName())."_response";
        $response = $responseResult->$responseApiName;
        return $response;
    }

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
        } catch (\XPayment\Exceptions\GatewayException $e) {
            throw $e;
        }
    }
}
