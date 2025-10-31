<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

$arActivityDescription = [
    'NAME' => 'Добавить комментарий к сделке',
    'DESCRIPTION' => 'Добавляет комментарий к сделке по ID',
    'TYPE' => ['activity', 'robot_activity'],
    'CLASS' => 'CustomAddDealCommentActivity',
    'JSCLASS' => 'BizProcActivity',
    'CATEGORY' => ['ID' => 'other'],
    'RETURN' => [
        'COMMENT_ID' => [
            'NAME' => 'ID комментария',
            'TYPE' => 'int',
        ],
    ],
];
