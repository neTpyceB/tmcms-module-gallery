<?php
namespace TMCms\AdminTMCms\Modules\Gallery\Object;
use TMCms\AdminTMCms\Orm\Entity;

/**
 * Class GalleryCategory
 * @package TMCms\AdminTMCms\Modules\Gallery\Object
 *
 * @method string getTitle()
 * @method bool getActive()
 * @method int getOrder()
 *
 * @method setTitle(array)
 * @method setActive(bool)
 * @method setOrder(int)
 */
class GalleryCategory extends Entity {
    protected $db_table = 'm_gallery_categories';
    protected $translation_fields = ['title'];
}
