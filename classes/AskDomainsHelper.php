<?php

defined('SYSPATH') or die('No direct script access.');

class AskDomainsHelper {

    const IP_A = '199.85.126.10';
    const IP_B = '199.85.126.20';
    const IP_C = '199.85.126.30';

    public static function askDomains($domains, $group, $limit) {

        if ($group == "a") {
            $SaveIP = self::IP_A;
        }
        if ($group == "b") {
            $SaveIP = self::IP_B;
        }
        if ($group == "c") {
            $SaveIP = self::IP_C;
        }

        $status = 'dns_checker_' . $group . '_status';
        $last_update = 'dns_checker_' . $group . '_date';
        $string = ''; //inicjacja pustej zmiennej string;

        foreach ($domains as $val){
            echo 'WOJTEK ustawiam na 6 domenę' . $val['site'] . '</br>';
            Database::instance()->query(Database::UPDATE, 'UPDATE _sites_dns_checker_' . $group . '_small SET ' . $status . '=6 WHERE id=' . $val['id'] . '');
            $los = rand(1, $limit);
            $los2 = $los / 10000;

            $string .= 'sleep ' . $los2 . '; proxychains host ' . $val['site'] . ' ' . $SaveIP . ' > /home/default/httpdocs/boty_wojtka_txt/' . $val['id'] . '_' . $val['site'] . '_' . $group . '.txt 2' . '> /home/default/httpdocs/boty_wojtka_txt/' . $val['id'] . '_' . $val['site'] . '_' . $group . '_txt_error.txt &' . PHP_EOL;
        }

        exec($string);  // wywołanie jednego execa
    }

}
