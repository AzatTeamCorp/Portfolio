<?php

namespace App\Services\MoneydayReg;
use App\Services\Moneyday\Facades\PaymentsService;
use App\Models\Registration;
use App\Enums\RegistrationSteps;
use App\Models\Visitor;
use App\Services\Moneyday\Facades\DbrainService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

use Stevebauman\Location\Facades\Location as Locator;
use Jenssegers\Agent\Agent;

class Base {
    private $reg;
    private $request;
    private RegService $regService;

    private static $excludeFields = [
        'id',
        'created_at',
        'updated_at',
        'customer_id',
        'payment',
        'password',
        'session_id',
        'phone_verification_code',
        'email_verification_code',
        'ref_id',
    ];

    public function __construct(HttpRequest $request, RegService $regService){
        $this->request = $request;
        $this->reg = $this->firstOrCreate();
        $this->regService = $regService;
        //$this->reg->touch();
    }

    public function init(){
        $fields = $this->getRegFields();

        return [
            'currentStep' => intval($this->reg->getRawOriginal('current_step')),
            'maxStep' => intval($this->reg->getRawOriginal('max_step')),
            'autosave' => $this->reg->autosave ?? false,
            'isPhoneConfirmed' => (bool) ($fields['phoneVerifiedAt'] ?? false),
            'isEmailConfirmed' => (bool) ($fields['emailVerifiedAt'] ?? false),
            'token' => csrf_token(),
            'page' => request()->url(),
            'fields' => $fields,
            'files' => $this->getFiles(),
            'calc' => $this->getCalc(),
            'options' => Options::getLists()
        ];
    }

    private function getRegFields(){
        $attributes = $this->reg->attributesToArray() ?? [];
        $attributes = Arr::except($attributes, self::$excludeFields);
        $attributes = collect($attributes)->mapWithKeys(function ($item, $key) {
            return [Str::camel($key) => $item !== null ? $item : ''];
        })->all();

        return $attributes;
    }

    public function controlOrderId(){
        $orderId = $this->request->get('order_id');
        $currentStep = $this->reg->getRawOriginal('current_step');
        $regOrderId = $this->reg->order_id ?? null;

        if(!$regOrderId && $currentStep == RegistrationSteps::FINAL_STEP ){
            Session::regenerate();
            return redirect()->away($this->request->fullUrlWithoutQuery('order_id'));
        }

        if(!$orderId)
            return false;

        if(!$regOrderId)
            return redirect()->away($this->request->fullUrlWithoutQuery('order_id'));

        // order_id принадлежит текущему пользователю
        if($regOrderId == $orderId){
            if( $currentStep == RegistrationSteps::PAYMENT_STEP ){
                PaymentsService::getPaylerStatus($orderId);
            }

            if( $currentStep == RegistrationSteps::FINAL_STEP ){
                Session::regenerate();
                return redirect()->away($this->request->fullUrlWithoutQuery('order_id'));
            }
        }

        return false;
    }

    public function updateField($name, $value = ''){
        $name = Str::snake($name);

        $update[$name] = $value ?? '';

        $this->reg->fill($update);

        if(!$this->reg->isDirty()){
            return true;
        }

        $this->reg->save();

        return true;
    }

    public function updateFile($name, $value = null){
        $filter = ['type' => $name];
        $existFile = $this->reg->getFirstMedia('files', $filter) ?? null;

        if($existFile){
            $existFile->delete();
        }

        $this->reg->addMedia($value)->withCustomProperties($filter)->toMediaCollection('files');
        return true;
    }

    public function update($request = []){
        // Сохранить плетежные данные в массиве
        if(isset($request['payment_data']) && is_array($request['payment_data'])){
            $currentPaymentData = $this->reg->payment_data??[];
            $request['payment_data'] = array_merge($currentPaymentData, $request['payment_data']);
        }

        // Сохранить поля Dbrain в массиве
        /*if(isset($request['dbrain_fields']) && is_array($request['dbrain_fields'])){
            $currenDbrainData = $this->reg->dbrain_fields??[];
            $request['dbrain_fields'] = array_merge($currenDbrainData, $request['dbrain_fields']);
            $request['dbrain_fields'] = array_unique($request['dbrain_fields']);
        }*/

        $this->reg->update($request);

        // Если заявка на финальном шаге то отправить запрос в 1С
        if (isset($request['current_step']) && $request['current_step']==RegistrationSteps::FINAL_STEP){
            $this->regService->make(RegistrationSteps::PAYMENT_STEP);
        }

        return true;
    }

