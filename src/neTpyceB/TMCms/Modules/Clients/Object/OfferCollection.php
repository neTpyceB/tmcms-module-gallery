<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Modules\CommonObjectCollection;

class OfferCollection extends CommonObjectCollection {
    protected $db_table = 'm_clients_offers';

    /**
     * @param int $collection_id
     * @return $this
     */
    public function setWhereCollectionId($collection_id)
    {
        $this->setFilterValue('collection_id', $collection_id);

        return $this;
    }

    public function setWhereClientId($client_id)
    {
        $this->setFilterValue('client_id', $client_id);

        return $this;
    }
}