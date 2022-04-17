<?php

/*
 * The file is part of the XPayment lib.
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
use XPayment\XPayment;
use XPayment\Sdk\Alipay\aop\AopClient;
use XPayment\Sdk\Alipay\aop\request\AlipayTradeRefundRequest;

/**
 * @package XPayment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/31 9:12 AM
 * @version : 1.0.0
 * @desc    : 统一收单交易退款接口
 **/
class Refund extends AliBaseObject implements IGatewayRequest
{
    const METHOD = 'alipay.trade.refund';

    /**
     * @param array $requestParams
     * @return mixed
     */
    protected function getBizContent(array $base,array $requestParams)
    {

        $aop = new AopClient ();
        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $base['app_id'];
        $aop->rsaPrivateKey = $base['rsaPrivateKey'];
        $aop->alipayrsaPublicKey= $base['alipayrsaPublicKey'];
        $aop->apiVersion = '1.0';
        $aop->signType = $base['sign_type'];
        $aop->postCharset ='UTF-8';
        $aop->format ='json';
        $object = new \stdClass();

        $object->out_trade_no = $requestParams['trade_no'];
        $object->refund_amount = $requestParams['refund_fee'];
        $object->out_request_no = $requestParams['refund_no'];

        $object->refund_reason = $requestParams['reason'];

        //// 返回参数选项，按需传入
        //$queryOptions =[
        //   'refund_detail_item_list'
        //];
        //$object->query_options = $queryOptions;
        $json = json_encode($object);
        $request = new AlipayTradeRefundRequest();
        $request->setBizContent($json);

        $result = $aop->execute ($request);

//        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
//        $resultCode = $result->$responseNode->code;
//        if(!empty($resultCode)&&$resultCode == 10000){
//            echo "成功";
//
//        } else {
//            echo "失败";
//        }

        return $result;
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
