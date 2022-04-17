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

use XPayment\Sdk\Alipay\aop\AopClient;
use XPayment\Sdk\Alipay\aop\request\AlipayTradeAppPayRequest;

/**
 * @package XPayment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/28 10:21 PM
 * @version : 1.0.0
 * @desc    : app 支付
 **/
class AppCharge extends AliBaseObject implements IGatewayRequest
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

        $aop = new AopClient ();
        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $base['app_id'];
        $aop->rsaPrivateKey = $base['rsaPrivateKey'];
        $aop->alipayrsaPublicKey = $base['alipayrsaPublicKey'];
        $aop->apiVersion = '1.0';
        $aop->signType = $base['sign_type'];
        $aop->postCharset='utf-8';
        $aop->format='json';
        $object = new \stdClass();

        $object->out_trade_no = $requestParams['trade_no'] ?? '';
        $object->total_amount = $requestParams['amount'];
        $object->subject = $requestParams['subject'] ?? '';
        $object->product_code ='QUICK_MSECURITY_PAY';
        $object->time_expire = $timeExpire ? date('Y-m-d H:i', $timeExpire) : '';
        $object->passback_params = urlencode($requestParams['return_params'] ?? '');
        $object->disable_pay_channels = implode(self::$config->get('limit_pay', ''), ',');

        ////商品信息明细，按需传入
        // $goodsDetail = [
        //     [
        //         'goods_id'=>'goodsNo1',
        //         'goods_name'=>'子商品1',
        //         'quantity'=>1,
        //         'price'=>0.01,
        //     ],
        // ];
        // $object->goodsDetail = $goodsDetail;
        // //扩展信息，按需传入
        // $extendParams = [
        //     'sys_service_provider_id'=>'2088511833207846',
        // ];
        //  $object->extend_params = $extendParams;
        $json = json_encode($object);
        $request = new AlipayTradeAppPayRequest();
        $request->setNotifyUrl($base['notify_url']);
        $request->setBizContent($json);
        $result = $aop->sdkExecute ( $request);
        return $result;
    }

}
