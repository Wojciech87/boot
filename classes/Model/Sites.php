<?php

class Model_Sites extends Model {

    //get table names small i big
    public static function getParamSitesTableName($paramName) {
        switch ($paramName) {
            default :
                return '_sites_' . $paramName;
        }
    }

    public static function getParamSitesTableSmallName($paramName) {

        switch ($paramName) {
            default :
                return self::getParamSitesTableName($paramName) . '_small';
        }
    }

    //import z matjki malych paczek
    public static function isToImport($paramName, $limit = 7000, $targetDbConnection = null) {
        $db = Mydb::getDbInstanceFor('default');

        if ($targetDbConnection != null && $targetDbConnection != 'default') {
            $db = Mydb::getDbInstanceFor($targetDbConnection);
        }

        $iloscWolnych = $db->query(Database::SELECT, 'SELECT count(*) as ile FROM ' . self::getParamSitesTableSmallName($paramName)
                        . ' WHERE ' . $paramName . '_status IN (0,2) AND (change_status LIKE "0000-00-00%" )')
                ->get('ile');


        if ($iloscWolnych > $limit) {
            return false;
        }

        return true;
    }

    public static function markHangingStatuses($paramName, $disabledAccept, $limit, $status, $dateInterval, $whereTransferStatus = ' = 7', $setTransferStatus = ' = 7', $withRedirected = true) {
        $db = Mydb::getDbInstanceSites();

        $iloscWiszacych7 = $db->query(Database::SELECT, 'SELECT count(*) as ile '
                        . ' FROM ' . self::getParamSitesTableName($paramName)
                        . ' WHERE ' . $paramName . '_status ' . $whereTransferStatus)
                ->get('ile');

        if ($iloscWiszacych7 < $limit) {

            $dataMinus31Day = date('Y-m-d', time() - $dateInterval * Date::DAY);

            $query = ' SELECT s.id '
                    . ' FROM ' . self::getParamSitesTableName($paramName) . ' s '
                    . ' JOIN _sites_base b '
                    . ' ON s.id = b.id '
                    . ' WHERE ' . $paramName . '_status ' . $status
                    . ' AND ' . $paramName . '_date < "' . $dataMinus31Day . '" '
                    . ' AND disabled IN (' . implode(',', $disabledAccept) . ') '
                    . ( $withRedirected == false ? ' AND is_redirect = 0 ' : '')
                    . ' ORDER BY ' . $paramName . '_date ASC '
                    . ' LIMIT ' . $limit;


            $idikiDoOznaczenia = $db->query(Database::SELECT, $query)->as_array('id');

            if (count($idikiDoOznaczenia) > 0) {
                //zanzaczam importowane domeny
                $update = ' UPDATE ' . self::getParamSitesTableName($paramName)
                        . ' SET ' . $paramName . '_status ' . $setTransferStatus
                        . ' WHERE id IN (' . implode(', ', array_keys($idikiDoOznaczenia)) . ') '
                        . ' AND ' . $paramName . '_status ' . $status;

                $ileOznaczono7 = $db->query(Database::UPDATE, $update);

                return $ileOznaczono7;
            }
        }

        return false;
    }

