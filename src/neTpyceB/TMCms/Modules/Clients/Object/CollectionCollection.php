<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Modules\CommonObjectCollection;

class CollectionCollection extends CommonObjectCollection {
    protected $db_table = 'm_clients_collections';

    protected $group_id;

    public function setGroupId($group_id) {
        $this->setFilterValue('group_id', $group_id);

        return $this;
    }

    public function setOnlyActive()
    {
        $this->setFilterValue('active', 1);

        return $this;
    }
}