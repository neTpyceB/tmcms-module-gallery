<?php
namespace neTpyceB\TMCms\Modules\Clients\Object;

use neTpyceB\TMCms\Modules\CommonObject;

/**
 * Class OfferSlide
 * @package neTpyceB\TMCms\Modules\Clients\Object
 * @method setTemplate(string)
 * @method setOrder(string)
 *
 * @method getTemplate() string
 * @method getOrder() string
 */
class OfferSlide extends CommonObject {
    protected $db_table = 'm_clients_offers_slides';
    protected $multi_lng_fields = [];

    protected $offer_id = 0;
    protected $template = '';
    protected $order = 0;

    public function getOfferId()
    {
        return $this->getField('offer_id');
    }

    public function setOfferId($offer_id)
    {
        $this->setField('offer_id', $offer_id);

        return $this;
    }
}