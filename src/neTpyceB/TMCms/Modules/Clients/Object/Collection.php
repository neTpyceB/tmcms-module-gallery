<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Admin\Users;
use neTpyceB\TMCms\Modules\CommonObject;
use neTpyceB\TMCms\Strings\Dstrings;


/**
 * Class Collection
 * @package neTpyceB\TMCms\Modules\Clients\Object
 * @method getTitle() string
 */
class Collection extends CommonObject {
    protected $db_table = 'm_clients_collections';
    protected $multi_lng_fields = ['title'];

    protected $title;
    protected $group_id;

    public function setGroupId($group_id)
    {
        $this->setField('group_id', $group_id);
    }

    public function getGroupId()
    {
        return $this->getField('group_id');
    }

    public function getCollectionId()
    {
    }
}