    public static function insertMarkedDomains($paramName, $limit, $targetDbConnection = null, $whereTransferStatus = ' = 7', $decrementParameterStataus = 7) {
        $db = Mydb::getDbInstanceSites();

        //importuje zaznaczone domeny
        $qu = 'SELECT s.id, b.id_group, b.site, b.disabled, b.is_redirect, s.' . $paramName . '_date, ' . $paramName . '_status '
                . ' FROM ' . self::getParamSitesTableName($paramName) . ' s '
                . ' JOIN _sites_base b ON s.id = b.id '
                . ' WHERE s.' . $paramName . '_status ' . $whereTransferStatus
                . ' LIMIT ' . (2 * $limit);

        $sites = $db->query(Database::SELECT, $qu);


        $toInsert = array();
        foreach ($sites as $s) {
            $toInsert[$s['id']] = '(' . $s['id'] . ', ' . $s['id_group'] . ', "' . $s['site'] . '", ' . $s['disabled'] . ', ' . $s['is_redirect']
                    . ', ' . ( $s[$paramName . '_status'] - $decrementParameterStataus ) . ', "' . $s[$paramName . '_date'] . '", NOW() )';
        }

        //wstawiam pobrane domeny do lokalnej tablicy..
        if (count($toInsert) > 0) {
            $dbTarget = Mydb::getDbInstanceFor('default');

            if ($targetDbConnection != null && $targetDbConnection != 'default') {
                $dbTarget = Mydb::getDbInstanceFor($targetDbConnection);
            }

            $insRes = $dbTarget->query(Database::INSERT, 'INSERT IGNORE INTO ' . self::getParamSitesTableSmallName($paramName)
                    . ' (id, id_group, site, disabled, is_redirect, ' . $paramName . '_status, ' . $paramName . '_date, import_date) '
                    . ' VALUES ' . implode(',', $toInsert));

            return $insRes[1];
        }
    }

    public static function importSitesPackWithoutRedirected($paramName, $disabledAccept = array(), $limit = 7000, $status = ' = 0', $dateInterval = 31, $targetDbConnection = 'default', $whereTransferStatus = ' = 7', $setTransferStatus = ' = 7', $decrementParameterStataus = 7, $nextStatus = ' = 2') {
        return self::importSitesPack($paramName, $disabledAccept, $limit, $status, $dateInterval, $targetDbConnection, $whereTransferStatus, $setTransferStatus, $decrementParameterStataus, $nextStatus, false);
    }

    public static function importSitesPack($paramName, $disabledAccept = array(), $limit = 7000, $status = ' = 0', $dateInterval = 31, $targetDbConnection = 'default', $whereTransferStatus = ' = 7', $setTransferStatus = ' = 7', $decrementParameterStataus = 7, $nextStatus = ' = 2', $withRedirected = true) {

        $db = Mydb::getDbInstanceSites();

        if (!self::isToImport($paramName, $limit, $targetDbConnection)) {
            return 0;
        }

        if (!self::markHangingStatuses($paramName, $disabledAccept, $limit, $status, $dateInterval, $whereTransferStatus, $setTransferStatus, $withRedirected)) {
            return false;
        }

        if (!self::insertMarkedDomains($paramName, $limit, $targetDbConnection, $whereTransferStatus, $decrementParameterStataus)) {
            return false;
        }


        //oznaczam zaimportowane domeny
        $update = ' UPDATE ' . self::getParamSitesTableName($paramName)
                . ' SET ' . $paramName . '_status ' . $nextStatus . ', ' . $paramName . '_date = NOW() + INTERVAL 2 HOUR '
                . ' WHERE ' . $paramName . '_status ' . $whereTransferStatus
                . ' LIMIT ' . (2 * $limit);

        return $db->query(Database::UPDATE, $update);
    }

    //koniec import
    //pobieranie z lokalnej malej tablic
    public static function getSmallSitesPackWithColumn($paramName, $status, $limit, $extraColumn, $offset = 0, $maxTryTimes = 2, $intervalMinutes = '5 MINUTE ') {
        return self::getSmallSitesPack($paramName, $status, $limit, $offset, $maxTryTimes, $intervalMinutes, $extraColumn);
    }

