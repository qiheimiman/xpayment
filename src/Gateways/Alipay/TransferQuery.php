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
use XPayment\Sdk\Alipay\aop\AopClient;
use XPayment\Sdk\Alipay\aop\AlipayConfig;

use XPayment\Sdk\Alipay\aop\request\AlipayFundTransCommonQueryRequest;



/**
 * @package Payment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/31 3:23 PM
 * @version : 1.0.0
 * @desc    : 商户可通过该接口查询转账订单的状态、支付时间等相关信息，主要应用于B2C转账订单查询的场景
 **/
class TransferQuery extends AliBaseObject implements IGatewayRequest
{
    const METHOD = 'alipay.fund.trans.common.query';

    /**
     * @param array $base
     * @param array $requestParams
     * @return mixed
     */
    protected function getBizContent(array $base, array $requestParams)
    {


        $privateKey = $base['rsaPrivateKey']; //私钥
        $alipayPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyzZe+mW+T9xRki1WJoGQrjzg97X3AUhcRYvDb5RpTFIS9P3vyl0mJENEPhjS5EBUWyrtqlVg8RNckSYwsn7IKcy3+qQU7v9VESNYgjoxtA78h+fzMB+BTye6Al/jHlxYqYn2Q8/PcrMdYF6P7On3Npvgf81fNYPElJ3JKmZ10U40ejcs4JrCTmdPvfvV5yAu25nQMzk+vj+sl/4UUZxJJcRpe1yJhWucZ8EnDZbr4O9ucqSCjPS8U0tuKlrnwvhnGOd/gM8zVMhNEXuEhR5Lq+06449tYUGb5OMNMQjzcLXQtIYf6tm0CwmYkgYjqwWxQyLlc3iju4kKK8z1KWK8LQIDAQAB';//支付宝公钥
        $alipayConfig = new AlipayConfig();
        $alipayConfig->setServerUrl($this->gatewayUrl);
        $alipayConfig->setAppId($base['app_id']);
        $alipayConfig->setPrivateKey($privateKey);
        $alipayConfig->setFormat("json");
//        $alipayConfig->setAlipayPublicKey($alipayPublicKey);
        $alipayConfig->setCharset("UTF-8");
        $alipayConfig->setSignType($base['sign_type']);

        $alipayConfig->setRootCertPath( $base['rootCertPath']);
        $alipayConfig->setAppCertPath( $base['appCertPath']);
        $alipayConfig->setAlipayPublicCertPath( $base['alipayCertPath']);


        $alipayClient = new AopClient($alipayConfig);

        $request = new AlipayFundTransCommonQueryRequest();
        $request->setBizContent("{".
            "\"out_biz_no\":\"100001111111\",".
            "\"order_id\":\"20220405020070011500090025835210\",".
            "\"biz_scene\":\"DIRECT_TRANSFER\",".
            "\"pay_fund_order_id\":\"20220405020070011500090025835210\",".
            "\"product_code\":\"TRANS_ACCOUNT_NO_PWD\"".
            "}");
        $responseResult = $alipayClient->execute($request);
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
        } catch (GatewayException $e) {
            throw $e;
        }
    }
}
