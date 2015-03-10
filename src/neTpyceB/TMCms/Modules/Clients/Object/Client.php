<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Modules\Clients\ModuleClients;
use neTpyceB\TMCms\Modules\CommonObject;
use neTpyceB\TMCms\Cache\Cacher;


/**
 * Class Client
 * @package neTpyceB\TMCms\Modules\Clients\Object
 *
 * @method string getLogin()
 */
class Client extends CommonObject {
    protected $db_table = 'm_clients';

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->getField('group_id');
    }
}