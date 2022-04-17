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

/**
 * @package XPayment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/31 9:15 AM
 * @version : 1.0.0
 * @desc    :
 **/
class TradeQuery extends AliBaseObject implements IGatewayRequest
{
    const METHOD = 'alipay.trade.query';

    /**
     * @param array $requestParams
     * @return mixed
     */
    protected function getBizContent(array $requestParams)
    {
        $bizContent = [
            'out_trade_no'  => $requestParams['trade_no'] ?? '',
            'trade_no'      => $requestParams['transaction_id'] ?? '',
            'query_options' => $requestParams['query_options'] ?? '',
            'org_pid'       => $requestParams['org_pid'] ?? '',
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
        try {
            $params = $this->buildParams(self::METHOD, $requestParams);
            $ret    = $this->get($this->gatewayUrl, $params);
            $retArr = json_decode($ret, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new GatewayException(sprintf('format refund data get error, [%s]', json_last_error_msg()), XPayment::FORMAT_DATA_ERR, ['raw' => $ret]);
            }

            $content = $retArr['alipay_trade_query_response'];
            if ($content['code'] !== self::REQ_SUC) {
                throw new GatewayException(sprintf('request get failed, msg[%s], sub_msg[%s]', $content['msg'], $content['sub_msg']), XPayment::SIGN_ERR, $content);
            }

            $signFlag = $this->verifySign($content, $retArr['sign']);
            if (!$signFlag) {
                throw new GatewayException('check sign failed', XPayment::SIGN_ERR, $retArr);
            }

            return $content;
        } catch (GatewayException $e) {
            throw $e;
        }
    }
}
