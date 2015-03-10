<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Admin\Users;
use neTpyceB\TMCms\Modules\Clients\ModuleClients;
use neTpyceB\TMCms\Modules\CommonObject;
use neTpyceB\TMCms\Strings\Dstrings;

class Group extends CommonObject {
    protected $db_table = 'm_clients_groups';
    protected $multi_lng_fields = ['title'];

    protected $title;

    public function getPairs() {
        return q_pairs('
SELECT
	`g`.`id`,
	`d`.`'. LNG .'` AS `title`
FROM `'. ModuleClients::$tables['groups'] .'` AS `g`
JOIN `cms_dstrings` AS `d` ON `d`.`id` = `g`.`title`
ORDER BY `g`.`title`');
    }

    public function deleteObject() {
        // Delete all clients
        $clients_collection = new ClientCollection();
        $clients_collection->setGroupId($this->getId());
        $clients_collection->deleteObjectCollection();

        // Delete all collection
        $clients_collection = new CollectionCollection();
        $clients_collection->setGroupId($this->getId());
        $clients_collection->deleteObjectCollection();

        // Delete group itself
        parent::deleteObject();
    }

    public function getTitle()
    {
        return Dstrings::get($this->title, Users::getUserLng());
    }

    public function setIsDefault()
    {
        $this->setField('default', 1);

        return $this;
    }

    public function setIsNotDefault()
    {
        $this->setField('default', 0);

        return $this;
    }
}