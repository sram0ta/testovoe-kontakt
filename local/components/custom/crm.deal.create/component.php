<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Main\Type\DateTime;

class CustomCrmDealCreateComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions(): array
    {
        return [
            'searchContacts' => [
                'prefilters' => [new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST])],
            ],
            'createContact' => [
                'prefilters' => [new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST])],
            ],
            'createDeal' => [
                'prefilters' => [new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST])],
            ],
        ];
    }

    public function onPrepareComponentParams($params)
    {
        $params['STAGE_NEW_ID'] = trim($params['STAGE_NEW_ID'] ?? 'NEW');
        $params['STAGE_OVERDUE_ID'] = trim($params['STAGE_OVERDUE_ID'] ?? 'OVERDUE');
        $params['CATEGORY_ID'] = (string)($params['CATEGORY_ID'] ?? '0');
        $params['ENABLE_BP'] = ($params['ENABLE_BP'] ?? 'Y') === 'Y' ? 'Y' : 'N';
        return $params;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('crm')) {
            ShowError('Модуль CRM не установлен');
            return;
        }
        \CJSCore::Init(['custom_crm_deal']);
        $this->includeComponentTemplate();
    }

    /** AJAX: Поиск контактов по ФИО */
    public function searchContactsAction(string $query): Json
    {
        if (!Loader::includeModule('crm')) { return new Json(['success' => false, 'error' => 'CRM module not loaded']); }
        $query = trim($query);
        if ($query === '') { return new Json(['success' => true, 'items' => []]); }

        $items = [];
        $res = ContactTable::getList([
            'select' => ['ID','NAME','SECOND_NAME','LAST_NAME'],
            'filter' => [
                'LOGIC' => 'OR',
                ['%NAME' => $query],
                ['%LAST_NAME' => $query],
                ['%SECOND_NAME' => $query],
            ],
            'limit' => 20,
            'order' => ['LAST_NAME' => 'ASC','NAME' => 'ASC']
        ]);
        while ($row = $res->fetch()) {
            $fullName = trim($row['LAST_NAME'].' '.$row['NAME'].' '.$row['SECOND_NAME']);
            $items[] = [
                'id' => (int)$row['ID'],
                'title' => $fullName !== '' ? $fullName : ('#'.$row['ID']),
            ];
        }
        return new Json(['success' => true, 'items' => $items]);
    }

    /** AJAX: Создание нового контакта */
    public function createContactAction(array $data): Json
    {
        if (!Loader::includeModule('crm')) { return new Json(['success' => false, 'error' => 'CRM module not loaded']); }

        $fields = [
            'NAME' => trim((string)($data['NAME'] ?? '')),
            'LAST_NAME' => trim((string)($data['LAST_NAME'] ?? '')),
            'SECOND_NAME' => trim((string)($data['SECOND_NAME'] ?? '')),
            'TYPE_ID' => 'CLIENT',
            'OPENED' => 'Y',
            'CREATED_BY_ID' => (int)$GLOBALS['USER']->GetID(),
            'ASSIGNED_BY_ID' => (int)$GLOBALS['USER']->GetID(),
        ];

        if (!empty($data['PHONE'])) {
            $fields['FM']['PHONE'] = [
                'n0' => ['VALUE' => trim($data['PHONE']), 'VALUE_TYPE' => 'WORK']
            ];
        }
        if (!empty($data['EMAIL'])) {
            $fields['FM']['EMAIL'] = [
                'n0' => ['VALUE' => trim($data['EMAIL']), 'VALUE_TYPE' => 'WORK']
            ];
        }

        $addRes = \CCrmContact::Add($fields, true, [ 'DISABLE_USER_FIELD_CHECK' => true ]);
        if ($addRes === false) {
            global $APPLICATION; $e = $APPLICATION->GetException();
            return new Json(['success'=>false, 'error' => $e ? $e->GetString() : 'Ошибка создания контакта']);
        }
        return new Json(['success'=>true, 'contactId' => (int)$addRes]);
    }

    /** AJAX: Создание сделки и привязка к контакту */
    public function createDealAction(array $data): Json
    {
        if (!Loader::includeModule('crm')) { return new Json(['success' => false, 'error' => 'CRM module not loaded']); }

        $title = trim((string)($data['TITLE'] ?? ''));
        $contactId = (int)($data['CONTACT_ID'] ?? 0);
        $amount = (float)($data['OPPORTUNITY'] ?? 0);
        $description = (string)($data['COMMENTS'] ?? '');

        if ($title === '' || $contactId <= 0) {
            return new Json(['success'=>false, 'error' => 'Заполните название и выберите контакт']);
        }

        $stage = $this->arParams['STAGE_NEW_ID'];
        $categoryId = (int)$this->arParams['CATEGORY_ID'];

        $dealFields = [
            'TITLE' => $title,
            'CONTACT_ID' => $contactId,
            'CATEGORY_ID' => $categoryId,
            'STAGE_ID' => $stage,
            'ASSIGNED_BY_ID' => (int)$GLOBALS['USER']->GetID(),
            'OPENED' => 'Y',
            'COMMENTS' => $description,
            'CURRENCY_ID' => \CCrmCurrency::GetBaseCurrencyID(),
            'OPPORTUNITY' => $amount,
            'BEGINDATE' => new DateTime(),
        ];

        $result = DealTable::add($dealFields);
        if (!$result->isSuccess()) {
            return new Json(['success'=>false, 'error' => implode('; ', $result->getErrorMessages())]);
        }

        $dealId = (int)$result->getId();

        if ($this->arParams['ENABLE_BP'] === 'Y' && Loader::includeModule('bizproc')) {
            $this->startBizproc($dealId);
        }

        return new Json(['success'=>true, 'dealId' => $dealId]);
    }

    protected function startBizproc(int $dealId): void
    {
        if (!Loader::includeModule('bizproc') || !Loader::includeModule('crm')) { return; }
        $documentType = ['crm', 'CCrmDocumentDeal', 'DEAL'];
        $documentId = ['crm', 'CCrmDocumentDeal', (string)$dealId];

        $tplList = \CBPWorkflowTemplateLoader::GetList([], [
            'DOCUMENT_TYPE' => $documentType,
            'ACTIVE' => 'Y',
            'AUTO_EXECUTE' => \CBPDocumentEventType::Create,
        ], false, false, ['ID']);

        while ($tpl = $tplList->Fetch()) {
            try {
                \CBPDocument::StartWorkflow($tpl['ID'], $documentId, [], $errors);
            } catch (\Throwable $e) {

            }
        }
    }
}
