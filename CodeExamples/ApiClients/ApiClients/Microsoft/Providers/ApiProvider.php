<?php

namespace App\ApiClients\Microsoft\Providers;

class ApiProvider{

    protected $authApi;
    protected $v1Api;
    protected $betaApi;
    protected $timeout;
    protected $isSso;

    public function __construct(){
        $this->timeout = 10;
    }

    public function authApi(): AuthProvider{
        if (!$this->authApi instanceof AuthProvider){
            $this->authApi = new AuthProvider();
        }
        return $this->authApi;
    }

    public function v1Api(): VersionOneProvider{
        if (!$this->v1Api instanceof VersionOneProvider){
            $this->v1Api = new VersionOneProvider($this->isSso);
        }
        return $this->v1Api;
    }

    public function betaApi(): BetaProvider{
        if (!$this->betaApi instanceof BetaProvider){
            $this->betaApi = new BetaProvider($this->isSso);
        }
        return $this->betaApi;
    }

}