    public function updateDbrain(){
        // Проверить только 2 вид документа (passportFileCard, passportFileReg)
        $mappingData = [
            'surname'           =>  'last_name',
            'first_name'        =>  'name',
            'other_names'       =>  'second_name',
            'date_of_birth'     =>  'birthdate',
            'place_of_birth'    =>  'place_of_birth',
            'date_of_issue'     =>  'passport_issue_date',
            'subdivision_code'  =>  'passport_code_department',
            'issuing_authority' =>  'passport_department',
            'series_and_number' =>  'passport',
            'address'           =>  'address_reg',
        ];
        $fields = [
            'last_name'                 => null,
            'name'                      => null,
            'second_name'               => null,
            'birthdate'                 => null,
            'place_of_birth'            => null,
            'passport_issue_date'       => null,
            'passport_code_department'  => null,
            'passport_department'       => null,
            'passport'                  => null,
            'address_reg'               => null,
        ];
        $dbrainFields = []; 
        foreach ($this->getFiles($getContent = true) as $key => $file):
            //if ($key=='passportFileCard' || $key=='passportFileReg'){
            $result = DbrainService::recognize($file[0]['content']);
            foreach ($result['items'] as $item):
                // Проверить все поля полученные из Dbrain
                foreach($mappingData as $key1 => $map){
                    if (isset($item['fields'][$key1]) && $item['fields'][$key1]['text']!=''){
                        if ($key1=='series_and_number'){
                            $item['fields'][$key1]['text'] = str_replace(' ', ' - ', $item['fields'][$key1]['text']); 
                        }
                        $fields[$map] = $item['fields'][$key1]['text'];
                        $dbrainFields[] = Str::camel($map);
                    }
                }
            endforeach;
            //}
        endforeach;

        $fields['dbrain_fields'] = (array)$dbrainFields;
        $this->update($fields);
        return $this->getRegFields();
    }

    public function delete(){
        return $this->reg->delete();
    }

    private function prepareRequestBeforeCreate(){
        $calc = Arr::dot(self::getCalcByProductId(request()->get('product_id')));

        $result = [
            'product_id' => $calc['productId']??null,
            'sum' => $calc['price.default']??null,
            'term' => $calc['period.default']??null,
        ];

        if($sum = request()->get('sum')){
            if($calc['price.min'] <= $sum && $sum <= $calc['price.max']){
                $result['sum'] = $sum;
            }
        }

        if($term = request()->get('term')){
            if($calc['period.min'] <= $term && $term <= $calc['period.max']){
                $result['term'] = $term;
            }
        }

        return $result;
    }

    private function firstOrCreate() : Registration{
        $request = $this->prepareRequestBeforeCreate();

        $registration = Registration::{(request()->get('product_id')?'updateOrCreate':'firstOrCreate')}(
            [
                'session_id' => Session::getId()
            ],
            [
                'current_step' => 1,
                'ref_id' => Request::get('ref_id', null),
                'product_id' => $request['product_id']??null,
                'sum' => $request['sum']??null,
                'term' => $request['term']??null,
            ]
        );

        if(!$registration->visitor()->exists()){
            $visitor = self::firstOrCreateVisitor();
            $registration->visitor()->save($visitor);
        }

        return $registration;
    }

    private static function firstOrCreateVisitor() : Visitor{
        $locator = Locator::get();
        $agent = new Agent();

        $browser = $agent->browser();
        $platform = $agent->platform();

        return Visitor::firstOrCreate(
            [
                'session_id' => Session::getId(),
            ],
            [
                'ip_address' => Request::ip(),
                'city' => $locator->cityName ?? null,
                'user_agent' => Request::server('HTTP_USER_AGENT'),
                'device' => $agent->device(),
                'platform' =>  $platform,
                'platform_version' => $agent->version($platform),
                'browser' => $browser,
                'browser_version' => $agent->version($browser),
                'is_desktop' => $agent->isDesktop(),
                'is_tablet' => $agent->isTablet(),
                'is_phone' => $agent->isPhone(),
                'is_robot' => $agent->isRobot(),
            ]
        );
    }

    private function getCalc($product_id = null): array{
        $product_id = $product_id ?? $this->reg->product_id ?? null;
        return self::getCalcByProductId($product_id);
    }

    private static function getCalcByProductId($product_id = null): array{
        $currentCalc = [];
        $product_id = $product_id ?? null;
        $calculators = config('moneyday.calc');
        $calculators = Arr::keyBy($calculators, 'product_id');

        if($product_id && isset($calculators[$product_id])){
            $currentCalc = $calculators[$product_id];
        }else{
            $currentCalc = Arr::first($calculators, function ($value, $key)
            {
                return $value['default'];
            });
        }

        $currentCalc = collect($currentCalc)->mapWithKeys(function ($item, $key) {
            return [Str::camel($key) => $item];
        });

        $currentCalc = $currentCalc->except('default');

        return $currentCalc->all();
    }

    private function getFiles($getContent = false){
        $files = [];
        $dbFiles = $this->reg->getMedia('files') ?? [];

        foreach($dbFiles as $file){
            $type = $file->getCustomProperty('type', 'non_type');
            $fileContent = file_get_contents($file->getPath());
            $base64 = 'data:'.$file->mime_type.';base64,'.base64_encode($fileContent);

            $files[$type][] = [
                'id' => md5($file->file_name),
                'name' => $file->file_name,
                'size' => $file->size,
                'blob' => $base64,
                'type' => $file->mime_type,
                'content' => ($getContent?$fileContent:''),
            ];
        }

        return $files;
    }

}
