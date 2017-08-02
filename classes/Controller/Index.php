<?php

defined('SYSPATH') OR die('No direct script access.');

class Controller_Index extends Controller {

    public function action_test() {

        $group = $this->request->param('group');
        $limit = $this->request->param('limit');
        echo $group;
        echo $limit;

        $stoper_start = microtime(true); //start pomiaru - mierzę czas wykoania skryptu od tego momentu do momentu oznaczonego $stoper_stop = microtime(true);

        /*
         * Pobieram z modelu Download domeny
         * Zliczam ich sumę
         */
        $download_domains_object = new Model_Download();
        $domains = $download_domains_object->download_domains($group, $limit);

        $sum_domains = count($domains);

        $AskDomainsHelperobject = new AskDomainsHelper();

        $AskDomainsHelperobject->askDomains($domains, $group, $limit);    // Wywołanie metody askDomains z klasy AskDomainsHeleper

        usleep(1000);

        $stoper_stop = microtime(true); //koniec pomiaru
        $log = '' . date("Y-m-d G:i:s") . '</br></br></br>GRUPA ' . $group . ' LIMIT = ' . $limit . '</br>Ustawienie status=6 (domena do sprawdzenia) + odpytania komendą exec + zapis rezultatów w plkach txt dla ' . $sum_domains . ' '
                . 'domen w czasie ' . bcsub($stoper_stop, $stoper_start, 4) . '</br></br></br>'; // Wyświetlenie wyniku pomiaru czasu
        file_put_contents('/var/www/html/boty_wojtka_txt/log_' . $group . '.html', $log, FILE_APPEND);
    }

}

?>
