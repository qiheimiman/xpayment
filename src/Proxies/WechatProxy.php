<?php

/*
 * The file is part of the XPayment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace XPayment\Proxies;

use InvalidArgumentException;
use XPayment\Contracts\IGatewayRequest;
use XPayment\Contracts\IPayNotify;
use XPayment\Contracts\IPayProxy;
use XPayment\Contracts\IQueryProxy;
use XPayment\Contracts\ITransferProxy;
use XPayment\Exceptions\GatewayException;
use XPayment\Gateways\Wechat\Bill;
use XPayment\Gateways\Wechat\CancelTrade;
use XPayment\Gateways\Wechat\CloseTrade;
use XPayment\Gateways\Wechat\Notify;
use XPayment\Gateways\Wechat\Refund;
use XPayment\Gateways\Wechat\RefundQuery;
use XPayment\Gateways\Wechat\Settlement;
use XPayment\Gateways\Wechat\TradeQuery;
use XPayment\Gateways\Wechat\Transfer;
use XPayment\Gateways\Wechat\TransferBank;
use XPayment\Gateways\Wechat\TransferBankQuery;
use XPayment\Gateways\Wechat\TransferQuery;
use XPayment\XPayment;
use XPayment\Supports\BaseObject;

/**
 * @package XPayment\Proxys
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/28 10:25 PM
 * @version : 1.0.0
 * @desc    : 微信对外暴露的方案集合
 **/
class WechatProxy extends BaseObject implements IPayProxy, IQueryProxy, ITransferProxy
{
    /**
     * 支付操作
     * @param string $channel
     * @param array $requestParams
     * @return mixed
     * @throws \XPayment\Exceptions\GatewayException
     */
    public function pay(string $channel, array $requestParams)
    {
        $className = $this->getChargeClass($channel);
        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Gateway [%s] not exists.', $className), XPayment::CLASS_NOT_EXIST);
        }

        try {
            /**
             * @var IGatewayRequest $charge
             */
            $charge = new $className();
            return $charge->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 获取支付类
     * @param string $channel
     * @return string
     */
    private function getChargeClass(string $channel)
    {
        $name = ucfirst(str_replace(['-', '_', ''], '', $channel));
        return "XPayment\\Gateways\\Wechat\\{$name}Charge";
    }

    /**
     * 退款操作
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function refund(array $requestParams)
    {
        try {
            $trade = new Refund();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 异步通知
     * @param IPayNotify $callback
     * @return mixed
     * @throws GatewayException
     */
    public function notify(IPayNotify $callback)
    {
        try {
            $n    = new Notify();
            $data = $n->request(); // 获取数据
        } catch (GatewayException $e) {
            throw $e;
        }

        // 异步 async，同步 sync
        $flag = $callback->handle('Wechat', $data['notify_type'], 'async', $data['notify_data']);

        return $n->response($flag);
    }

    /**
     * 取消交易
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function cancel(array $requestParams)
    {
        try {
            $trade = new CancelTrade();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 关闭交易
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function close(array $requestParams)
    {
        try {
            $trade = new CloseTrade();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 交易查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function tradeQuery(array $requestParams)
    {
        try {
            $trade = new TradeQuery();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 退款查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function refundQuery(array $requestParams)
    {
        try {
            $trade = new RefundQuery();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 转账查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function transferQuery(array $requestParams)
    {
        $channel = $requestParams['channel'] ?? 'bank';

        try {
            if ($channel === 'bank') {
                $trade = new TransferBankQuery();
            } else {
                $trade = new TransferQuery();
            }

            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 账单查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function billDownload(array $requestParams)
    {
        try {
            $trade = new Bill();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 打款结算查询
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function settleDownload(array $requestParams)
    {
        try {
            $trade = new Settlement();
            return $trade->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * 转账
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function transfer(array $requestParams)
    {
        $channel = $requestParams['channel'] ?? 'bank';

        try {
            if ($channel === 'bank') {
                $trf = new TransferBank();
            } else {
                $trf = new Transfer();
            }

            return $trf->request($requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }
}
