<?php
namespace neTpyceB\TMCms\Modules\Gallery\Object;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Modules\CommonObject;
use neTpyceB\TMCms\Modules\Gallery\ModuleGallery;
use neTpyceB\TMCms\Modules\Images\Object\ImageCollection;


/**
 * Class Gallery
 * @package neTpyceB\TMCms\Modules\Gallery\Object
 *
 * @method string getTitle()
 */
class Gallery extends CommonObject {
    protected $db_table = 'm_gallery';
    protected $multi_lng_fields = ['title'];

    protected $title = '';
    protected $active = '';
    protected $order = 0;
    protected $category_id = 0;

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->getField('category_id');
    }

    public function deleteObject() {
        $images_collection = new ImageCollection();
        $images_collection->setWhereItemType('gallery');
        $images_collection->setWhereItemId($this->getId());
        $images_collection->deleteObjectCollection();

        $path = DIR_BASE . ModuleGallery::getGalleryImagesPath($this->getId());
        FileSystem::remdir($path);

        parent::deleteObject();
    }
}