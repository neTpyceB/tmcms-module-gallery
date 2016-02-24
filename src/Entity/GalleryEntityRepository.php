<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Orm\EntityRepository;

class GalleryEntityRepository extends EntityRepository {
    protected $db_table = 'm_gallery';

    protected $table_structure = [
        'fields' => [
            'category_id' => [
                'type' => 'index',
            ],
            'title' => [
                'type' => 'translation',
            ],
            'image' => [
                'type' => 'varchar',
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