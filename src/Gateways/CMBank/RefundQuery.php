<?php

/*
 * The file is part of the XPayment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace XPayment\Gateways\CMBank;

use XPayment\Contracts\IGatewayRequest;
use XPayment\Exceptions\GatewayException;

/**
 * @package XPayment\Gateways\CMBank
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/11/27 7:43 PM
 * @version : 1.0.0
 * @desc    : 查询具体某笔交易的退款状态
 **/
class RefundQuery extends CMBaseObject implements IGatewayRequest
{
    const METHOD = 'NetXPayment/BaseHttp.dll?QuerySettledRefundV2';

    const SANDBOX_METHOD = 'netXPayment_dl/BaseHttp.dll?QuerySettledRefundV2';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        $method           = self::METHOD;
        $this->gatewayUrl = 'https://XPayment.ebank.cmbchina.com/%s';
        if ($this->isSandbox) {
            $method           = self::SANDBOX_METHOD;
            $this->gatewayUrl = 'http://121.15.180.66:801/%s';
        }
        try {
            return $this->requestCMBApi($method, $requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    protected function getRequestParams(array $requestParams)
    {
        $nowTime = time();

        $params = [
            'dateTime'         => date('YmdHis', $nowTime),
            'branchNo'         => self::$config->get('branch_no', ''),
            'merchantNo'       => self::$config->get('mch_id', ''),
            'type'             => $requestParams['type'] ?? 'A',
            'orderNo'          => $requestParams['trade_no'] ?? '',
            'date'             => date('Ymd', $requestParams['date'] ?? $nowTime),
            'merchantSerialNo' => $requestParams['refund_no'] ?? '',
            'bankSerialNo'     => $requestParams['bank_serial_no'] ?? '',
        ];

        return $params;
    }
}
