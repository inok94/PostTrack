<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

$wsdl_url = 'https://tracking.russianpost.ru/rtm34?wsdl';
$url = 'https://tracking.russianpost.ru/rtm34';
//$wsdl = file_get_contents_curl($wsdl_url);

$client2 = '';
$barcode = "tracNum"; // Трек номер от почты России который нужно проверить 
$login = "login";//Логин от API ПР
$password = "pass";

/*
    Запрос к SOAP серверу API почты России 
*/
$request = '<?xml version="1.0" encoding="UTF-8"?>
                <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                 xmlns:oper="http://russianpost.org/operationhistory" 
                 xmlns:data="http://russianpost.org/operationhistory/data"
                 xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Header/>
                <soap:Body>
                   <oper:getOperationHistory>
                      <data:OperationHistoryRequest>
                         <data:Barcode>'. $barcode .'</data:Barcode>  
                         <data:MessageType>0</data:MessageType>
                         <data:Language>RUS</data:Language>
                      </data:OperationHistoryRequest>
                      <data:AuthorizationHeader soapenv:mustUnderstand="1">
                         <data:login>'.$login.'</data:login>
                         <data:password>'.$password.'</data:password>
                      </data:AuthorizationHeader>
                   </oper:getOperationHistory>
                </soap:Body>
             </soap:Envelope>';

$client = new SoapClient($wsdl_url,  array(
    'trace' => 1,
    'exceptions' => 0,
    'soap_version' => SOAP_1_2));
//Запрос истории опирации по трек номеру клиента 
$apiRequest = $client->__doRequest($request, $url, "OperationHistoryData", SOAP_1_2);

//Очиста XML ответа от сервера 
$clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'S:', 'ns2:', 'ns4:', 'ns5:', 'ns6', 'ns7:', 'ns3:'], '', $apiRequest);

$xmlObj = simplexml_load_string($clean_xml);
//Ответ истории где посылка
$historyResponse = $xmlObj->Body->getOperationHistoryResponse->OperationHistoryData->historyRecord;


$res = array();

foreach ($historyResponse as $index=>$value)
{
    $type_id_x = $value->OperationParameters->OperType->Id;
    $type_x = $value->OperationParameters->OperType->Name;
    $attr_id_y = $value->OperationParameters->OperAttr->Id;
    $attr_y = $value->OperationParameters->OperAttr->Name;
    $date_z = $value->OperationParameters->OperDate;
    $description = $value->AddressParameters->OperationAddress->Description;
    $resultsPost[] = array(
        'operType_id' => (int)$type_id_x,
        'operType' => (string)$type_x,
        'operAttr_id' => (int)$attr_id_y,
        'operAttr' => (string)$attr_y,
        //'datetime' => date("Y-m-d H:i:s", strtotime((string)$date_x)),
        'date' => date("Y-m-d", strtotime((string)$date_z)),
        //'time' => date("H:i:s", strtotime((string)$date_x)),
        'description' => (string)$description,
    );
}

//Массив конечных статусов
$orderStatus = array(
//конечная операция
    // Вручение адресату
    2=>array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
    //Невручение
    5=>array(1, 2),
    //конечные 15-18
    15=>array('a'),
    16=>array('a'),
    17=>array('a'),
    18=>array('a'),
);

$lastPost = array_pop($resultsPost);
var_dump($lastPost);
/**
 * Проверка конченого статуса
 */
$lastTypeid = $lastPost['operType_id'];
if(array_key_exists($lastTypeid, $orderStatus)){
    if(in_array($lastPost['operAttr_id'], $orderStatus[$lastTypeid])){
        $status = 1;
    }else if(in_array('a', $orderStatus[$lastTypeid])){
        $status = 1;
    }
}
echo $status;

//$xml = simplexml_load_string($apiRequest);
//echo $xml->OperationHistoryData[0]->ItemParameters->ComplexItemName;



