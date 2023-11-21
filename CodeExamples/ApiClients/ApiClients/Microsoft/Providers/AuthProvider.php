<?php

namespace App\ApiClients\Microsoft\Providers;

use App\ApiClients\Client;
use App\ApiClients\Helpers;

class AuthProvider{
    use Client;

    protected $baseUrl = 'https://login.microsoftonline.com';
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;
    protected $mainAccount;

    protected $isSso;
    protected $key;
    protected $defaultKey;
    protected $ssoKey;

    protected $timeout = 10;

    public function __construct($isSso = false){
        $this->defaultKey = config('keys.microsoft.defult.key');
        $this->ssoKey = config('keys.microsoft.sso.key');
        $this->isSso = $isSso;

        $this->key = ($this->isSso?$this->ssoKey:$this->defaultKey);
    }

    public function getAuthUrl($redirectUrl = ''){
        $this->clientId = config('keys.microsoft.sso.client_id');
        $this->clientSecret = config('keys.microsoft.sso.client_secret');
        $this->tenantId = config('keys.microsoft.sso.client_tenant');
        $this->key = $this->ssoKey;

        return url("$this->baseUrl/$this->tenantId/oauth2/v2.0/authorize?".http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
            'response_mode' => 'query',
            'scope' => 'openid offline_access https://graph.microsoft.com/.default'
        ]));
    }

    public function getAccessToken(): object{
        $accessToken = Helpers::get($this->key);
        if (!$accessToken) {
            $accessToken = $this->auth();
            if (!$accessToken){
                return false;
            }
        }
        return $accessToken;
    }

    public function auth(){
        $this->clientId = config('keys.microsoft.defult.client_id');
        $this->clientSecret = config('keys.microsoft.defult.client_secret');
        $this->tenantId = config('keys.microsoft.defult.client_tenant');
        $this->key = $this->defaultKey;

        $response = $this->postData($headers = [],  $url = "$this->baseUrl/$this->tenantId/oauth2/v2.0/token", 
            [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'openid offline_access https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
            $this->timeout,
            $asForm = true
        );

        if ($response['statusCode']!=200){
            return false;
        }
        $accessToken = Helpers::save($this->key, $response['data']);
        return $accessToken;
    }

    public function callback($code = '', $redirectUrl = ''){
        $this->clientId = config('keys.microsoft.sso.client_id');
        $this->clientSecret = config('keys.microsoft.sso.client_secret');
        $this->tenantId = config('keys.microsoft.sso.client_tenant');
        $this->key = $this->ssoKey;

        $response = $this->postData($headers = [], $url = "$this->baseUrl/$this->tenantId/oauth2/v2.0/token",
            [
                'client_id' => $this->clientId,
                'scope' => 'openid offline_access https://graph.microsoft.com/.default',
                'code' => $code,
                'redirect_uri' => $redirectUrl,
                'grant_type' => 'authorization_code',
                'client_secret' => $this->clientSecret
            ],
            $this->timeout,
            $asForm = true
        );

        if ($response['statusCode']!=200){
            return false;
        }

        $accessToken = Helpers::save($this->key, $response['data']);
        return $accessToken;
    }

    public function refreshToken(){
        $this->clientId = config('keys.microsoft.defult.client_id');
        $this->clientSecret = config('keys.microsoft.defult.client_secret');
        $this->tenantId = config('keys.microsoft.defult.client_tenant');
        $this->mainAccount = config('keys.microsoft.details.main_account');
        $this->key = $this->ssoKey.$this->mainAccount;
        
        $accessToken = Helpers::get($this->key.$this->mainAccount);
        $response = $this->postData($headers = [], "$this->baseUrl/$this->tenantId/oauth2/v2.0/token", 
            [
                'client_id' => $this->clientId,
                'scope' => 'openid offline_access https://graph.microsoft.com/.default',
                'refresh_token' => $accessToken->refresh_token,
                'grant_type' => 'refresh_token',
                'client_secret' => $this->clientSecret
            ],
            $this->timeout,
            $asForm = true
        );

        if ($response['statusCode']!=200){
            return false;
        }
        
        $accessToken = Helpers::save($this->key, $response['data']);
        return $accessToken;
    }

    public function saveToken($key, $value): void{
        Helpers::save($key, $value);
    }

}