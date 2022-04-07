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

use XPayment\Exceptions\GatewayException;
use XPayment\Helpers\ArrayUtil;
use XPayment\Helpers\Rsa2Encrypt;
use XPayment\Helpers\RsaEncrypt;
use XPayment\Helpers\StrUtil;
use XPayment\Payment;
use XPayment\Supports\BaseObject;
use XPayment\Supports\HttpRequest;

/**
 * @package Payment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/30 8:44 PM
 * @version : 1.0.0
 * @desc    : 支付宝业务的基础类
 **/
abstract class AliBaseObject extends BaseObject
{
    use HttpRequest;

    const REQ_SUC = '10000';

    /**
     * @var string
     */
    protected $privateKey = '';

    /**
     * @var string
     */
    protected $publicKey = '';

    /**
     * @var string
     */
    protected $gatewayUrl = '';

    /**
     * @var bool
     */
    protected $isSandbox = false;

    /**
     * @var bool
     */
    protected $returnRaw = false;

    /**
     * AliBaseObject constructor.
     * @throws GatewayException
     */
    public function __construct()
    {

        $this->isSandbox = self::$config->get('use_sandbox', false);
        $this->returnRaw = self::$config->get('return_raw', false);

        // 初始 支付宝网关地址
        $this->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        if ($this->isSandbox) {
            $this->gatewayUrl = 'https://openapi.alipaydev.com/gateway.do';
        }

        $this->publicKey = self::$config->get('rsaPublicKey', false);
        $this->privateKey = self::$config->get('rsaPrivateKey', false);

    }

    /**
     * @param string $signType
     * @param string $signStr
     * @return string
     * @throws GatewayException
     */
    protected function makeSign(string $signType, string $signStr)
    {
        $signType = strtoupper($signType);
        try {
            switch ($signType) {
                case 'RSA':
                    $rsa = new RsaEncrypt($this->privateKey);

                    $sign = $rsa->encrypt($signStr);
                    break;
                case 'RSA2':
                    $rsa = new Rsa2Encrypt($this->privateKey);

                    $sign = $rsa->encrypt($signStr);
                    break;
                default:
                    throw new GatewayException(sprintf('[%s] sign type not support', $signType), Payment::PARAMS_ERR);
            }
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('sign error, sign type is [%s]. msg: [%s]', $signType, $e->getMessage()), Payment::SIGN_ERR);
        }

        return $sign;
    }

    /**
     * @param array $data
     * @param string $sign
     * @return bool
     * @throws GatewayException
     */
    protected function verifySign(array $data, string $sign)
    {
        $signType = strtoupper(self::$config->get('sign_type', ''));
        $preStr   = json_encode($data, JSON_UNESCAPED_UNICODE);

        try {
            if ($signType === 'RSA') {// 使用RSA
                $rsa = new RsaEncrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            } elseif ($signType === 'RSA2') {// 使用rsa2方式
                $rsa = new Rsa2Encrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            }
            throw new GatewayException(sprintf('[%s] sign type not support', $signType), Payment::PARAMS_ERR);
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('check ali pay sign failed, sign type is [%s]', $signType), Payment::SIGN_ERR, $data);
        }
    }

    /**
     * 针对异步通知的验证签名
     * @param array $data
     * @param string $sign
     * @param string $signType
     * @return bool
     * @throws GatewayException
     */
    protected function verifySignForASync(array $data, string $sign, string $signType)
    {
        $params = ArrayUtil::arraySort($data);

        try {
            $preStr = ArrayUtil::createLinkString($params);

            if ($signType === 'RSA') {// 使用RSA
                $rsa = new RsaEncrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            } elseif ($signType === 'RSA2') {// 使用rsa2方式
                $rsa = new Rsa2Encrypt($this->publicKey);
                return $rsa->rsaVerify($preStr, $sign);
            }
            throw new GatewayException(sprintf('[%s] sign type not support', $signType), Payment::PARAMS_ERR);
        } catch (\Exception $e) {
            throw new GatewayException(sprintf('check ali pay sign failed, sign type is [%s]', $signType), Payment::SIGN_ERR, $data);
        }
    }

    /**
     * @param string $method
     * @param array $requestParams
     * @return array
     * @throws GatewayException
     */
    protected function buildParams(string $method, array $requestParams)
    {
        $bizContent = $this->getBizContent($requestParams);
        $params     = $this->getBaseData($method, $bizContent);

        // 支付宝新版本  需要转码
        foreach ($params as &$value) {
            $value = StrUtil::characet($value, 'utf-8');
        }

        $params = ArrayUtil::arraySort($params);
        try {
            $signStr = ArrayUtil::createLinkString($params);

            $signType       = self::$config->get('sign_type', '');
            $params['sign'] = $this->makeSign($signType, $signStr);
        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GatewayException($e->getMessage(), Payment::PARAMS_ERR);
        }

        return $params;
    }

    /**
     * 获取基础数据
     * @param string $method
     * @param array $bizContent
     * @return array
     */
    protected function getBaseData(string $method)
    {
        $requestData = [
            'app_id'     => self::$config->get('app_id', ''),
            'method'     => $method,
            'format'     => 'JSON',
            'return_url' => self::$config->get('return_url', ''),
            'charset'    => 'utf-8',
            'sign_type'  => self::$config->get('sign_type', ''),
            'timestamp'  => date('Y-m-d H:i:s'),
            'version'    => '1.0',
            'notify_url' => self::$config->get('notify_url', ''),

            'alipayrsaPublicKey' => $this->publicKey,

            /** 密钥格式为pkcs1，如何获取私钥请参考：https://opensupport.alipay.com/support/helpcenter/207/201602469554  **/
            'rsaPrivateKey' => $this->privateKey,

            /** 应用公钥证书路径，下载后保存位置的绝对路径  **/
            'appCertPath' => self::$config->get('appCertPath', ''),

            /** 支付宝公钥证书路径，下载后保存位置的绝对路径 **/
            'alipayCertPath' => self::$config->get('alipayCertPath', ''),

            /** 支付宝根证书路径，下载后保存位置的绝对路径 **/
            'rootCertPath' => self::$config->get('rootCertPath', ''),

        ];


        return $requestData;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getBizContent(array $base, array $requestParams);
}
