<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Loader;

class CustomAddDealCommentActivity extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = [
            'Title' => '',
            'DealId' => null,
            'Message' => '',
            'COMMENT_ID' => 0,
        ];
    }

    public function Execute()
    {
        if (!Loader::includeModule('crm')) {
            $this->WriteToTrackingService('Модуль CRM не установлен', 0, CBPTrackingType::Error);
            return CBPActivityExecutionStatus::Closed;
        }

        $dealId = (int)$this->DealId;
        $message = (string)$this->Message;
        if ($dealId <= 0 || $message === '') {
            $this->WriteToTrackingService('DealId/Message не заполнены', 0, CBPTrackingType::Error);
            return CBPActivityExecutionStatus::Closed;
        }

        $commentId = \CCrmTimelineComment::Add([
            'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
            'ENTITY_ID' => $dealId,
            'TEXT' => $message,
            'AUTHOR_ID' => (int)$GLOBALS['USER']->GetID() ?: 1,
            'BINDINGS' => [ ['ENTITY_TYPE_ID' => CCrmOwnerType::Deal, 'ENTITY_ID' => $dealId] ],
        ]);

        $this->COMMENT_ID = (int)$commentId;
        return CBPActivityExecutionStatus::Closed;
    }

    public static function ValidateProperties($arTestProperties, CBPWorkflowTemplateUser $user = null)
    {
        $errors = [];
        if (!isset($arTestProperties['DealId']) || (int)$arTestProperties['DealId'] <= 0) {
            $errors[] = ['code' => 'DealId', 'message' => 'Нужно указать DealId'];
        }
        if (!isset($arTestProperties['Message']) || trim((string)$arTestProperties['Message']) === '') {
            $errors[] = ['code' => 'Message', 'message' => 'Нужно указать Message'];
        }
        return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
    }

    public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = '')
    {
        if (!is_array($currentValues)) {
            $currentValues = [
                'deal_id' => '',
                'message' => '',
            ];
        }
        return '<div style="padding:8px">'
            . 'Deal ID: <input type="text" name="deal_id" value="'.htmlspecialcharsbx($currentValues['deal_id']).'"/><br><br>'
            . 'Message: <input type="text" name="message" value="'.htmlspecialcharsbx($currentValues['message']).'" size="50"/>'
            . '</div>';
    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
    {
        $props = [
            'DealId' => (int)($currentValues['deal_id'] ?? 0),
            'Message' => (string)($currentValues['message'] ?? ''),
        ];

        $errors = self::ValidateProperties($props);
        if ($errors) { return false; }

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
        $arCurrentActivity['Properties'] = $props;
        return true;
    }
}
