<?php
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\DealTable;

require_once $_SERVER['DOCUMENT_ROOT'].'/local/js/custom/crm.deal/extension.php';

const CUSTOM_STAGE_NEW_ID = 'NEW';
const CUSTOM_STAGE_OVERDUE_ID = 'OVERDUE';
const CUSTOM_CATEGORY_ID = 0;

// Перевод "Новых" старше 3 дней в "Просрочена"
function custom_agent_overdue_deals(): string
{
    if (!Loader::includeModule('crm')) { return __FUNCTION__.'();'; }

    $threeDaysAgo = new DateTime();
    $threeDaysAgo->add('-3 days');

    $list = DealTable::getList([
        'select' => ['ID','STAGE_ID','CATEGORY_ID','DATE_CREATE'],
        'filter' => [
            '=CATEGORY_ID' => CUSTOM_CATEGORY_ID,
            '=STAGE_ID' => CUSTOM_STAGE_NEW_ID,
            '<=DATE_CREATE' => $threeDaysAgo,
        ],
        'limit' => 200
    ]);

    while ($d = $list->fetch()) {
        $upd = DealTable::update((int)$d['ID'], [ 'STAGE_ID' => CUSTOM_STAGE_OVERDUE_ID ]);
        if ($upd->isSuccess()) {
            if (Loader::includeModule('crm')) {
                \CCrmTimelineComment::Add([
                    'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
                    'ENTITY_ID' => (int)$d['ID'],
                    'TEXT' => 'Автоматический перевод из-за просрочки',
                    'AUTHOR_ID' => 1,
                    'BINDINGS' => [ ['ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => (int)$d['ID']] ],
                ]);
            }
        }
    }

    return __FUNCTION__.'();';
}

if (class_exists('CAgent')) {
    $found = false;
    $rs = CAgent::GetList([], ['NAME' => 'custom_agent_overdue_deals%']);
    if ($rs && $rs->Fetch()) { $found = true; }
    if (!$found) {
        CAgent::AddAgent('custom_agent_overdue_deals();', 'main', 'N', 3600, '', 'Y');
    }
}

// Обработчик изменения сделки
EventManager::getInstance()->addEventHandler('crm', 'OnAfterCrmDealUpdate', function(array &$fields) {
    if (!isset($fields['ID'])) { return; }

    $dealId = (int)$fields['ID'];
    $oldStage = (string)($fields['~ORIGINAL_STAGE_ID'] ?? '');
    $newStage = (string)($fields['STAGE_ID'] ?? '');

    if ($newStage === '') { return; }

    global $USER; $userId = is_object($USER) ? (int)$USER->GetID() : 0;

    $line = sprintf(
        "[%s] DEAL #%d: %s -> %s | USER: %d\n",
        (new DateTime())->toString(),
        $dealId,
        $oldStage ?: '-',
        $newStage,
        $userId
    );

    $logDir = $_SERVER['DOCUMENT_ROOT'].'/upload/logs';
    if (!is_dir($logDir)) { mkdir($logDir, 0775, true); }
    file_put_contents($logDir.'/deal_stage_changes.log', $line, FILE_APPEND);
});
