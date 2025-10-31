<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); } ?>

<div class="c-crm-deal-create">
  <div class="c-field">
    <label>Название сделки<span class="req">*</span></label>
    <input type="text" id="c-deal-title" class="c-input" placeholder="Например: Поставка оборудования"/>
  </div>

  <div class="c-field">
    <label>Контакт<span class="req">*</span></label>
    <div class="c-flex">
      <input type="text" id="c-contact-search" class="c-input" placeholder="Поиск по ФИО" autocomplete="off"/>
      <button class="c-btn" id="c-contact-create">Новый контакт</button>
    </div>
    <input type="hidden" id="c-contact-id"/>
    <div id="c-contact-suggest" class="c-suggest"></div>
  </div>

  <div class="c-field">
    <label>Сумма</label>
    <input type="number" id="c-deal-amount" class="c-input" placeholder="0" step="0.01" min="0"/>
  </div>

  <div class="c-field">
    <label>Описание</label>
    <textarea id="c-deal-desc" class="c-input" rows="4" placeholder="Краткое описание"></textarea>
  </div>

  <div class="c-actions">
    <button class="c-btn c-btn--primary" id="c-deal-save">Сохранить</button>
  </div>
</div>

<script>
 BX.ready(function() {
   BX.CustomCrmDeal.init({
     component: '<?=CUtil::JSEscape($this->getComponent()->getName())?>',
     signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
   });
 });
</script>
