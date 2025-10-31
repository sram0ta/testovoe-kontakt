<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

CJSCore::RegisterExt('custom_crm_deal', [
    'js' => ['/local/js/custom/crm.deal/crm.deal.js'],
    'rel' => ['ajax', 'popup', 'fx', 'ui.notification', 'ui.dialogs.messagebox'],
]);
