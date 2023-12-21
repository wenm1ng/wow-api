<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-03 11:59
 */
namespace App\Work\WxPay\Service;

use Common\Common;
use Wa\Models\WowWaTabTitleModel;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use EasySwoole\EasySwoole\Config;
use WeChatPay\Formatter;

class WxPayService{
    const AUTH_TAG_LENGTH_BYTE = 16;
    //商户号
    protected static $merchantId;
    //商户API私钥文件路径
    protected static $merchantPrivateKeyFilePath;
    //商户API证书序列号
    protected static $merchantCertificateSerial;
    //微信支付平台证书签名路径
    protected static $platformCertificateFilePath;
    //请求实例
    protected static $instance;
//    protected static \WeChatPay\BuilderChainable $instance;
    //appid
    protected static $appId;
    protected static $logName = 'wxPay';
    //支付回调链接
    protected static $callbackUrl;
    //app v3秘钥
    protected static $v3Secret;
    protected static $returnData = [
        'code' => 0,
        'message' => '',
        'data' => []
    ];

    public function __construct()
    {
        self::$merchantId = Config::getInstance()->getConf('app.MERCHANT_ID');
        self::$merchantPrivateKeyFilePath = Config::getInstance()->getConf('app.MERCHANT_PRIVATE_KEY_FILE_PATH');
        self::$merchantCertificateSerial = Config::getInstance()->getConf('app.MERCHANT_CERTIFICATE_SERIAL');
        self::$platformCertificateFilePath = Config::getInstance()->getConf('app.PLATFORM_CERTIFICATE_FILE_PATH');
        self::$appId = Config::getInstance()->getConf('app.APP_KEY');
        self::$callbackUrl = Config::getInstance()->getConf('app.MERCHANT_PAY_CALLBACK_URL');
        self::$v3Secret = Config::getInstance()->getConf('app.APP_V3_SECRET');
        self::init();
    }

    public static function init(){
        $merchantPrivateKeyInstance = Rsa::from('file://'.self::$merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

        $platformPublicKeyInstance = Rsa::from('file://'.self::$platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

        $platformCertificateSerial = PemUtil::parseCertificateSerialNo('file://'.self::$platformCertificateFilePath);

        // 构造一个 APIv3 客户端实例
        self::$instance = Builder::factory([
            'mchid'      => self::$merchantId,
            'serial'     => self::$merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);
    }

    /**
     * @desc        添加wx预支付订单
     * @example
     * @param int    $money
     * @param string $openId
     *
     * @return mixed
     */
    public function wxAddOrder(int $money, string $openId, string $orderNo){

        try {
            $data = [
                'json' => [
                    'mchid'        => self::$merchantId,
                    'out_trade_no' => $orderNo,
                    'appid'        => self::$appId,
                    'description'  => 'WOW WA仓库-帮币',
                    'notify_url'   => self::$callbackUrl,
                    'amount'       => [
                        'total'    => $money, //单位分
                        'currency' => 'CNY'
                    ],
                    'payer' =>[
                        'openid' => $openId
                    ],
                ],
//                    'debug' => true
            ];
            Common::log('wxAddOrder_requestData:'.json_encode($data, JSON_UNESCAPED_UNICODE), self::$logName);

            $resp = self::$instance
                ->chain('v3/pay/transactions/jsapi')
                ->post($data);

            self::$returnData['code'] = $resp->getStatusCode();
            self::$returnData['data'] = json_decode($resp->getBody(), true);
            Common::log('wxAddOrder_responseData:'.$resp->getBody(), self::$logName);

        } catch (\Exception $e) {
            // 进行错误处理
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                Common::log('wxAddOrder_code:'.$r->getStatusCode(). ';body:'.$r->getReasonPhrase(), self::$logName);
            }
            Common::log('wxAddOrder_errorMsg:'.$e->getMessage(), self::$logName);
            self::$returnData['code'] = $r->getStatusCode();
            self::$returnData['message'] = $e->getMessage();
        }
        return self::$returnData;
    }

    /**
     * @desc        获取支付签名相关信息
     * @example
     * @param string $prepayId
     *
     * @return array
     */
    public static function getSign(string $prepayId){
        $merchantPrivateKeyFilePath = 'file://'.self::$merchantPrivateKeyFilePath;
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath);

        $params = [
            'appId'     => self::$appId,
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr'  => Formatter::nonce(),
            'package'   => 'prepay_id='.$prepayId,
        ];
        $params += ['paySign' => Rsa::sign(
            Formatter::joinedByLineFeed(...array_values($params)),
            $merchantPrivateKeyInstance
        ), 'signType' => 'RSA'];

        return $params;
    }

    /**
     * 解密报文
     *
     * @param string    $associatedData     AES GCM additional authentication data
     * @param string    $nonceStr           AES GCM nonce
     * @param string    $ciphertext         AES GCM cipher text
     *
     * @return string|bool      Decrypted string on success or FALSE on failure
     */
    public function decryptToString($associatedData, $nonceStr, $ciphertext)
    {
        $ciphertext = base64_decode($ciphertext);
        if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
            return false;
        }
        // ext-sodium (default installed on >= PHP 7.2)
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') &&
            \sodium_crypto_aead_aes256gcm_is_available()) {
            dump(1);
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, self::$v3Secret);
        }

        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') &&
            \Sodium\crypto_aead_aes256gcm_is_available()) {
            dump(2);
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, self::$v3Secret);
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);
            dump(3);
            return \openssl_decrypt($ctext, 'aes-256-gcm', self::$v3Secret, \OPENSSL_RAW_DATA, $nonceStr,
                $authTag, $associatedData);
        }

        throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }
}