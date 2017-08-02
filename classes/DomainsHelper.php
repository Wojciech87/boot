<?php

defined('SYSPATH') or die('No direct script access.');

class DomainsHelper {

    const DAYS_TO_REPEAT_CHECK = 20;    // po ilu dniach odpytać dla statusu 1 
    const DATE_FORMAT = "Y-m-d G:i:s";

    public static function AnalyzeDomains($domains, $group) {
        $date_now = date(self::DATE_FORMAT);
        $new_date_status_1 = strtotime('- ' . self::DAYS_TO_REPEAT_CHECK . ' day', strtotime($date_now));
        $difference_date_status_1 = date("Y-m-d G:i:s", $new_date_status_1);

        $time_check = '';
        foreach ($domains as $key => $val){
            $id[] = $val['id'];
            $uri[] = $val['uri'];
            $last_update[] = $val['last_update_date_' . $group . ''];
            $status[] = $val['status_' . $group . ''];
        }

        if ($status[0] == '1') {
            echo ($last_update[0] < $difference_date_status_1) ? "OK" : "NIE";
        }
    }

}
