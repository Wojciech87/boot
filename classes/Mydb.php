<?php

defined('SYSPATH') or die('No direct script access.');

class Mydb {

    protected static $db;
    protected static $db_booty4_master;
    protected static $db_booty4_bot;
    protected static $db_timeline_screenshots;
    protected static $db_sites;

    public static function isToOldParameterRecord($con, $tableName, $dateColumn = 'date') {
        $minRecordDate = $con->query(Database::SELECT, 'SELECT MIN(MONTH(' . $dateColumn . ')) as min_month FROM ' . $tableName . ' LIMIT 1')->get('min_month');

        return date('n') > $minRecordDate;
    }

    public static function getDbInstanceFor($instanceName) {
        return Database::instance($instanceName);
    }

    public static function getDbInstanceSites() {
        if (self::$db_sites == NULL) {
            self::$db_sites = Database::instance('db_sites');
        }

        return self::$db_sites;
    }

}
