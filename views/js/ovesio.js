function ajaxSubmitForm(form) {
  form.querySelectorAll('.ov-alert').forEach(alert => alert.remove());

  const fields = $(form).serialize();

  var btn = form.querySelector('button[type="submit"]');
  if (!btn) { // poate a fost din modal
    btn = document.querySelector('#ovesioModal #btn_save_modal');
  }

  var originalBtnHtml = btn?.innerHTML;

  return $.ajax({
    url: form.action,
    type: form.method,
    dataType: 'json',
    data: fields,
    beforeSend: function() {
      // Show loading spinner
      if (btn) {
        btn.classList.add('ov-btn-loading');
        btn.innerHTML = '<span class="ov-spinner ov-spinner-sm"></span> Loading...';
      }
    },
    complete: function() {
      // Restore button state
      if (btn) {
        btn.classList.remove('ov-btn-loading');
        btn.innerHTML = originalBtnHtml;
      }
    },
    error: function(xhr, status, error) {
      if (xhr.status == 422) {
        json = xhr.responseJSON;

        for (let [field, message] of Object.entries(json.errors)) {
          let parts = field.split('.');
          if (parts.length > 1) {
            field = parts[0] + '[' + parts.slice(1).join('][') + ']';
          }

          const input = form.querySelector(`[name="${field}"]`);
          if (input) {
            let existing = input.parentNode.querySelector('.ov-error-message');
            if (existing) {
              input.parentNode.removeChild(input.parentNode.querySelector('.ov-error-message')); // remove existing error if any
            }

            const errorNode = document.createElement('div');
            errorNode.classList.add('ov-error-message');
            errorNode.innerText = message;
            input.parentNode.appendChild(errorNode);
          }
        }
      }
    }
  })
}

function ajaxGet(url, data = {}, dataType = 'json') {
  return $.ajax({
    url: url,
    type: 'GET',
    data: data,
    dataType: dataType
  });
}

function ajaxPost(url, data = {}) {
  return $.ajax({
    url: url,
    type: 'POST',
    dataType: 'json',
    data: data
  });
}

window.ovesio = {};

ovesio.buildAlert = function(message, type = 'info', size = 'sm') {
  const html = `
    <div class="ov-alert ov-alert-${size} ov-alert-${type} ov-mb-3" role="alert">
      ${message}
    </div>
  `;

  const div = document.createElement('div');
  div.innerHTML = html.trim();

  return div.firstChild;
}

ovesio.selectOptions = function(selectNode, options, label = 'label', value = 'value') {
  const selectedValue = selectNode.getAttribute('value');

  let optionsHtml = '';

  options.forEach(option => {
    if (option[value] === selectedValue) {
      optionsHtml += `<option value="${option[value]}" selected>${option[label]}</option>`;
    } else {
      optionsHtml += `<option value="${option[value]}">${option[label]}</option>`;
    }
  });

  selectNode.innerHTML = optionsHtml;
}

ovesio.connectAPI = function(e) {
  e.preventDefault();

  const form = e.target;

  ajaxSubmitForm(form).then((res) => {
    if (!res.connected) { // step 1
      if (res.success) {
        form.querySelectorAll('.ov-form-group').forEach(group => group.classList.add('ov-hidden'));
        form.querySelector('#default_language_group').classList.remove('ov-hidden');

        const selectNode = form.querySelector('#default_language');
        res.languages.unshift({ code: 'auto', name: 'Auto' });
        this.selectOptions(selectNode, res.languages, 'name', 'code');
        selectNode.disabled = false;

        const alertNode = this.buildAlert(res.message, 'success');

        // insert alert at the top of the form
        form.insertBefore(alertNode, form.firstChild);
      } else {
        const alertNode = this.buildAlert(res.message, 'danger');
        form.insertBefore(alertNode, form.firstChild);
      }
    } else { // step 2
      document.querySelector('#btn_disconnect').classList.remove('ov-hidden');
      document.querySelector('#workflow_cards').classList.remove('ov-hidden');
      document.querySelector('#general_cron_info').classList.remove('ov-hidden');

      const connCard = document.querySelector('#connection_card')
      connCard.classList.add('ov-hidden');

      const alertNode = this.buildAlert(res.message, 'success');
      connCard.parentNode.insertBefore(alertNode, connCard.nextSibling);
      setTimeout(() => alertNode.remove(), 5000);
    }
  });
}

