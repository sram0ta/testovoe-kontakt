BX.namespace('BX.CustomCrmDeal');

BX.CustomCrmDeal = {
  params: {},
  suggestTimeout: null,

  init: function(params) {
    this.params = params || {};
    this.bindUI();
  },

  bindUI: function() {
    var input = BX('c-contact-search');
    if (input) {
      BX.bind(input, 'keyup', BX.debounce(this.handleSearch.bind(this), 300));
    }
    var btnCreate = BX('c-contact-create');
    if (btnCreate) {
      BX.bind(btnCreate, 'click', this.openCreateContact.bind(this));
    }
    var btnSave = BX('c-deal-save');
    if (btnSave) {
      BX.bind(btnSave, 'click', this.createDeal.bind(this));
    }
  },

  handleSearch: function() {
    var q = BX('c-contact-search').value.trim();
    var box = BX('c-contact-suggest');
    BX.cleanNode(box);
    if (q.length < 2) { return; }

    BX.ajax.runComponentAction('custom:crm.deal.create', 'searchContacts', {
      mode: 'class',
      signedParameters: this.params.signedParameters,
      data: { query: q }
    }).then(function(response) {
      var items = (response && response.data && response.data.items) || [];
      if (items.length === 0) { return; }
      var ul = BX.create('ul');
      items.forEach(function(it){
        var li = BX.create('li', { text: it.title });
        BX.bind(li, 'click', function(){
          BX('c-contact-search').value = it.title;
          BX('c-contact-id').value = it.id;
          BX.cleanNode(box);
        });
        ul.appendChild(li);
      });
      box.appendChild(ul);
    }).catch(function(err){
      BX.UI.Notification.Center.notify({content: 'Ошибка поиска контактов'});
    });
  },

  openCreateContact: function() {
    var content = BX.create('div', { props: { className: 'c-modal' }, children: [
      BX.create('div', { props:{className:'c-field'}, children:[BX.create('input',{attrs:{placeholder:'Фамилия', id:'m-last'}})]}),
      BX.create('div', { props:{className:'c-field'}, children:[BX.create('input',{attrs:{placeholder:'Имя', id:'m-name'}})]}),
      BX.create('div', { props:{className:'c-field'}, children:[BX.create('input',{attrs:{placeholder:'Отчество', id:'m-second'}})]}),
      BX.create('div', { props:{className:'c-field'}, children:[BX.create('input',{attrs:{placeholder:'Телефон', id:'m-phone'}})]}),
      BX.create('div', { props:{className:'c-field'}, children:[BX.create('input',{attrs:{placeholder:'Email', id:'m-email'}})]}),
    ] });

    BX.UI.Dialogs.MessageBox.show({
      title: 'Новый контакт',
      message: content,
      buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
      onOk: function(messageBox){
        var data = {
          LAST_NAME: BX('m-last').value.trim(),
          NAME: BX('m-name').value.trim(),
          SECOND_NAME: BX('m-second').value.trim(),
          PHONE: BX('m-phone').value.trim(),
          EMAIL: BX('m-email').value.trim()
        };
        return BX.ajax.runComponentAction('custom:crm.deal.create','createContact',{
          mode:'class',
          signedParameters: BX.CustomCrmDeal.params.signedParameters,
          data: { data: data }
        }).then(function(res){
          if (res && res.data && res.data.success) {
            var id = res.data.contactId;
            BX('c-contact-id').value = id;
            var full = [data.LAST_NAME, data.NAME, data.SECOND_NAME].filter(Boolean).join(' ');
            BX('c-contact-search').value = full || ('#'+id);
            BX.UI.Notification.Center.notify({content: 'Контакт создан'});
            messageBox.close();
          } else {
            BX.UI.Notification.Center.notify({content: (res.data && res.data.error) || 'Ошибка создания'});
          }
        }).catch(function(){
          BX.UI.Notification.Center.notify({content: 'Ошибка создания контакта'});
        });
      }
    });
  },

  createDeal: function(){
    var title = BX('c-deal-title').value.trim();
    var contactId = parseInt(BX('c-contact-id').value || '0', 10);
    var sum = parseFloat(BX('c-deal-amount').value || '0');
    var desc = BX('c-deal-desc').value || '';

    if (!title || !contactId) {
      BX.UI.Notification.Center.notify({content: 'Заполните обязательные поля'});
      return;
    }

    BX.ajax.runComponentAction('custom:crm.deal.create','createDeal',{
      mode:'class',
      signedParameters: this.params.signedParameters,
      data:{ data: { TITLE: title, CONTACT_ID: contactId, OPPORTUNITY: sum, COMMENTS: desc } }
    }).then(function(res){
      if (res && res.data && res.data.success) {
        BX.UI.Notification.Center.notify({content: 'Сделка создана (#'+res.data.dealId+')'});
        location.href = '/crm/deal/details/'+res.data.dealId+'/';
      } else {
        BX.UI.Notification.Center.notify({content: (res.data && res.data.error) || 'Ошибка сохранения'});
      }
    }).catch(function(){
      BX.UI.Notification.Center.notify({content: 'Ошибка сохранения'});
    });
  }
};
