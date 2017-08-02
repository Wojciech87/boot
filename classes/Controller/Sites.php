<?php

defined('SYSPATH') OR die('No direct script access.');

class Controller_Sites extends Controller {

    protected $updateSitesCount = 30000;
    protected $importSitesCount = 30000;
    protected $raportsPath = '/home/default/dns-logs/';

    public function before() {

        parent::before();
        $this->starttime = time();

        set_time_limit(0);

        $this->currentRaportKey = $this->request->controller() . '.' . $this->request->action();

        // CZAS akcji
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $this->startTime = $time;

        $this->currentPlikSingleton = $this->raportsPath . 'singleton/' . $this->currentRaportKey . '.singleton';

        if (file_exists($this->currentPlikSingleton)) {
            $fCont = trim(file_get_contents($this->currentPlikSingleton));

            $singleTime = $fCont;
            if (preg_match('~\d+\-\d+\-\d+ \d+:\d+:\d+~', $fCont, $matches)) {
                $singleTime = $matches[0];
            }
            $roznica = Kohana_Date::span(time(), strtotime($singleTime), 'minutes');

            $wasDeadlock = false;

            if (stripos($fCont, 'Deadlock found when trying to get lock;') !== false) {
                $wasDeadlock = true;
            }


            if ($roznica >= 30 || ($wasDeadlock == true && $roznica > 5)) {
                $this->raport('Wznowienie po poprzedniej awarii skryptu..', 'NOTICE');
            } else {
                $this->raport('already running', 'NOTICE');
                exit('already runnning');
            }
        }

        file_put_contents($this->currentPlikSingleton, date('Y-m-d H:i:s'));
    }

    public function after() {
        $endtime = time();

        $alltime = $endtime - $this->starttime;

        parent::after();


        @unlink($this->currentPlikSingleton);

        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $stop = $time;

        if ($stop - $this->startTime > 60) {
            if ($this->wyswietlonoCzas == false) {
                $this->raport('CZAS WYKONYWANIA ' . round($stop - $this->startTime, 4), 'ALERT');
            }
        }
    }

    public function getMyTime() {
        $time = microtime();
        $time = explode(' ', $time);
        $timeStop = $time[1] + $time[0];

        return round($timeStop - $this->timeStart, 2);
    }

    public function getMyTimeInSeconds() {
        $time = microtime();
        $time = explode(' ', $time);
        $timeStop = $time[1] + $time[0];

        return Date::span($timeStop, $this->timeStart, 'seconds');
    }

