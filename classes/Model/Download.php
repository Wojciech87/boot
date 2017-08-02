<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Download extends Model {

    const DAYS_STATUS_0 = 30;
    const DAYS_STATUS_1 = 30;
    const DAYS_STATUS_2 = 2;
    const DAYS_STATUS_3 = 3;
    const DAYS_STATUS_4 = 30;

    public function download_domains($group, $limit) {

        $date_now = date("Y-m-d G:i:s");
        //   echo $date_now;
        $new_date_status_0 = strtotime('- ' . self::DAYS_STATUS_0 . ' day', strtotime($date_now));
        $new_date_status_1 = strtotime('- ' . self::DAYS_STATUS_1 . ' day', strtotime($date_now));
        $new_date_status_2 = strtotime('- ' . self::DAYS_STATUS_2 . ' hour', strtotime($date_now));
        $new_date_status_3 = strtotime('- ' . self::DAYS_STATUS_3 . ' hour', strtotime($date_now));
        $new_date_status_4 = strtotime('- ' . self::DAYS_STATUS_4 . ' day', strtotime($date_now));
        $difference_date_status_0 = date("Y-m-d G:i:s", $new_date_status_0);
        $difference_date_status_1 = date("Y-m-d G:i:s", $new_date_status_1);
        $difference_date_status_2 = date("Y-m-d G:i:s", $new_date_status_2);
        $difference_date_status_3 = date("Y-m-d G:i:s", $new_date_status_3);
        $difference_date_status_4 = date("Y-m-d G:i:s", $new_date_status_4);
        /*
         * Pobranie domen gdzie data do sprawdzenia jest mniejsza lub równa 
         * od obecnej, oraz status różny od 3(Domeny mające ten status są przeznaczone do analizy)
         */
        /*
          SELECT * FROM TABELA WHERE
          (last_update_status - UNIX_TIMESTAMP(NOW()) < (2*7*24*60*6) AND status == 1) OR
          (last_update_status - UNIX_TIMESTAMP(NOW()) < (30*24*60*6) AND status == 2)
         */

        $last_update = 'dns_checker_' . $group . '_date';

        $status = 'dns_checker_' . $group . '_status';
        echo $status;
        echo '</br>';
        echo $last_update;
        echo '<\br>';
        echo '_sites_dns_checker_' . $group . '_small';

        return Database::instance()->query(Database::SELECT, 'SELECT id, site, ' . $status . ' , ' . $last_update . ' FROM _sites_dns_checker_' . $group . '_small WHERE '
                        . ' ' . $last_update . ' < \'' . $difference_date_status_2 . '\'  AND ' . $status . ' = 2 '
                        . 'OR ' . $last_update . ' < \'' . $difference_date_status_3 . '\'  AND ' . $status . ' = 3 '
                        . 'OR ' . $last_update . ' < \'' . $difference_date_status_4 . '\'  AND ' . $status . ' = 4 '
                        . 'OR ' . $last_update . ' < \'' . $difference_date_status_0 . '\'  AND ' . $status . ' = 0 '
                        . 'OR ' . $last_update . ' < \'' . $difference_date_status_1 . '\'  AND ' . $status . ' = 1  ORDER BY ' . $last_update . ' DESC LIMIT ' . $limit . ' ')->as_array();
        //'.$last_update.' < \''.$difference_date.'\'  AND 
        //'.$last_update.' <=NOW() AND
        /*
          return  Database::instance()->query(Database::SELECT, 'SELECT id, uri, last_update_date, '
          . 'status FROM domains WHERE id=47 ' )->as_array();
         */
    }

    public function download_domains_for_status($group, $limit) {

        /*
         * Pobranie domen przenaczonych do analizy
         */

        $last_update = 'dns_checker_' . $group . '_date';

        $status = 'dns_checker_' . $group . '_status';

        return Database::instance()->query(Database::SELECT, 'SELECT id, site, ' . $last_update . ', ' . $status . ' FROM _sites_dns_checker_' . $group . '_small WHERE ' . $status . '=6 LIMIT ' . $limit . '')->as_array();
    }

}
?>

