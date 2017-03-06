<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Files\FileSystem;
use TMCms\Orm\Entity;
use TMCms\Modules\Gallery\ModuleGallery;
use TMCms\Modules\Images\Entity\ImageEntityRepository;

/**
 * Class GalleryEntity
 * @package TMCms\Modules\Gallery\Entity
 *
 * @method string getTitle()
 * @method bool getActive()
 * @method int getOrder()
 * @method int getCategoryId()
 *
 * @method setTitle(array)
 * @method setActive(bool)
 * @method setOrder(int)
 * @method setCategoryId(int)
 */
class GalleryEntity extends Entity {
    protected $translation_fields = ['title'];

    // Before delete object
    public function deleteObject() {
        // Delete Collection images from DB
        $images_collection = new ImageEntityRepository();
        $images_collection->setWhereItemType('gallery');
        $images_collection->setWhereItemId($this->getId());
        $images_collection->deleteObjectCollection();

        // Delete files - remove folder
        $path = DIR_BASE . ModuleGallery::getGalleryImagesPath($this->getId());
        FileSystem::remdir($path);

        parent::deleteObject();
    }
}