<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class UpdWXPayCertCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:update_wxpay_cert';
    // 配置参数
    private $mchid                = '';
    private $api_v3_key           = '';
    private $merchant_cert_serial = '';
    private $merchant_private_key = ''; // 商户私钥路径
    private $certDir              = ''; // 平台证书保存目录

    public function __construct() {
        // 配置参数
        $this->mchid = env('WECHATPAY_MERCHANT_ID');
        $this->api_v3_key = env('WECHATPAY_APIV3_SECRET_KEY');
        $this->certDir = config_path('crt/wechatpay/certificate/');
        $private_path = config_path('crt/wechatpay/apiclient_key.pem');
        $private_content = file_get_contents($private_path);
        $this->merchant_private_key = openssl_pkey_get_private($private_content);
        if (empty($this->merchant_private_key)) {
            echo '请检查商户私钥路径是否正确'.PHP_EOL;
            die;
        }
        $pub_path = config_path('crt/wechatpay/apiclient_cert.pem');
        $pub_content = file_get_contents($pub_path);
        $cert = openssl_x509_parse($pub_content);
        $this->merchant_cert_serial = $cert['serialNumberHex'];
        if (empty($this->merchant_cert_serial)) {
            echo '请检查商户公钥路径是否正确'.PHP_EOL;
            die;
        }
        parent::__construct();
    }

    public function handle() {
        $method = 'GET';
        $url = '/v3/certificates';  // 接口路径（不含域名）
        $timestamp = time();        // 当前时间戳（秒级）
        $nonce = bin2hex(random_bytes(8)); // 随机字符串
        // 生成签名
        $signature = $this->generateSignature($method, $url, $timestamp, $nonce);
        // 构造Authorization头
        $authHeader = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->mchid,
            $nonce,
            $timestamp,
            $this->merchant_cert_serial,
            $signature
        );
        // 发送HTTP请求
        $headers = [
            'Accept: application/json',
            'User-Agent: PHP/'.PHP_VERSION,
            'Authorization: '.$authHeader
        ];
        $requrl = 'https://api.mch.weixin.qq.com/v3/certificates';
        $response = $this->sendRequest($requrl, $headers);
        $responseData = json_decode($response, true);
        if (empty($responseData['data'])) {
            echo '获取平台证书失败'.json_encode($responseData).PHP_EOL;
            die;
        }
        foreach ($responseData['data'] as $certInfo) {
            $cipherText = $certInfo['encrypt_certificate']['ciphertext'];
            $associatedData = $certInfo['encrypt_certificate']['associated_data'];
            $nonce = $certInfo['encrypt_certificate']['nonce'];
            // 使用 APIv3 密钥解密
            $plainText = $this->decryptCertificate($cipherText, $associatedData, $nonce);
            // 4. 保存证书文件（文件名用序列号标识）
            $filename = $this->certDir.$certInfo['serial_no'].'.pem';
            file_put_contents($filename, $plainText);
            echo "证书 {$certInfo['serial_no']} 已保存至 {$filename}\n";
        }
    }

    // 发送请求
    function sendRequest($url, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    private function generateSignature($method, $url, $timestamp, $nonce, $body = '') {
        // 构造签名串（严格按协议格式）
        $signingString = implode("\n", [$method, $url, $timestamp, $nonce, $body])."\n";
        // 使用SHA256-RSA签名
        openssl_sign($signingString, $signature, $this->merchant_private_key, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    // 解密证书
    function decryptCertificate($ciphertext, $associatedData, $nonce) {
        // 确保密文是 Base64 解码后的二进制数据
        $ciphertext = base64_decode($ciphertext);
        if ($ciphertext === false) {
            throw new \Exception("密文 Base64 解码失败");
        }

        // 提取认证标签
        $tag = substr($ciphertext, -16);
        $ciphertext = substr($ciphertext, 0, -16);

        // 进行解密操作
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->api_v3_key, OPENSSL_RAW_DATA, $nonce, $tag, $associatedData);

        if ($plaintext === false) {
            throw new \Exception("解密失败: ". openssl_error_string());
        }

        return $plaintext;
    }


    // 解密证书数据（AES-256-GCM）
    private function decrypt($cipherText, $associatedData, $nonce) {
        $aesKey = base64_decode($this->api_v3_key);
        $cipherText = base64_decode($cipherText);

        // 分离加密数据和GCM的tag（最后16字节）
        $tagLength = 16;
        $tag = substr($cipherText, -$tagLength);
        $actualCiphertext = substr($cipherText, 0, -$tagLength);

        $plainText = openssl_decrypt(
            $actualCiphertext,
            'aes-256-gcm',
            $aesKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $associatedData
        );

        if ($plainText === false) {
            throw new \Exception("解密失败: " . openssl_error_string());
        }

        return $plainText;
    }


}
