<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Modules\Clients\ModuleClients;
use neTpyceB\TMCms\Modules\CommonObject;
use neTpyceB\TMCms\Modules\Emailer\Object\EmailerCollection;


class Offer extends CommonObject {
    protected $db_table = 'm_clients_offers';
    protected $multi_lng_fields = [];

    protected $client_id = 0;
    protected $collection_id = 0;
    protected $date_added = 0;
    protected $date_updated = 0;
    protected $title = '';

    public function getClientId()
    {
        return $this->getField('client_id');
    }

    public function setClientId($client_id)
    {
        $this->setField('client_id', $client_id);

        return $this;
    }

    public function setCollectionId($collection_id)
    {
        $this->setField('collection_id', $collection_id);

        return $this;
    }

    public function getCollectionId()
    {
        return $this->getField('collection_id');
    }

    public function getDateAdded()
    {
        return $this->getField('date_added');
    }

    protected function beforeSave() {
        $this->setField('date_updated', NOW);

        return $this;
    }

    protected function beforeCreate() {
        $this->setField('date_added', NOW);

        return $this;
    }

    public function deleteObject() {
        // Remove stats
        $emailer_collection = new EmailerCollection();
        $emailer_collection->setWhereOfferId($this->getId());
        $emailer_collection->deleteObjectCollection();

        // Remvoe files
        $client = new Client($this->getClientId());
        $file_path = ModuleClients::getOfferFilesPath($client->getGroupId(), $this->getId());
        FileSystem::remdir(DIR_BASE . $file_path);

        parent::deleteObject();
    }
}