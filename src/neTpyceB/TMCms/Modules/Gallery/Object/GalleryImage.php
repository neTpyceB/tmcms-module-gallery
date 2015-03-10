<?php
namespace neTpyceB\TMCms\Modules\Gallery\Object;
use neTpyceB\TMCms\Modules\CommonObject;

/**
 * Class Client
 * @package neTpyceB\TMCms\Modules\Gallery\Object
 */
class GalleryImage extends CommonObject {
    protected $db_table = 'm_gallery';

    protected $image = '';
    protected $title = '';
    protected $order = 0;
    protected $category_id = 0;

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->getField('category_id');
    }
}