ovesio.disconnectApi = function(e) {
  e.preventDefault();

  const btn = e.target;
  const originalBtnHtml = btn.innerHTML;
  const url = btn.getAttribute('data-url');

  const confirmMessage = btn.getAttribute('data-confirm');
  if (!confirm(confirmMessage)) {
    return;
  }

  btn.classList.add('ov-btn-loading');
  btn.innerHTML = '<span class="ov-spinner ov-spinner-sm"></span> Loading...';
  ajaxPost(url).then((res) => {
    btn.classList.remove('ov-btn-loading');
    btn.innerHTML = originalBtnHtml;

    if (res.success) {
      document.querySelector('#btn_disconnect').classList.add('ov-hidden');
      document.querySelector('#workflow_cards').classList.add('ov-hidden');
      document.querySelector('#general_cron_info').classList.add('ov-hidden');

      const connCard = document.querySelector('#connection_card')
      connCard.classList.remove('ov-hidden');
      const alertNode = this.buildAlert(res.message, 'success');
      connCard.parentNode.insertBefore(alertNode, connCard);
      setTimeout(() => alertNode.remove(), 5000);
    }
  });
}

ovesio.openModal = function openModal(content, title) {
  const modal = document.getElementById('ovesioModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalContent = document.getElementById('modalContent');

  modalTitle.innerHTML = title;
  modalContent.innerHTML = content;

  modal.style.display = 'block';
}

ovesio.closeModal = function() {
  document.getElementById('ovesioModal').style.display = 'none';
}

ovesio.saveModal = function() {
  // Handle save logic here
  const modal = document.getElementById('ovesioModal');
  modal.querySelector('form').dispatchEvent(new Event('submit', { cancelable: true }));
}

// Close modal when clicking outside
window.onmousedown = function(event) {
  const modal = document.getElementById('ovesioModal');
  if (event.target === modal) {
    setTimeout(() => ovesio.closeModal(), 300); // simulate a delay
  }
}

ovesio.modalButton = function(e) {
  e.preventDefault();

  const btn = e.target;
  const url = btn.getAttribute('data-url');
  const title = btn.getAttribute('data-title');

  ajaxGet(url, {}, 'html').then((res) => {
    this.openModal(res, title);
  });
}

ovesio.generateContentFormSave = function(e) {
  e.preventDefault();

  const form = e.target;

  ajaxSubmitForm(form).then((res) => {
    if (res.success) {
      const alertNode = this.buildAlert(res.message, 'success');

      if (res.card_html) {
        const card = document.getElementById('generate_content_card');
        card.outerHTML = res.card_html.trim();
      }

      const feedbackContainer = document.querySelector('#generate_content_card .ov-feedback-container');
      feedbackContainer.appendChild(alertNode);
      setTimeout(() => alertNode.remove(), 5000);
    }

    ovesio.closeModal();
  });
};

ovesio.generateSeoFormSave = function(e) {
  e.preventDefault();

  const form = e.target;

  ajaxSubmitForm(form).then((res) => {
    if (res.success) {
      const alertNode = this.buildAlert(res.message, 'success');

      if (res.card_html) {
        const card = document.getElementById('generate_seo_card');
        card.outerHTML = res.card_html.trim();
      }

      const feedbackContainer = document.querySelector('#generate_seo_card .ov-feedback-container');
      feedbackContainer.appendChild(alertNode);
      setTimeout(() => alertNode.remove(), 5000);
    }

    ovesio.closeModal();
  });
};

ovesio.translateFormSave = function(e) {
  e.preventDefault();

  const form = e.target;

  ajaxSubmitForm(form).then((res) => {
    if (res.success) {
      const alertNode = this.buildAlert(res.message, 'success');

      if (res.card_html) {
        const card = document.getElementById('translate_card');
        card.outerHTML = res.card_html.trim();
      }

      const feedbackContainer = document.querySelector('#translate_card .ov-feedback-container');
      feedbackContainer.appendChild(alertNode);
      setTimeout(() => alertNode.remove(), 5000);
    }

    ovesio.closeModal();
  });
};

ovesio.updateActivityStatus = function(activityId, status) {
  const button = event.target;
  const svg = button.querySelector('svg');

  button.disabled = true;
  svg.classList.add('ov-spin');

  ajaxPost(window.url_update_status, {
    activity_id: activityId,
    status: status
  }).then((res) => {
    if (!res.success) {
      button.setAttribute('data-tooltip', res.error);
      button.classList.add('ov-text-danger');
      svg.classList.remove('ov-spin');
      return;
    }

    if (res.status == 'completed') {
      // document.querySelector('#buttonStatus' + activityId).classList.add('ov-hidden');
      document.querySelector('#buttonResponse' + activityId).classList.remove('ov-hidden');

      let old_status = document.querySelector('#buttonStatus' + activityId).closest('td').querySelector('.ov-status-badge');

      // replace node
      const new_status = document.createElement('span');
      new_status.className = 'ov-status-badge ' + res.status_display.class;
      new_status.innerText = res.status_display.text;

      old_status.parentNode.replaceChild(new_status, old_status);
    }

    // Add blink effect
    const status_badge = document.querySelector('#buttonResponse' + activityId).closest('td').querySelector('.ov-status-badge');
    status_badge.style.animation = 'blink 0.8s ease-in-out 3';
    setTimeout(() => {
      delete status_badge.style.animation;
    }, 4);

    button.disabled = false;
    svg.classList.remove('ov-spin');
  });
};

ovesio.generateContent = function(e) {
  const btn = e.target;
  const originalHtml = btn.innerHTML;
  btn.classList.add('ov-btn-loading');
  btn.disable = true;
  btn.innerHTML = '<span class="ov-spinner ov-spinner-sm"></span> ' + originalHtml;

  const url   = btn.getAttribute('data-href');
  const route = btn.getAttribute('data-route');

  const form = document.querySelector('form.table-responsive');
  if (!form) {
    console.error('Form not found');
    return;
  }

  const selected = [];
  form.querySelectorAll('.js-bulk-action-checkbox:checked').forEach(checkbox => {
    selected.push(checkbox.value);
  })

  ajaxPost(url, { selected: selected, from: route, activity_type: 'generate_content'}).then((res) => {
    btn.classList.remove('ov-btn-loading');
    btn.innerHTML = originalHtml;
    btn.disable = false;

    let alert;
    if (res.success) {
      alert = this.buildAlert(res.message, 'success', 'md');
    } else {
      alert = this.buildAlert(res.message, 'danger', 'md');
    }

    alert.classList.add('ov-mt-3');
    form.parentNode.insertBefore(alert, form);
    setTimeout(() => alert.remove(), 5000);
  });
}

ovesio.generateSeo = function(e) {
  const btn = e.target;
  const originalHtml = btn.innerHTML;
  btn.classList.add('ov-btn-loading');
  btn.disable = true;
  btn.innerHTML = '<span class="ov-spinner ov-spinner-sm"></span> ' + originalHtml;

  const url   = btn.getAttribute('data-href');
  const route = btn.getAttribute('data-route');

  const form = document.querySelector('form.table-responsive');
  if (!form) {
    console.error('Form not found');
    return;
  }

  const selected = [];
  form.querySelectorAll('.js-bulk-action-checkbox:checked').forEach(checkbox => {
    selected.push(checkbox.value);
  })

  ajaxPost(url, { selected: selected, from: route, activity_type: 'generate_seo'}).then((res) => {
    btn.classList.remove('ov-btn-loading');
    btn.innerHTML = originalHtml;
    btn.disable = false;

    let alert;
    if (res.success) {
      alert = this.buildAlert(res.message, 'success', 'md');
    } else {
      alert = this.buildAlert(res.message, 'danger', 'md');
    }

    alert.classList.add('ov-mt-3');
    form.parentNode.insertBefore(alert, form);
    setTimeout(() => alert.remove(), 5000);
  });
}

ovesio.translate = function(e) {
  const btn = e.target;
  const originalHtml = btn.innerHTML;
  btn.classList.add('ov-btn-loading');
  btn.disable = true;
  btn.innerHTML = '<span class="ov-spinner ov-spinner-sm"></span> ' + originalHtml;

  const url   = btn.getAttribute('data-href');
  const route = btn.getAttribute('data-route');

  const form = document.querySelector('form.table-responsive');
  if (!form) {
    console.error('Form not found');
    return;
  }

  const selected = [];
  form.querySelectorAll('.js-bulk-action-checkbox:checked').forEach(checkbox => {
    selected.push(checkbox.value);
  })

  ajaxPost(url, { selected: selected, from: route, activity_type: 'translate'}).then((res) => {
    btn.classList.remove('ov-btn-loading');
    btn.innerHTML = originalHtml;
    btn.disable = false;

    let alert;
    if (res.success) {
      alert = this.buildAlert(res.message, 'success', 'md');
    } else {
      alert = this.buildAlert(res.message, 'danger', 'md');
    }

    alert.classList.add('ov-mt-3');
    form.parentNode.insertBefore(alert, form);
    setTimeout(() => alert.remove(), 5000);
  });
}