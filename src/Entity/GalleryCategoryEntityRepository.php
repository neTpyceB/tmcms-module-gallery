<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Orm\EntityRepository;

class GalleryCategoryEntityRepository extends EntityRepository {
    protected $db_table = 'm_gallery_categories';

    protected $table_structure = [
        'fields' => [
            'title' => [
                'type' => 'translation',
            ],
            'active' => [
                'type' => 'bool',
            ],
            'order' => [
                'type' => 'int',
                'unsigned' => true,
            ],
        ]
    ];
}