    public static function getSmallSitesPack($paramName, $status, $limit, $offset = 0, $maxTryTimes = 2, $intervalMinutes = '5 MINUTE ', $extraColumn = null) {

        $db = Mydb::getDbInstanceFor('default');

        if ($status > 0 && $maxTryTimes > 0) {
            $maxTryTimes ++;
        }
        $sites = $db->query(Database::SELECT, 'SELECT id, id_group, site, disabled, is_redirect '
                        . ($extraColumn != null ? ', ' . $extraColumn . ' ' : '')
                        . ' FROM ' . self::getParamSitesTableSmallName($paramName)
                        . ' WHERE ' . $paramName . '_status = ' . $status . ' AND export_status = 0 '
                        . ( $status == 0 ? ' AND import_date < NOW() - INTERVAL 5 MINUTE ' : '' )
                        . ' AND change_status < NOW() - INTERVAL ' . $intervalMinutes
                        . ($maxTryTimes > 0 ? ' AND try_times < ' . $maxTryTimes : '')
                        . ' ORDER BY change_status, import_date '
                        . ' LIMIT ' . $offset . ', ' . $limit)
                ->as_array('id');

        //jesli nie ma zadnych stron do pobrania to loguje i wychodzi z funkcji
        if (count($sites) <= 0) {
            return false;
        }

        return $sites;
    }

    //ustawianie lokalnych statusow
    public static function setSmallSitesPackStatusForIds($paramName, $idsArray, $targetStatus = 2, $targetDate = '') {

        $db = Mydb::getDbInstanceFor('default');

        return $db->query(Database::UPDATE, 'UPDATE ' . self::getParamSitesTableSmallName($paramName)
                        . ' SET ' . $paramName . '_status = ' . $targetStatus . ', '
                        . $paramName . '_date = ' . ($targetDate == '' ? ' NOW() ' : $targetDate )
                        . ( $targetStatus == 2 ? ', try_times = try_times + 1 ' : '' )
                        . ' WHERE id IN (' . implode(', ', $idsArray) . ')');
    }

