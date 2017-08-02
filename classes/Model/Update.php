<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Update extends Model {

    public function update_domains($domains) {
        $id = array();
        $uri = array();
        foreach ($domains as $val) {

            echo $val['id'];
            Database::instance()->query(Database::UPDATE, 'UPDATE domains SET status=3 WHERE id=' . $val['id'] . '');
            $id[] = $val['id'];
            $uri[] = $val['id'];
        }
    }

    public function save_domains_to_analyze($domains) {

        foreach ($domains as $val){
            echo $val['id'];
            Database::instance()->query(Database::UPDATE, 'UPDATE domains SET status=3 WHERE id=' . $val['id'] . '');
        }
    }

}

?>