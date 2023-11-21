<?php

namespace App\Services\MoneydayReg;

use App\Enums\RegistrationSteps;
use App\Helpers\Helper;
use App\Models\Registration;
use App\Services\Payments\Payler;
use Session;

class Payments
{
    private $payler;

    public function __construct(Payler $payler){
        $this->payler = $payler;
    }

    public function getPaylerLink($request){
        $request = Registration::where('session_id', Session::getId())->first();

        return [
            'success' => true,
            'link' => $this->payler->getLink('Pay', ['session_id' => $request['payment_data']["session_id"]])
        ];
    }

    public function getPaylerStatus($order_id = null){
        if(!$order_id)
            return false;

        $responseCard = $this->payler->post("GetAdvancedStatus", ['order_id' => $order_id]);

        if (isset($responseCard['error'])){
            return false;
        }

        \App\Services\MoneydayReg\Facades\RegService::update([
            'current_step' => RegistrationSteps::FINAL_STEP,
            'payment_data' => [
                'card_id' => $responseCard["card_id"] ?? '',
                'card_number' => $responseCard["card_number"] ?? '',
                'card_holder' => $responseCard['card_holder'] ?? '',
                'expired_year' => $responseCard['expired_year'] ?? '',
                'expired_month' => $responseCard['expired_month'] ?? '',
                'recurrent_template_id' => $responseCard['recurrent_template_id'] ?? ''
            ]
        ]);

        return true;
    }
    
    public function customerRegister($request){
        list($request["passportSeries"], $request["passportNumber"]) = Helper::explodePassport($request["passport"] ?? '');

        $dataCustomerRegister = [
            'customer_name' => $request["name"] ?? '',
            'customer_phone' => Helper::clearPhone($request["phone"] ?? ''),
            'customer_email' => $request["email"] ?? '',
            'customer_fullName' => trim(($request["name"]??'') . ' ' . ($request["last_name"]??'') . ' ' .  ($request["second_name"]??'')),
            'customer_documentType' => 'Паспорт',
            'customer_documentSeria' => $request["passportSeries"] ?? '',
            'customer_documentNumber' => $request["passportNumber"] ?? '',
        ];

        return $this->payler->post("CustomerRegister", $dataCustomerRegister);
    }

    public function startSession($orderId, $customerId, $email = ''){
        return $this->payler->post("StartSession", [
            'type' => 'TwoStep',
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'recurrent' => 1,
            'amount' => 100,
            'email' => ($email!=''?$email:"your@mail.ru"),
            'return_url_success' => Helper::routeQuery('web.reg', ['order_id' => $orderId]),
            'return_url_decline' => Helper::routeQuery('web.reg', ['order_id' => $orderId, 'error' => true]),
        ]);
    }
}
