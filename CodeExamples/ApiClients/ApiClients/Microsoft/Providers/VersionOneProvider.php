<?php

namespace App\ApiClients\Microsoft\Providers;

use App\ApiClients\Client;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class VersionOneProvider{
    
    use Client;

    protected $graphUrl = 'https://graph.microsoft.com';
    protected $contentType = 'application/json';

    protected $token;
    protected $v;
    protected $timeout;
    protected $isSso;

    protected $authProvider;

    public function __construct($isSso = false, $v = 'v1.0', $timeout = 10){
        $this->isSso = $isSso;
        $this->v = $v;
        $this->timeout = $timeout;

        $this->authProvider = new AuthProvider($isSso);
        $accessToken = $this->authProvider->getAccessToken();
        $this->token = $accessToken->access_token;
    }

    public function post(string $url, array $data = []): array|string{
        $url = "$this->graphUrl/$this->v".$url;
        return $this->call($url, 'post', $data);
    }

    public function get(string $url, array $data = []): array|string{
        $url = "$this->graphUrl/$this->v".$url;
        return $this->call($url, 'get', $data);
    }

    public function put(string $url, array $data = []): array|string{
        $url = "$this->graphUrl/$this->v".$url;
        return $this->call($url, 'put', $data);
    }

    public function delete(string $url, array $data = []): array|string{
        $url = "$this->graphUrl/$this->v".$url;
        return $this->call($url, 'delete', $data);
    }

    public function patch(string $url, array $data = []): array|string{
        $url = "$this->graphUrl/$this->v".$url;
        return $this->call($url, 'patch', $data);
    }

    private function call(string $url, string $method = 'get', array $data = []){
        // Refresh access token
        $accessToken = $this->authProvider->getAccessToken();
        $this->token = $accessToken->access_token;
        $headers = [
            'Content-Type' => $this->contentType,
            'Authorization' => 'Bearer '. $this->token,
        ];

        $response = $this->{$method."Data"}($headers, $url, $data, $this->timeout);

        if ($response['statusCode']==ResponseCode::HTTP_UNAUTHORIZED){
            if ($this->isSso){
                $this->authProvider->refreshToken();
            }else{
                $this->authProvider->auth();
            }

            return $this->call($url, $method, $data);
        }

        return $response;
    }

}