    public static function setSmallSitesPackStatus($paramName, $insertArray) {

        $db = Mydb::getDbInstanceFor('default');

        $insRes = $db->query(Database::INSERT, 'INSERT INTO ' . self::getParamSitesTableSmallName($paramName)
                . ' (id, ' . $paramName . '_status, try_times, ' . $paramName . '_date) '
                . ' VALUES ' . implode(', ', $insertArray)
                . ' ON DUPLICATE KEY UPDATE ' . $paramName . '_status = VALUES(' . $paramName . '_status),
                            try_times = IF (VALUES(try_times) = 0, 0, try_times + VALUES(try_times)),
                            ' . $paramName . '_date = VALUES(' . $paramName . '_date)');

        return $insRes[1];
    }

    //export paczki statusow do matki
    protected static function getExportWhereCondition($paramName, $workStatuses = '2') {
        return $paramName . '_status NOT IN (' . $workStatuses . ')'
                . ' AND change_status > "0000-00-00 00:00:00" '
                . ' AND change_status < NOW() - INTERVAL 5 MINUTE  '
                . ' AND export_status = 0 ';
    }

    public static function countSitesPackStatusToExport($paramName, $limit, $workStatus) {

        $db = Mydb::getDbInstanceFor('default');

        return $db->query(Database::SELECT, 'SELECT count(*) as ile '
                        . ' FROM ' . self::getParamSitesTableSmallName($paramName)
                        . ' WHERE ' . self::getExportWhereCondition($paramName, $workStatus)
                        . ' LIMIT ' . $limit)->get('ile');
    }

    protected static function insertIntoTargetSiteStatus($paramName, $toInsert) {
        if (count($toInsert) <= 0) {
            return false;
        }

        $db = Mydb::getDbInstanceSites();

        $insRes = $db->query(Database::INSERT, 'INSERT INTO ' . self::getParamSitesTableName($paramName)
                . ' (id, ' . $paramName . '_status, ' . $paramName . '_date) '
                . ' VALUES ' . implode(', ', $toInsert)
                . ' ON DUPLICATE KEY UPDATE '
                . ' ' . $paramName . '_status = VALUES(' . $paramName . '_status), '
                . ' ' . $paramName . '_date = VALUES(' . $paramName . '_date)');

        $ileWstawiono = $insRes[1] / 2;

        if ($ileWstawiono > 0) {

            $db = Mydb::getDbInstanceFor('default');
            $db->query(Database::DELETE, 'DELETE FROM ' . self::getParamSitesTableSmallName($paramName)
                    . ' WHERE id IN (' . implode(',', array_keys($toInsert)) . ')');
        }

        return $ileWstawiono;
    }

    public static function exportSitesPackStatus($paramName, $limit = 5000, $minLimit = 200, $workStatuses = '2') {
        $maxLimit = 2 * $limit;

        if (self::countSitesPackStatusToExport($paramName, $limit, $workStatuses) < $minLimit) {
            echo 'za malo do exporti ';
            return false;
        }

        $db = Mydb::getDbInstanceFor('default');

        $db->query(Database::UPDATE, 'UPDATE ' . self::getParamSitesTableSmallName($paramName)
                . ' SET export_status = 1 '
                . ' WHERE ' . self::getExportWhereCondition($paramName, $workStatuses)
                . ' LIMIT ' . $limit);

        $ids = $db->query(Database::SELECT, 'SELECT id, ' . $paramName . '_status, ' . $paramName . '_date '
                        . ' FROM ' . self::getParamSitesTableSmallName($paramName)
                        . ' WHERE export_status = 1 '
                        . ' LIMIT ' . $maxLimit)->as_array();

        $toInsert = array();
        foreach ($ids as $i) {
            $toInsert[$i['id']] = '(' . $i['id'] . ', ' . $i[$paramName . '_status'] . ', "' . $i[$paramName . '_date'] . '")';
        }

        return self::insertIntoTargetSiteStatus($paramName, $toInsert);
    }

    //restarty statusow
    public static function restartParamHangingSmallStatuses($paramName, $intervalSmallTable = '5 HOUR', $fromStatus = 2, $toStatus = 0, $limit = 1000) {
        return self::restartParamHangingSmallStatusesAndDates($paramName, $intervalSmallTable, $fromStatus, $toStatus, $limit);
    }

    public static function restartParamHangingSmallStatusesAndDates($paramName, $intervalSmallTable = '5 HOUR', $fromStatus = 2, $toStatus = 0, $limit = 1000, $withDates = true) {

        $db = Mydb::getDbInstanceFor('default');

        return $db->query(Database::UPDATE, 'UPDATE ' . self::getParamSitesTableSmallName($paramName)
                        . ' SET ' . $paramName . '_status = ' . $toStatus
                        . ($withDates == true ? ', ' . $paramName . '_date = (NOW() - INTERVAL 30 DAY) ' : '')
                        . ' WHERE ' . $paramName . '_status = ' . $fromStatus . ' AND ' . $paramName . '_date < (NOW() - INTERVAL ' . $intervalSmallTable . ');');
    }

    public static function restartParamHangingBigStatuses($paramName, $intervalBigTable = 10, $fromStatus = 2, $toStatus = 0, $limit = 4000) {
        return self::restartParamHangingBigStatusesAndDates($paramName, $intervalBigTable, $fromStatus, $toStatus, $limit, false);
    }

    public static function restartParamHangingBigStatusesAndDates($paramName, $intervalBigTable = 10, $fromStatus = 2, $toStatus = 0, $limit = 4000, $withDates = true) {

        $db = Mydb::getDbInstanceSites();

        return $db->query(Database::UPDATE, 'UPDATE ' . self::getParamSitesTableName($paramName)
                        . ' SET ' . $paramName . '_status = ' . $toStatus
                        . ($withDates ? ', ' . $paramName . '_date = (NOW() - INTERVAL 30 DAY) ' : '')
                        . ' WHERE ' . $paramName . '_status = ' . $fromStatus . ' AND ' . $paramName . '_date < (NOW() - INTERVAL ' . $intervalBigTable . ' DAY) '
                        . ' LIMIT ' . $limit);
    }

    //ustawianie lokalnie informacji o padnietych/przekeirowujacych domenach
    public static function markSiteRedirectLocalStatusPack($inserArray, $localTableName = 'site_redirects_infos') {

        $db = Mydb::getDbInstanceFor('default');

        $toInsert = array();

        foreach ($inserArray as $insInfo) {
            $toInsert[$insInfo['id_domain']] = '(' . $insInfo['id_domain'] . ', NOW(), ' . time() . ', '
                    . $insInfo['is_redirect'] . ', '
                    . ($insInfo['is_redirect'] ? $insInfo['code'] : 'NULL') . ', '
                    . ($insInfo['is_redirect'] ? $db->escape($insInfo['url']) : 'NULL')
                    . ')';
        }

        $insRes = $db->query(Database::INSERT, 'INSERT INTO ' . $localTableName . ' '
                . ' (id_domain, date, timestamp, is_redirect, redirect_code, redirect_url ) '
                . ' VALUES ' . implode(', ', $toInsert)
                . ' ON DUPLICATE KEY UPDATE '
                . ' date = VALUES(date), '
                . ' timestamp = VALUES(timestamp), '
                . ' is_redirect = VALUES(is_redirect), '
                . ' redirect_code = VALUES(redirect_code), '
                . ' redirect_url = VALUES(redirect_url), '
                . ' status = 0 ');

        return $insRes;
    }

    public static function markSiteRedirectLocalStatus($idDomain, $isRedirect = 0, $redirectCode = null, $redirectUrl = null, $localTableName = 'site_redirects_infos') {

        return self::markSiteRedirectLocalStatusPack(array(array('id_domain' => $idDomain,
                        'is_redirect' => $isRedirect,
                        'code' => $redirectCode,
                        'url' => $redirectUrl)), $localTableName
        );
    }

    public static function markSiteDownloadLocalErrorPack($inserArray, $localTableName = 'site_download_error_status') {
        $db = Mydb::getDbInstanceFor('default');


        $toInsert = array();

        foreach ($inserArray as $insInfo) {
            $toInsert[$insInfo['id_domain']] = '(' . $insInfo['id_domain'] . ', NOW(), ' . time() . ', ' . $insInfo['was_error'] . ')';
        }

        $insRes = $db->query(Database::INSERT, 'INSERT INTO ' . $localTableName . ' (id_domain, date, timestamp, was_error) '
                . ' VALUES ' . implode(',', $toInsert)
                . ' ON DUPLICATE KEY UPDATE '
                . ' date = VALUES(date), '
                . ' timestamp = VALUES(timestamp), '
                . ' was_error = VALUES(was_error), '
                . ' status = 0 ');

        return $insRes;
    }

    public static function markSiteDownloadLocalError($idDomain, $downloadError, $localTableName = 'site_download_error_status') {


        return self::markSiteDownloadLocalErrorPack(array(array('id_domain' => $idDomain, 'was_error' => $downloadError)), $localTableName);
//        $db = Mydb::getDbInstanceFor('default');
//        
//        $insRes = $db->query(Database::INSERT, 'INSERT INTO site_download_error_status (id_domain, date, timestamp, was_error) '
//                                                    . ' VALUES (' . $idDomain . ', NOW(), ' . time() . ', ' . $downloadError . ')'
//                                                    . ' ON DUPLICATE KEY UPDATE '
//                                                            . ' date = VALUES(date), '
//                                                            . ' timestamp = VALUES(timestamp), '
//                                                            . ' was_error = VALUES(was_error), '
//                                                            . ' status = 0 ' );
//        
//        return $insRes;
    }

    //export lokalnych info o padnietych/przekierowujacych domenach
    public static function exportToSiteServerDataPack($tableName, $additionalColumns, $columnsToStringFunction, $targetTableName) {

        $limit = 10000;
        $maxTransferLimit = 100000;
        $minLimit = 1000;


        $local = Mydb::getDbInstanceFor('default');

        $iloscDanych = $local->query(Database::SELECT, 'SELECT count(*) as ile FROM ' . $tableName . ' WHERE status = 0')->get('ile');


        if ($iloscDanych < $minLimit) {
            echo 'za malo danych <br />';
            return false;
        }

        $extraColumnList = (count($additionalColumns) > 0 ? ', ' : '') . implode(', ', $additionalColumns);
        $extraColumnUpdateList = (count($additionalColumns) > 0 ? ', ' : '');

        $lastCol = end($additionalColumns);

        foreach ($additionalColumns as $col) {
            $extraColumnUpdateList .= $col . ' = VALUES(' . $col . ')';

            if ($col != $lastCol) {
                $extraColumnUpdateList .= ', ';
            }
        }


        $timestampkMax = $local->query(Database::SELECT, 'SELECT timestamp '
                        . ' FROM  ' . $tableName
                        . ' WHERE status = 0 '
                        . ' ORDER BY timestamp '
                        . ' LIMIT ' . ($iloscDanych - 1) . ', 1')
                ->get('timestamp');
        if ($timestampkMax <= 0) {
            echo 'za maly timestamp';
            return false;
        }
        $local->query(Database::UPDATE, 'UPDATE ' . $tableName
                . ' SET status = 1 '
                . ' WHERE status = 0 '
                . ' AND timestamp <= ' . $timestampkMax
                . ' LIMIT ' . $limit);


        $localPack = $local->query(Database::SELECT, 'SELECT id_domain, date, timestamp ' . $extraColumnList
                . ' FROM ' . $tableName . ' 
                                                    WHERE status = 1 
                                                    LIMIT ' . $maxTransferLimit);

        $toInsertValues = array();
        foreach ($localPack as $info) {
            $toInsertValues[$info['id_domain']] = '(' . $info['id_domain'] . ', "' . $info['date'] . '", ' . $info['timestamp']
                    . $columnsToStringFunction($info) . ')';

//                    . $info['is_redirect'] . ', ' 
//                    . ($info['is_redirect'] ? $info['redirect_code'] : 'NULL') . ', '
//                    . ($info['is_redirect'] ? '"' . $info['redirect_url'] . '"' : 'NULL' ) . ' )';
        }

        if (count($toInsertValues) <= 0) {
            echo 'and sudenly shit';
            return false;
        }

        $remote = Mydb::getDbInstanceSites();
        $remote->query(Database::INSERT, 'INSERT INTO ' . $targetTableName . ' (id_domain, date, timestamp' . $extraColumnList . ') 
                                                    VALUES ' . implode(',', $toInsertValues)
                . ' ON DUPLICATE KEY UPDATE date = VALUES(date), '
                . ' timestamp = VALUES(timestamp) '
                . $extraColumnUpdateList
                . ', status = 0 ');


        $deletedFromLocal = $local->query(Database::DELETE, 'DELETE FROM ' . $tableName . ' WHERE status = 1');


        return $deletedFromLocal;
    }

    public static function exportToSiteServerRedirectStatusPack($tableName = 'site_redirects_infos') {
        $targetTableName = 'site_redirects_infos';

        $columns = array('is_redirect', 'redirect_code', 'redirect_url');

        $getInfo = function($info) {
            return ', ' . $info['is_redirect'] . ', '
                    . ($info['is_redirect'] ? $info['redirect_code'] : 'NULL') . ', '
                    . ($info['is_redirect'] ? '"' . mysql_escape_string($info['redirect_url']) . '"' : 'NULL' );
        };

        return self::exportToSiteServerDataPack($tableName, $columns, $getInfo, $targetTableName);
    }

    public static function exportToSiteServerDownloadStatusPack($tableName = 'site_download_error_status') {

        $targetTableName = 'site_download_error_status';
        $columns = array('was_error');

        $getInfo = function($info) {
            return ', ' . $info['was_error'];
        };

        return self::exportToSiteServerDataPack($tableName, $columns, $getInfo, $targetTableName);
    }

}

?>
