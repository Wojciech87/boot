<?php

defined('SYSPATH') OR die('No direct script access.');

class Controller_Analyze extends Controller {

    const LOG_PATH ='/var/www/html/boty_wojtka_txt/' ;
    
    public function action_testnext() {

        /*
         * Pobieram z modelu Download domeny
         * Zliczam ich sumę
         */

        $group = $this->request->param('group');
        $limit = $this->request->param('limit');
        
        echo $group;
        
        $download_domains_object = new Model_Download();
        $domains = $download_domains_object->download_domains_for_status($group, $limit);
        $sum_domains = count($domains);
        
        echo $sum_domains;
        
        $AnalyzeFileHostHelperobject = new AnalyzeFileHostHelper();

        $stoper_start = microtime(true); //start pomiaru - mierzę czas wykoania skryptu od tego momentu do momentu oznaczonego $stoper_stop = microtime(true);

        $AnalyzeFileHostHelper = $AnalyzeFileHostHelperobject->AnalyzeFile($domains, $group); // przejście do analizy plików
        // }
        usleep(1000);

        $stoper_stop = microtime(true); //koniec pomiaru

        $log = '</br></br></br>' . date("Y-m-d G:i:s") . '</br>GRUPA ' . $group . ' Z analizowałem ' . $sum_domains . ' plików txt oraz zapisałem '
                . 'rezultaty analizy do bazy result w czasie: ' . bcsub($stoper_stop, $stoper_start, 4) . '</br></br></br></br>'; // Wyświetlenie wyniku pomiaru czasu
        file_put_contents( self::LOG_PATH . 'log__'. $group . '_next.html', $log, FILE_APPEND);
    }

}

?>
