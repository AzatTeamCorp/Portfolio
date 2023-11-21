<?php

namespace App\ApiClients;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

trait Client{

    public function postData(array $headers, string $url, array $data = [], int $timeout = 10, $asForm = false): array{
        $response = Http::withHeaders($headers)
                        ->timeout($timeout)
                        ->when($asForm, function($http){
                            $http->asForm();
                        })
                        ->post($url, $data);
        return $this->dataFormatter($response, $url, $data);
    }

    public function getData(array $headers, string $url, array $data = [], int $timeout = 10): array{
        $response = Http::withHeaders($headers)->timeout($timeout)->get($url, $data);
        return $this->dataFormatter($response, $url, $data);
    }

    public function putData(array $headers, string $url, array $data = [], int $timeout = 10): array{
        $response = Http::withHeaders($headers)->timeout($timeout)->put($url, $data);
        return $this->dataFormatter($response, $url, $data);
    }

    public function deleteData(array $headers, string $url, array $data = [], int $timeout = 10): array{
        $response = Http::withHeaders($headers)->timeout($timeout)->delete($url, $data);
        return $this->dataFormatter($response, $url, $data);
    }

    public function patchData(array $headers, string $url, array $data = [], int $timeout = 10): array{
        $response = Http::withHeaders($headers)->timeout($timeout)->patch($url, $data)->throw();
        return $this->dataFormatter($response, $url, $data);
    }

    protected function dataFormatter(Response $response, $url = null, $data = null) : array{
        $result = [
            'success' => ($response->status()<300?true:false),
            'statusCode' => $response->status(),
            'statusMessage' => ResponseCode::$statusTexts[$response->status()],
            'date' => Carbon::now()->format('c')
        ];

        $responseData = $response->json()??$response->body();

        if($response->successful()){
            $result['data'] = $responseData;
        }

        return $result;
    }
}