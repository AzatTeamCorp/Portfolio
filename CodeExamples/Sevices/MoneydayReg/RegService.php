<?php

namespace App\Services\MoneydayReg;
use App\Models\Registration;
use App\Services\Moneyday\MoneydayService;
use App\Services\Payments\Payler;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use \App\Helpers\Helper;
use Session;

class RegService
{
    private $payler;
    private $moneydayService;

    public function __construct( Payler $payler, MoneydayService $moneydayService ){
        $this->payler = $payler;
        $this->moneydayService = $moneydayService;
    }

    public function check(){
        $request = Registration::where('session_id', Session::getId())->first();

        $totalArray = [
            "СоздатьЗаявку" => "false",
        ];

        $data = $this->prepareData($request);

        if(!empty($data)){
            $totalArray['Данные'] = $data;
        }

        return $this->moneydayService->checkPerson($totalArray);
    }

    public function make($step = 4){
        $request = Registration::where('session_id', Session::getId())->first();

        $totalArray = [
            "СоздатьЗаявку" => $step==4?"false":"true",
        ];

        $data = $this->prepareData($request, ($step==4?false:true));

        if(!empty($data)){
            $totalArray['Данные'] = $data;
        }

        if ($step==4){
            $files = $this->prepareFiles($request->loadMedia('files'));
            if(!empty($files)){
                $totalArray['Файлы'] = $files;
            }
        }

        $totalArray['step'] = $step;

        $this->moneydayService->registrationPersonalAccount($totalArray);

        return true;
    }

