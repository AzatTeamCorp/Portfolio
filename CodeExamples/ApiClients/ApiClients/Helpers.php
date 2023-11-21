<?php

namespace App\ApiClients;

use App\Models\Setting\SystemSetting;

class Helpers{
    static function save($key, $value){
        $setting = SystemSetting::where('key', $key)
                        ->first();
        if ($setting==null){
            $setting = SystemSetting::create([
                'key' => $key,
                'value' => $value   
            ]);
        }else{
            $setting->update(['value' => $value]);
        }

        return json_decode(json_encode($setting->value));
    }

    static function get($key){
        $setting = SystemSetting::where('key', $key)
                        ->first();
        if ($setting){
            return json_decode(json_encode($setting->value));
        }
        return null;
    }
}
