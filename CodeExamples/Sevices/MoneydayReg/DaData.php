<?php

namespace App\Services\MoneydayReg;

use Illuminate\Support\Facades\Http;
use MoveMoveIo\DaData\Facades\DaDataName;
use MoveMoveIo\DaData\Facades\DaDataPassport;
use MoveMoveIo\DaData\Facades\DaDataAddress;
use MoveMoveIo\DaData\Enums\Language;
use MoveMoveIo\DaData\Enums\Gender;
use MoveMoveIo\DaData\Enums\Parts;

class DaData
{
    public static function dataFormatter($value = '')
    {
        $value = trim($value);

        return [
            'label' => $value,
            'value' => $value,
        ];
    }

    public static function getLastNameAutocomplete($query = '')
    {
        $data = [];
        $response = DaDataName::prompt($query, 10, Gender::UNKNOWN, [Parts::SURNAME]);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }


        return $data;
    }

    public static function getNameAutocomplete($query = '')
    {
        $data = [];
        $response = DaDataName::prompt($query, 10, Gender::UNKNOWN, [Parts::NAME]);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }

        return $data;
    }

    public static function getSecondNameAutocomplete($query = '')
    {
        $data = [];
        $response = DaDataName::prompt($query, 10, Gender::UNKNOWN, [Parts::PATRONYMIC]);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }

        return $data;
    }

    public static function getCityAutocomplete($query = '')
    {
        $data = [];
        $response = DaDataAddress::prompt($query, 30, Language::RU, [], [], [], ['value' => 'city'], ['value' => 'settlement']);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }

        return $data;
    }

    public static function getAddressAutocomplete($query = '')
    {
        $data = [];
        $response = DaDataAddress::prompt($query, 10, Language::RU);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }

        return $data;
    }

    public static function getPassportCodeDepartmentAutocomplete($query = '')
    {
        $data = [];
        $response = DaDataPassport::fms($query, 10);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['data']['code']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }

        $data = array_values(array_unique($data, SORT_REGULAR));

        return $data;
    }
    
    public static function getPassportDepartmentsByCode($query = '')
    {
        $data = [];
        $response = DaDataPassport::fms($query, 10);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($query);
        }

        $data = array_values(array_unique($data, SORT_REGULAR));

        return $data;
    }

    public static function getCompanyAutocomplete($query = ''){
        $data = [];

        $response = self::apiCall('/rs/suggest/party', ['query' => $query]); 

        if(!empty($response['suggestions'])){
            foreach ($response['suggestions'] as $suggestion){
                $tmp = self::dataFormatter($suggestion['value']);
                $exists = false;
                foreach($data as $d):
                    if ($d['value']==$tmp['value']){
                        $exists = true;break;
                    }
                endforeach;

                if ($exists){
                    continue;
                }

                $data[] = $tmp;
            }
        }else{
            $data[] = self::dataFormatter($query);
        }

        return $data;
    }

    public static function confirmPassport($passport = '')
    {
        $data = [];
        $response = DaDataPassport::standardization($passport);

        if(!empty($response['suggestions']))
        {
            foreach ($response['suggestions'] as $suggestion)
            {
                $data[] = self::dataFormatter($suggestion['value']);
            }
        }
        else
        {
            $data[] = self::dataFormatter($passport);
        }

        return $data;
    }

    public static function addressStandard($address = ''){
        $response = DaDataAddress::prompt($address, 10, Language::RU);

        if(!empty($response['suggestions'])){
            $response = $response['suggestions'][0];
        } else {
            $response = [
                'value' => '',
                'unrestricted_value' => '',
                'data' => [
                    null
                ]
            ];
        }

        return $response;
    }

    private static function apiCall($uri, $data = []){
        $base_uri = 'https://suggestions.dadata.ru/suggestions/api/4_1';
        $response = Http::withHeaders([
            'Authorization' => 'Token '.config('dadata.token')
        ])
        ->post($base_uri.$uri, $data);
        return json_decode($response->body(), true);
    }
}