    public function czasDzialania() {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $stop = $time;

        return round($stop - $this->startTime, 4);
    }

   
    public function raport($message, $czas = false) {
        if ($czas == true) {
            $message .= ' (' . $this->czasDzialania() . 's)';
            $this->wyswietlonoCzas = true;
        }

        if (PHP_SAPI != 'cli') {
            echo $message . '<Br />';
        }

        file_put_contents($this->raportsPath . 'infos/' . $this->currentRaportKey, date('Y-m-d H:i:s') . ' - ' . $message . '
', FILE_APPEND);
    }

    public function action_test() {
        echo 'jupi';
    }

    public function action_importSitesForStatusA() {
        $limit = $this->importSitesCount;

        $importCount = Model_Sites::importSitesPack('dns_checker_a', array(0, 2, 7), $limit);


        if ($importCount == false) {
            if ($importCount === 0) {
                $this->raport('nie trzeba importowacs', true);
            } else {
                $this->raport('brak danych do importu', true);
            }
            return false;
        }

        $this->raport('zaimportowano ' . $importCount . ' domen do smalla', true);
    }

    public function action_importSitesForStatusB() {
        $limit = $this->importSitesCount;

        $importCount = Model_Sites::importSitesPack('dns_checker_b', array(0, 2, 7), $limit);


        if ($importCount == false) {
            if ($importCount === 0) {
                $this->raport('nie trzeba importowacs', true);
            } else {
                $this->raport('brak danych do importu', true);
            }
            return false;
        }

        $this->raport('zaimportowano ' . $importCount . ' domen do smalla', true);
    }

    public function action_importSitesForStatusC() {
        $limit = $this->importSitesCount;

        $importCount = Model_Sites::importSitesPack('dns_checker_c', array(0, 2, 7), $limit);


        if ($importCount == false) {
            if ($importCount === 0) {
                $this->raport('nie trzeba importowacs', true);
            } else {
                $this->raport('brak danych do importu', true);
            }
            return false;
        }

        $this->raport('zaimportowano ' . $importCount . ' domen do smalla', true);
    }

    public function action_updateSitesStatusForStatusA() {
        $limit = $this->updateSitesCount;
        $minLimit = 200;

        $exportCount = Model_Sites::exportSitesPackStatus('dns_checker_a', $limit, $minLimit, '2,3,6');
        if ($exportCount == false) {
            $this->raport(' nic nie przeniesiono ', true);
            return false;
        }
        $this->raport(' przeniesiono statusy dla  ' . $exportCount, true);
    }

    public function action_updateSitesStatusForStatusB() {
        $limit = $this->updateSitesCount;
        $minLimit = 200;

        $exportCount = Model_Sites::exportSitesPackStatus('dns_checker_b', $limit, $minLimit, '2,3,6');
        if ($exportCount == false) {
            $this->raport(' nic nie przeniesiono ', true);
            return false;
        }
        $this->raport(' przeniesiono statusy dla  ' . $exportCount, true);
    }

    public function action_updateSitesStatusForStatusC() {
        $limit = $this->updateSitesCount;
        $minLimit = 200;

        $exportCount = Model_Sites::exportSitesPackStatus('dns_checker_c', $limit, $minLimit, '2,3,6');
        if ($exportCount == false) {
            $this->raport(' nic nie przeniesiono ', true);
            return false;
        }
        $this->raport(' przeniesiono statusy dla  ' . $exportCount, true);
    }

    public function action_testDownloadingu() {


        $sites = Model_Sites::getSmallSitesPack('dns_checker_a', 0, $limit);

        if ($sites == false) {
            $this->raport('Brak zadan', 'DEBUG');

            return false;
        }

        $sitesAsked = array();

        foreach ($sites as $idDomain => $w) {


            exec($com);

            $sitesAsked[$idDomain] = $idDomain;
        }


        if (count($sitesAsked) > 0) {


            Model_Sites::setSmallSitesPackStatusForIds('dns_checker_a', array_keys($sitesAsked), 2);
        }
    }

    public function action_testAnalyse() {

        $sites = Model_Sites::getSmallSitesPack('dns_checker_a', 2, $limit);

        if ($sites == false) {
            $this->raport('Brak zadan', 'DEBUG');

            return false;
        }



        foreach ($sites as $idDomain => $w) {

            //sprawdzanie
            //oznaczanie
            $arrok[$idDomain] = $idDomain;

            //lub
            $toInsertIntoSites[$idDomain] = '(' . $idDomain . ', 0, 1,  NOW())';
        }


        if (count($arrok) > 0) {
            //nazwa_statusu, tablica_id, docelowy status
            Model_Sites::setSmallSitesPackStatusForIds('dns_checker_a', $arrok, 0);
        }

        if (count($toInsertIntoSites) > 0) {
            //id, dns_checker_a_status, try_times, dns_checker_a_date
            Model_Sites::setSmallSitesPackStatus('dns_checker_a', $toInsertIntoSites);
        }
    }

    public function action_restartStatus() {
//        $ile = $this->db_sites->query(Database::UPDATE, 'UPDATE sites_gsbd_small SET google_sbd_status = 0 WHERE google_sbd_status = 2 AND google_sbd_date < NOW() - INTERVAL 12 HOUR LIMIT 3000');

        $smallResetACount = Model_Sites::restartParamHangingSmallStatuses('dns_checker_a', '5 HOUR', 6);    //restart zaieszonych domen w trakcie pobeirania lokalnie
        $bigResetACount = Model_Sites::restartParamHangingBigStatuses('dns_checker_a');                     //restart zawieszonych domen w trakcie pobierania po 10 dniach (na serwerze zdalnymn

        $smallResetBCount = Model_Sites::restartParamHangingSmallStatuses('dns_checker_b', '5 HOUR', 6);
        $bigResetBCount = Model_Sites::restartParamHangingBigStatuses('dns_checker_b');

        $smallResetCCount = Model_Sites::restartParamHangingSmallStatuses('dns_checker_c', '5 HOUR', 6);
        $bigResetCCount = Model_Sites::restartParamHangingBigStatuses('dns_checker_c');


        //nalezy tez na serwerze zdalnym resetowac status z 1 na 0 - co okreslony czas np 62 dni, bo zakladamy ze jak juz jest porno, to predko sie nie zmieni
        $limit = 10000;
        $bigMarkedResetACount = Model_Sites::restartParamHangingBigStatuses('dns_checker_a', 62, 1, 0, $limit);
        $bigMarkedResetBCount = Model_Sites::restartParamHangingBigStatuses('dns_checker_b', 62, 1, 0, $limit);
        $bigMarkedResetCCount = Model_Sites::restartParamHangingBigStatuses('dns_checker_c', 62, 1, 0, $limit);


        $this->raport('Zrestartowano A:' . $smallResetACount . '/' . $bigResetACount
                . ', B:' . $smallResetBCount . '/' . $bigResetBCount
                . ', C:' . $smallResetCCount . '/' . $bigResetCCount . ' statusow '
                . ', FROM 1->0 A: ' . $bigMarkedResetACount . ', B: ' . $bigMarkedResetBCount . ', C:' . $bigMarkedResetCCount, true);
    }

}

?>
