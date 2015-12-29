<?php
namespace TMCms\AdminTMCms\Modules\Gallery\Object;

use TMCms\AdminTMCms\Orm\EntityRepository;

class GalleryCategoryCollection extends EntityRepository {
    protected $db_table = 'm_gallery_categories';
}