    public function prepareData($request = [], $paymentCard = false){
        list($request["passportSeries"], $request["passportNumber"]) = Helper::explodePassport($request["passport"]??'');

        $request["passportSeries"] = str_replace(" ", "", $request["passportSeries"]);
        $request["passportNumber"] = str_replace(" ", "", $request["passportNumber"]);

        // Основная информация
        $data = [
            "СуммаЗайма" => $request["sum"],
            "СрокЗайма" => $request["term"],
            "ФинансовыйПродукт" => $request["product_id"],
            "Фамилия" => $request["last_name"],
            "Имя" => $request["name"],
            "Отчество" => $request["second_name"],
            "Телефон" => Helper::clearPhone($request["phone"]),
            "АдресЭП" => $request["email"],
            "Регион" => $request["region"],
            "ХэшПароля" => $request["password"],
            "СогласиеПравилаПредоставленияЗаймов" => "true",
            "СогласиеСПравиламиОбработкиПерсональныхДанных" => "true",
            "СогласиеНаОбраткуПерсональныхДанных" => "true",
            "СогласиеИспользованияПростойЭлектроннойПодписи" => "true",
            "СогласиеНаПолучениеИнформации" => "true",
            "Серия" => $request["passportSeries"],
            "Номер" => $request["passportNumber"],
            "ДатаВыдачи" => (new Carbon($request["passport_issue_date"]))->format('c'),
            "КемВыдан" => $request["passport_department"],
            "КодПодразделения" => $request["passport_code_department"],
            "МестоРождения" => $request["place_of_birth"],
            "ДатаРождения" => (new Carbon($request["birthdate"]))->format('c'),

            "СтраховойНомерПФР" => '', // $request["snlis"],
            "ИНН" => '', // $request["inn"],

            "ОсновноеОбразование" => $request["education"],
            "СостояниеВБраке" => $request["marriage"],
            "КоличествоДетей" => $request["childrens"],

            "АдресРегистрацииDaData" => [
                "Вид" => "АдресРегистрации",
                "ТипЖилья" => $request["address_reg_type"],
                "АдресРегистрацииДатаРегистрации" => (new Carbon($request["address_reg_date"]))->format('c'),
                "Значение" => [
                    "suggestions" => [
                        DaData::addressStandard($request["address_reg"])
                    ]
                ]
            ],

            "АдресПроживанияСовпадаетСАдресомРегистрации" => (bool)$request["address_reg_coincidence"],

            "ОбъектыЗалогаТранспортноеСредство" => []
        ];

        // Адрес регистрации
        if (!$request["address_reg_coincidence"]){
            $data["АдресПроживанияDaData"] = [
                "Вид" => "АдресМестаПроживания",
                "ТипЖилья" => $request["address_residence_type"],
                "Значение" => [
                    "suggestions" => [
                        DaData::addressStandard($request["address_residence"])
                    ]
                ]
            ];
        }

        // Занятость
        if ($request["dont_work"]) {
            $data["Занятость"] = "ВременноНеРаботаю";
        } else {
            $data = array_merge($data, [
                "Занятость" => $request["employment"],
                "ВидДеятельности" => $request["activity"],
                "МестоРаботы" => $request["company"],
                "Должность" => $request["position"],
                "СтажНаПоследнемМестеРаботы" => $request["stage"],
                "СреднемесячныйДоход" => intval($request["earnings"]*1),
                "РабочийТелефон" => $request["work_phone"]
            ]);
        }

        // Дополнительный доход
        $data = array_merge($data, [
            "ДополнительныйДоход" => 0, // intval($request["dop_earnings"]*1),
            "ИсточникДополнительногоДохода" => '' //($request["dop_source"] == "dopField") ? $request["dop_earnings_string"] : $request["dop_source"],
        ]);

        // Контакты
        if(!empty($request['contacts'])){
            if (!is_array($request['contacts'])){
                $request['contacts'] = json_decode($request['contacts'], true);
            }
            $data["КонтактныеЛица"] = Arr::map($request['contacts'], function ($value) {
                return [
                    "ФИО" => $value['fio'],
                    "Телефон" => $value['phone'],
                    "КемПриходится" => $value['whoIs'],
                ];
            });
        }

        // Дополнительные поля
        $data = array_merge($data, [
            "НомерСчета" => '', // $request["number_account"],
            "БИКБанка" => '', // $request["bic"],
            "НаименованиеБанка" => '', // $request["title_account"],
            "КоррСчетБанка" => '', // $request["corp_account"],
            "СреднемесячныеОбязательства" => 0, // intval($request["dop_earnings_money_give"]),
            "ФормаОплаты" => '31d1854d-64d4-11ea-80d9-001e67d8af87' // $request["payment_method"],
        ]);

        // Данные карты
        if ($paymentCard && isset($request['payment_data']['card_id'])){
            $data = array_merge($data, [
                "ИдентификаторБанковскойКартыВСервисеПлатежей" => $request['payment_data']['card_id'],
                "НомерБанковскойКарты" => $request['payment_data']['card_number'],
                "ИмяДержателяБанковскойКарты" => $request['payment_data']['card_holder'],
                "СрокДействияГодБанковскойКарты" => $request['payment_data']['expired_year'],
                "СрокДействияМесяцБанковскойКарты" => $request['payment_data']['expired_month'],
                "ИдентификаторШаблонаРекуррентныхПлатежей" => $request['payment_data']['recurrent_template_id'],
                "ФормаОплаты" => "9dd5fdac-461a-11eb-80e0-001e67d8af87"
            ]);
        }

        // Идентификатор контрагента
        $data["ИдентификаторКонтрагентаВСервисеПлатежей"] = $request['customer_id'];

        return $data;
    }

    public function prepareFiles($request = []){
        $dataFiles = [];
        foreach ($request as $file)
        {
            $fileSizeMb = round($file['size']/1000000);
            $limitMb = 2;
            $allowTypes = ['image/jpeg', 'image/png', 'image/bmp'];
            
            // zip
            if ($fileSizeMb>$limitMb && in_array($file['mime_type'], $allowTypes)){
                $resource = imagecreatefromstring(file_get_contents($file->getPath()));
                imagejpeg($resource, $file->getPath(), ((($limitMb-.1)*100)/$fileSizeMb));
            }

            $content = file_get_contents($file->getPath());
            $content = base64_encode($content);

            $customProperties = $file['custom_properties'];
            
            $identifier = '';
            if ($customProperties['type']=='passportFileCard'){
                $identifier = 'РазворотСФотографией';
            }else if ($customProperties['type']=='passportFileReg'){
                $identifier = 'РазворотСПропиской';
            }else if ($customProperties['type']=='passportFileSelfie'){
                $identifier = 'СелфиСПаспортом';
            }

            $dataFiles[$identifier] = 'data:'.$file['mime_type'].';base64,'.$content;
        }
        return ["Паспорт" => $dataFiles];
    }

}
