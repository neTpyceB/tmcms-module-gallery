<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Modules\CommonObjectCollection;

class ClientCollection extends CommonObjectCollection {
    protected $db_table = 'm_clients';

    protected $group_id;

    public function setGroupId($group_id) {
        $this->setFilterValue('group_id', $group_id);

        return $this;
    }
}