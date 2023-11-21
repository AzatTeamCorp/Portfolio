<?php

namespace App\Services\MoneydayReg\Facades;

use Illuminate\Support\Facades\Facade;

class RegService extends Facade {

    protected static function getFacadeAccessor() : string {
        return \App\Services\MoneydayReg\Base::class;
    }
    
}
