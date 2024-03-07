<?php

namespace App\Models;
use App\Models\Base;

class WechatTool extends Base
{
    public array $config = [];

    public static string $MERCHANT_NAME = '';
    public static string $DESCRIPTION = '';
    public static string $GETWAY_HOST = '';
    public static string $GETWAY_HOST_BACKUP = '';
    public static string $MERCHANT_ID = '';
    public static string $APPID = '';
    public static string $SECRET = '';
    public static string $MERCHANT_SERIAL_NUMBER = '';
    public static string $MERCHANT_PRIVATE_KEY = '';
    public static string $APIV3_SECRET_KEY = '';
    public static string $CERTIFICATE_FOLDER = '';

    public function __construct($config = [])
    {
        $this->config = $config;
        self::$MERCHANT_NAME = env('WECHATPAY_MERCHANT_NAME');
        self::$DESCRIPTION = env('WECHATPAY_DESCRIPTION');
        self::$GETWAY_HOST = env('WECHATPAY_GETWAY_HOST');
        self::$GETWAY_HOST_BACKUP = env('WECHATPAY_GETWAY_HOST_BACKUP');
        self::$MERCHANT_ID = env('WECHATPAY_MERCHANT_ID');
        self::$APPID = env('WECHATPAY_APPID');
        self::$SECRET = env('WECHATPAY_SECRET');
        self::$MERCHANT_SERIAL_NUMBER = env('WECHATPAY_MERCHANT_SERIAL_NUMBER');
        self::$MERCHANT_PRIVATE_KEY = base_path().env('WECHATPAY_MERCHANT_PRIVATE_KEY');
        self::$APIV3_SECRET_KEY = env('WECHATPAY_APIV3_SECRET_KEY');
        self::$CERTIFICATE_FOLDER = base_path().env('WECHATPAY_CERTIFICATE_FOLDER');
    }

    public function getOAuthUrl($redirectUri, $state, $scope = 'snsapi_base')
    {
        // $referer = empty($referer) ? ($_SERVER['HTTP_REFERER'] ?? '') : $referer;
        if ($scope != 'snsapi_base' || $scope != 'snsapi_userinfo ') {
            $scope == 'snsapi_base';
        }
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $query = http_build_query([
                'appid' => self::$APPID,
                // 'redirect_uri' => Yii::$app->params['frontend_domain'].'/api/order/we2?referer='.urlencode($referer),
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $state,
                // 'connect_redirect' => '1',
            ]);
        $url = $url.'?'.$query.'#wechat_redirect';

        return $url;
    }

    public function getOpenid($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $query = http_build_query([
                'appid' => self::$APPID,
                'secret' => self::$SECRET,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);
        $url = $url.'?'.$query;

        $json = file_get_contents($url);
        $arr = json_decode($json, true);
        $openid = $arr['openid'] ?? null;
        if ($openid === null) {
            throw new \Exception('openid not found');
        }

        return $openid;
    }
}
