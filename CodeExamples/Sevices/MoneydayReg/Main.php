<?php

namespace App\Services\MoneydayReg;
use App\Services\Moneyday\MoneydayService;
use App\Services\Payments\Payler;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use \App\Helpers\Helper;

class Main
{
    private $payler;
    private $moneydayService;

    public function __construct( Payler $payler, MoneydayService $moneydayService )
    {
        $this->payler = $payler;
        $this->moneydayService = $moneydayService;
    }

    public function make($request)
    {
        $totalArray = [
            "СоздатьЗаявку" => "true",
        ];

        $data = $this->prepareData($request);
        if(!empty($data))
        {
            $totalArray['Данные'] = $data;
        }

        $files = $this->prepareData($request);
        if(!empty($files))
        {
            $totalArray['Файлы'] = $files;
        }

        $this->moneydayService->registrationPersonalAccount($totalArray);

        return true;
    }

    public function prepareData($request = [])
    {
        list( $request["passportSeries"], $request["passportNumber"] ) = Helper::explodePassport($request["passport"]);

        $data = [
            "СуммаЗайма" => $request["calc_range_sum"],
            "СрокЗайма" => $request["calc_range_clock"],
            "ФинансовыйПродукт" => $request["calcID"],

            "Фамилия" => $request["lastName"],
            "Имя" => $request["name"],
            "Отчество" => $request["secondName"],
            "Телефон" => Helper::clearPhone($request["phone"]),
            "АдресЭП" => $request["email"],

            "ХэшПароля" => md5($request["password"]),
            "Регион" => $request["region"],

            "СогласиеПравилаПредоставленияЗаймов" => "true",
            "СогласиеСПравиламиОбработкиПерсональныхДанных" => "true",
            "СогласиеНаОбраткуПерсональныхДанных" => "true",
            "СогласиеИспользованияПростойЭлектроннойПодписи" => "true",
            "СогласиеНаПолучениеИнформации" => "true",

            "Серия" => str_replace(" ", "", $request["passportSeries"]),
            "Номер" => $request["passportNumber"],

            "ДатаВыдачи" => (new Carbon($request["passportIssueDate"]))->format('c'),
            "КодПодразделения" => $request["passportCodeDepartment"],
            "КемВыдан" => $request["passportDepartment"],
            "МестоРождения" => $request["placeOfBirth"],
            "ДатаРождения" => (new Carbon($request["birthday"]))->format('c'),

            "ОсновноеОбразование" => $request["education"],
            "СостояниеВБраке" => $request["marriage"],
            "КоличествоДетей" => $request["childrens"],

            "АдресРегистрацииDaData" => [
                "Вид" => "АдресРегистрации",
                "ТипЖилья" => $request["addressRegType"],
                "АдресРегистрацииДатаРегистрации" => (new Carbon($request["addressRegDate"]))->format('c'),
                "Значение" => [
                    "suggestions" => [
                        json_decode($request["addressReg"], true)
                    ]
                ]
            ],

            "АдресПроживанияСовпадаетСАдресомРегистрации" => $request["addressRegCoincidence"],

            "ОбъектыЗалогаТранспортноеСредство" => []
        ];

        if (!$request["addressRegCoincidence"])
        {
            $data["АдресПроживанияDaData"] = [
                "Вид" => "АдресМестаПроживания",
                "ТипЖилья" => $request["addressResidenceType"],
                "Значение" => [
                    "suggestions" => [
                        json_decode($request["addressResidence"], true)
                    ]
                ]
            ];
        }

        if ($request["dontWork"])
        {
            $data["Занятость"] = "ВременноНеРаботаю";
        }
        else
        {
            $data = array_merge($data, [
                "Занятость" => $request["employment"],
                "ВидДеятельности" => $request["activity"],
                "МестоРаботы" => $request["company"],
                "Должность" => $request["position"],
                "СтажНаПоследнемМестеРаботы" => $request["stage"],
                "СреднемесячныйДоход" => intval($request["earnings"]),
                "РабочийТелефон" => $request["workPhone"]
            ]);
        }

        if(!empty($request['contacts']))
        {
            $data["КонтактныеЛица"] = Arr::map($request['contacts'], function ($value) {
                return [
                    "ФИО" => $value['fio'],
                    "Телефон" => $value['phone'],
                    "КемПриходится" => $value['role'],
                ];
            });
        }

        // Пока не активные поля, не нужные
        // $data = array_merge($data, [
        //     "ДополнительныйДоход" => intval($request["dop_earnings"]),
        //     "ИсточникДополнительногоДохода" => ($request["dop_source"] == "dopField") ? $request["dop_earnings_string"] : $request["dop_source"],
        //     "НомерСчета" => $request["number_account"],
        //     "БИКБанка" => $request["bic"],
        //     "НаименованиеБанка" => $request["title_account"],
        //     "КоррСчетБанка" => $request["corp_account"],
        //     "СреднемесячныеОбязательства" => intval($request["dop_earnings_money_give"]),
        //     "ФормаОплаты" => $request["payment_method"],
        //     "СтраховойНомерПФР" => $request["snlis"],
        //     "ИНН" => $request["inn"],
        // ]);

        return $data;
    }

    public function prepareFiles($request = [])
    {
        $dataFiles = [];
        foreach ($request["base64_passport"] as $key => $file)
        {
            $dataFiles[$request["name_passport"][$key]] = $file;
        }
        return $dataFiles;
    }

}
