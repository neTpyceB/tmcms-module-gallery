<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Orm\Entity;

/**
 * Class GalleryCategoryEntity
 * @package TMCms\Modules\Gallery\Entity
 *
 * @method string getTitle()
 * @method bool getActive()
 * @method int getOrder()
 *
 * @method setTitle(array)
 * @method setActive(bool)
 * @method $this setOrder(int $order)
 */
class GalleryCategoryEntity extends Entity {
    protected $db_table = 'm_gallery_categories';
    protected $translation_fields = ['title'];
}