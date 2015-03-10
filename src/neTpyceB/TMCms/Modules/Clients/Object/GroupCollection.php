<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Modules\CommonObjectCollection;

class GroupCollection extends CommonObjectCollection {
    protected $db_table = 'm_clients_groups';

    public function setIsNotDefault()
    {
        foreach ($this->collectObjects() as $v) {
            /** @var Group $v */
            $v->setIsNotDefault();
        }
    }
}