<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    'PARAMETERS' => [
        'STAGE_NEW_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID стадии "Новая"',
            'TYPE' => 'STRING',
            'DEFAULT' => 'NEW',
        ],
        'STAGE_OVERDUE_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID стадии "Просрочена"',
            'TYPE' => 'STRING',
            'DEFAULT' => 'OVERDUE',
        ],
        'CATEGORY_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID направления сделки',
            'TYPE' => 'STRING',
            'DEFAULT' => '0',
        ],
        'ENABLE_BP' => [
            'PARENT' => 'ADDITIONAL_SETTINGS',
            'NAME' => 'Запускать бизнес-процесс при создании',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
    ],
];
