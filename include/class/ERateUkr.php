<?php

/**
 * Курс валют парсинг з goverla.ua (парсить нову версію сайту!) та API приватбанку
 * Автор: Богдан Карпов <bogdan.karpow@urk.net>
 * Telegram: @BogdanKarpov
 */

class ERateUkr
{
    //Constants for goberla.ua
    const URL_GOVERLA_API = 'https://api.goverla.ua/graphql';
    const QUERY_GOVERLA_STRING = '{"operationName":"Point","variables":{"alias":"goverla-ua"},"query":"query Point($alias: Alias!) {\n  point(alias: $alias) {\n    id\n    rates {\n      id\n      currency {\n        alias\n        name\n        exponent\n        codeAlpha\n        codeNumeric\n        __typename\n      }\n      bid {\n        absolute\n        relative\n        updatedAt\n        __typename\n      }\n      ask {\n        absolute\n        relative\n        updatedAt\n        __typename\n      }\n      __typename\n    }\n    updatedAt\n    __typename\n  }\n}\n"}';

    //PrivatBank Rate from uan
    const URL_PRIVATBANK_API = 'https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5';

    static private function get_post(string $url, string $query):string
    {
        $myCurl = curl_init();
        curl_setopt_array($myCurl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_SSL_VERIFYPEER => true ,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query)
            ],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0'
        ));
        $response = curl_exec($myCurl);

        $ern = curl_errno($myCurl);
        if ($ern){
            die('ERROR CURL: ' . curl_error($myCurl));
        }
        curl_close($myCurl);
        return (!$response) ? '' : (string) $response;
    }



    static public function format_curruncy(string $absolute):float
    {
        $len = strlen($absolute) - 1;
        $sli = (int) $absolute[$len -1]  . $absolute[$len];
        $int = (int) preg_replace('#\d\d$#si', '', $absolute);
        return (float) $int . '.' . $sli;
    }



    /**
     * Поаертає рядок json з інформаціжю курсів валют з сайту goverla.ua
     * @param bool $return_array - якшо true то повертає масив інакше json рядок
     * @return string|array
     */
    static public function goverla($return_array = false)
    {
        $json_request = self::get_post(self::URL_GOVERLA_API, self::QUERY_GOVERLA_STRING);
        $resp = json_decode( $json_request, true );
        $currency = $resp['data']['point']['rates'];
        if (!$currency){ return '["ok":false]'; }
        $r = [];
        foreach($currency as $c ){
            $a['name'] = htmlspecialchars( strip_tags($c['currency']['name']));
            $a['bid'] = self::format_curruncy($c['bid']['absolute']);
            $a['ask'] = self::format_curruncy($c['ask']['absolute']);
            $r[] = $a;
        }
        return ($return_array) ? $r : json_encode($r);
    }



    static private function get_data_privatbank():array
    {
        $res = file_get_contents(self::URL_PRIVATBANK_API);
        if (!empty($res)){
            return json_decode($res, true);
        }
        return [];
    }


    static private function reader_rate_name(string $name):string
    {
        switch ($name) {
            case 'USD':
                return 'Долар США';

            case 'EUR':
                return 'Євро';

            case 'RUR':
                return 'Російський рубль';

            case 'BTC':
                return 'Біткоїн';

            default:
                return $name;
        }
    }


    static private function formater_data_privatbank(array $data):array
    {
        $f_array = [];
        foreach ($data as $datum) {
            $a = [];
            $a['name'] = self::reader_rate_name($datum['ccy']);
            $a['bid'] = (float)$datum['buy'];
            $a['ask'] = (float)$datum['sale'];
            $f_array[] = $a;
        }
        return $f_array;
    }


    /**
     * Поаертає рядок json з інформаціжю курсів валют з api PrivatBank
     * @param bool $return_array - якшо true то повертає масив інакше json рядок
     * @return string|array
     */
    static public function privatbank($return_array = false)
    {
        $data = self::get_data_privatbank();
        $f_data = self::formater_data_privatbank($data);
        return ($return_array) ? $f_data : (string) json_encode($f_data);
    }


    /**
     * Поаертає рядок json з інформаціжю курсів валют з усіх доступних сервісів
     * @param bool $return_array - якшо true то повертає масив інакше json рядок
     * @return string|array
     */
    static public function all(bool $return_array = false)
    {
        $ar = [];
        $ar['goverla'] = self::goverla(true);
        $ar['privatbank'] = self::privatbank(true);
        // add new Rate
        // ...


        return ($return_array) ? $ar : json_encode($ar);
    }


}

