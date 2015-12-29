<?php
namespace TMCms\Modules\Gallery\Object;

use TMCms\Orm\EntityRepository;

class GalleryCategoryCollection extends EntityRepository {
    protected $db_table = 'm_gallery_categories';
}
