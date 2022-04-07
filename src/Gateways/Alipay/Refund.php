<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Gateways\Alipay;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Payment;

/**
 * @package Payment\Gateways\Alipay
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
    protected function getBizContent(array $requestParams)
    {
        $bizContent = [
            'out_trade_no'              => $requestParams['trade_no'] ?? '',
            'trade_no'                  => $requestParams['transaction_id'] ?? '',
            'refund_amount'             => $requestParams['refund_fee'] ?? '',
            'refund_currency'           => $requestParams['refund_currency'] ?? 'CNY',
            'refund_reason'             => $requestParams['reason'] ?? '',
            'out_request_no'            => $requestParams['refund_no'] ?? '',
            'operator_id'               => $requestParams['operator_id'] ?? '',
            'store_id'                  => $requestParams['store_id'] ?? '',
            'terminal_id'               => $requestParams['terminal_id'] ?? '',
            'goods_detail'              => $requestParams['goods_detail'] ?? '',
            'refund_royalty_parameters' => $requestParams['refund_royalty_parameters'] ?? '',
            'org_pid'                   => $requestParams['org_pid'] ?? '',
        ];
        $bizContent = ArrayUtil::paraFilter($bizContent);

        return $bizContent;
    }

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = 'your app_id';
        $aop->rsaPrivateKey = '请填写开发者私钥去头去尾去回车，一行字符串';
        $aop->alipayrsaPublicKey='请填写支付宝公钥，一行字符串';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='GBK';
        $aop->format='json';
        $object = new stdClass();
        $object->trade_no = '2021081722001419121412730660';
        $object->refund_amount = 0.01;
        $object->out_request_no = 'HZ01RF001';
        //// 返回参数选项，按需传入
        //$queryOptions =[
        //   'refund_detail_item_list'
        //];
        //$object->query_options = $queryOptions;
        $json = json_encode($object);
        $request = new AlipayTradeRefundRequest();
        $request->setBizContent($json);

        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }
}
