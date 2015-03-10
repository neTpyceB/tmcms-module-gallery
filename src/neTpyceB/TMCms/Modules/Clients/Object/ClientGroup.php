<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\DB\SQL;
use neTpyceB\TMCms\Modules\Clients\ModuleClients;
use neTpyceB\TMCms\Modules\CommonObject;

class ClientGroup extends CommonObject {
    protected $db_table = 'm_clients_groups';
    protected $multi_lng_fields = ['title'];

    private $clients = [];

    public function getPairs() {
        return q_pairs('
SELECT
	`g`.`id`,
	`d`.`'. LNG .'` AS `title`
FROM `'. ModuleClients::$tables['groups'] .'` AS `g`
JOIN `cms_dstrings` AS `d` ON `d`.`id` = `g`.`title`
'. $this->getWhereSQL() .'
ORDER BY `g`.`title`');
    }

    public function findOneByKey($key, $value) { // TODO move in Commont
        return q_value('SELECT * FROM `'. ModuleClients::$tables['groups'] .'` WHERE `'. sql_prepare($key) .'` = "'. sql_prepare($value) .'"'); // TODO make SQL::selectOne
    }

    public function deleteObject() {
        // Delete all clients // TODO make as object related by GroupId()
        SQL::delete(ModuleClients::$tables['clients'], $this->getId(), 'group_id');

        // Selete group itself
        parent::deleteObject();
    }
}