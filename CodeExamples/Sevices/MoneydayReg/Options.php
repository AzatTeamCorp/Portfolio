<?php

namespace App\Services\MoneydayReg;

use App\Models\Reference\Payment;
use App\Models\Reference\Reference;
use App\Http\Resources\PaymentResource;

use Illuminate\Support\Str;

class Options {

    public static function getLists() {
        $data = [
            'passportDepartment' => [],
        ];

        $records = Reference::whereIn('type', [
            'types_of_education',
            'types_of_employment',
            'types_of_activities',
            'positions',
            'duration_of_present_employment',
            'contact_roles',
            'types_of_residential_estate',
            'state_of_marriage',
        ])->orderBy('name', 'asc')->get();

        foreach($records as $item)
        {
            $data[Str::camel($item->type)][] = [
                'value' => $item->uuid,
                'label' => Str::ucfirst($item->name)
            ];
        }

        for($i=0; $i<=5; $i++)
        {
            $data['childrens'][] = [
                'value' => ($i < 5 ? $i : '5+'),
                'label' => ($i < 5 ? $i : '5 и более')
            ];
        }

        $payments = Payment::active()->orderBy('sort', 'asc')->get();

        foreach($payments as $payment)
        {
            $data['payments'][$payment->uuid] = (new PaymentResource( $payment ));
        }

        return $data;
    }
    
}
