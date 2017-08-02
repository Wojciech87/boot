<?php

defined('SYSPATH') or die('No direct script access.');

class AnalyzeFileHostHelper {

    const FILE_INFO_PATH = '/home/default/httpdocs/boty_wojtka_txt/';
    const ERROR_FILE_PATH = '/home/default/httpdocs/boty_wojtka_txt/';
    const IP_A_STAT_ONE_FIRST = '156.154.175.10';
    const IP_A_STAT_ONE_SECOND = '156.154.176.10';
    const IP_B_STAT_ONE_FIRST = '156.154.175.20';
    const IP_B_STAT_ONE_SECOND = '156.154.176.20';
    const IP_C_STAT_ONE_FIRST = '156.154.175.30';
    const IP_C_STAT_ONE_SECOND = '156.154.176.30';
    const GROUP_A = 'group_a';
    const GROUP_B = 'group_b';
    const GROUP_C = 'group_c';
    
    const GROUP_A_LETTER = 'a';
    const GROUP_B_LETTER = 'b';
    const GROUP_C_LETTER = 'c';
    
    const STATUS_O = '0';
    const STATUS_A = '1';
    const STATUS_B = '2';
    const STATUS_C = '3';
    const STATUS_D = '4';

    /*
     * Funkcja która otwiera pliki do pobranych domen, analizuje je i rezultaty binarnie zapisuje do bazy result
     */

    public static function AnalyzeFile($domains, $group) {

        foreach ($domains as $key => $val){
            $file_info = self::FILE_INFO_PATH . $val['id'] . '_' . $val['site'] . '_' . $group . '.txt';
            $file_info_error = self::ERROR_FILE_PATH . $val['id'] . '_' . $val['site'] . '_' . $group . '_txt_error.txt';

            if (file_exists($file_info)) {

                //    echo 'JEST';
                $file = file_get_contents(self::ERROR_FILE_PATH . $val['id'] . '_' . $val['site'] . '_' . $group . '.txt');

                //$dead = false;
                $group_to_save = 'group_' . $group;
                echo $group_to_save;
                //    foreach ($file as $pos) :
                if (preg_match('/Host [^\s]+ not found/si', $file)) {
                    $save = 'off';
                    $status = self::STATUS_D;
                } else if (preg_match('/connection timed out/si', $file)) {
                    //  $dead = true;
                    $save = 'off';
                    $status = self::STATUS_B;
                }

                //  
                else if (preg_match('/[^\s]+ has address ([\.\:\d]+)/i', $file, $mtch)) {
                    $save = 'on';
                    $ip = UTF8::trim($mtch[1]);
                    if (self::IP_A_STAT_ONE_FIRST || self::IP_A_STAT_ONE_SECOND) {
                        if ($group_to_save == self::GROUP_A) {
                            $status = self::STATUS_A;
                            $group = self::GROUP_A_LETTER;
                        }
                    } else if (self::IP_B_STAT_ONE_FIRST || self::IP_B_STAT_ONE_SECOND) {
                        if ($group_to_save  == self::GROUP_B) {

                            $status = self::STATUS_A;
                            $group = self::GROUP_B_LETTER;
                        }
                    } else if (self::IP_C_STAT_ONE_FIRST || self::IP_C_STAT_ONE_SECOND) {
                        if ($group_to_save == self::GROUP_C) {
                            $status = self::STATUS_A;
                            $group = self::GROUP_C_LETTER;
                            $save = 'on';
                        }
                    } else {

                        $status = self::STATUS_O;
                    }
                } else {

                    $save = 'off';
                    $status = self::STATUS_C;

                    Database::instance()->query(Database::UPDATE, 'UPDATE _sites_dns_checker_' . $group . '_small SET dns_checker_' . $group . '_status=' . $status . ', dns_checker_' . $group . '_date=\'' . $date_now . '\' '
                            . 'WHERE id=' . $val['id'] . ' ');

                    unlink($file_info);
                    unlink($file_info_error);
                }
                echo $save;
                if ($save == 'on') {


                    $date_to_timestamp = date_create();
                    $date_timestamp = date_timestamp_get($date_to_timestamp);
                    //   echo $status;
                
                    Database::instance()->query(Database::INSERT, 'INSERT INTO result(`group`, `id_domains`,`answer_exec`,`data_check`,`status`,`data_check_bot`)'
                            . 'VALUES(\'' . $group . '\',\'' . $val['id'] . '\',\'' . $ip . '\',NOW(),\'' . $status . '\',\'' . $date_timestamp . '\') ');
                }

                $date_now = date("Y-m-d G:i:s");

                Database::instance()->query(Database::UPDATE, 'UPDATE _sites_dns_checker_' . $group . '_small SET dns_checker_' . $group . '_status=' . $status . ', dns_checker_' . $group . '_date=\'' . $date_now . '\' '
                        . 'WHERE id=' . $val['id'] . ' ');

                unlink($file_info);
                unlink($file_info_error);
            } else {
                $date_now = date("Y-m-d G:i:s");

                Database::instance()->query(Database::UPDATE, 'UPDATE _sites_dns_checker_' . $group . '_small SET dns_checker_' . $group . '_status=2, dns_checker_' . $group . '_date=\'' . $date_now . '\' '
                        . 'WHERE id=' . $val['id'] . ' ');
            }
        }
    }

}
