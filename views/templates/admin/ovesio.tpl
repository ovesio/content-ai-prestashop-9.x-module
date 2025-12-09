<link rel="stylesheet" type="text/css" href="<?php echo _MODULE_DIR_; ?>ovesio/views/css/ovesio.css" />
<div class="ov-reset">
  <!-- Top Bar -->
  <div class="ov-top-bar">
    <div></div>
    <div>
      <?php if ($count_errors): ?>
      <a class="ov-badge ov-badge-danger" href="<?php echo $url_list_errors; ?>" target="_blank"><?php echo $text_errors; ?>: <?php echo $count_errors; ?></a>
      <?php endif; ?>
      <a href="<?php echo $url_list; ?>" type="button" class="ov-btn ov-btn-secondary">
        <?php echo $text_activity_list; ?>
      </a>
      <button type="button" class="ov-btn ov-btn-danger <?php echo $connected ? '' : 'ov-hidden'; ?>" id="btn_disconnect" onclick="ovesio.disconnectApi(event)" data-url="<?php echo $url_disconnect; ?>" data-confirm="<?php echo $text_disconnect_confirm; ?>"><?php echo $button_disconnect; ?></button>
    </div>
  </div>

  <div id="ovesio_feedback_container"></div>

  <?php echo $connect_form; ?>

  <?php if ($connected && $errors): ?>
  <div class="ov-alert ov-alert-danger">
    <ul class="ov-mb-0">
      <?php foreach ($errors as $error): ?>
      <li><span style="font-weight: bold"><?php echo $text_api_check_failed; ?></span> <?php echo $error; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- Flexbox container with horizontal scroll -->
  <div class="ov-table-responsive">
    <div class="ov-cards-container <?php echo $connected ? '' : 'ov-hidden'; ?>" id="workflow_cards">

      <!-- Store Info Card -->
      <div class="ov-card ov-workflow-card ov-card-home">
        <div class="ov-card-body">
          <h4 class="ov-card-title ov-text-xl"><?php echo $text_store_info; ?></h4>
          <div style="text-align: center; padding: 1em 0;">
            <div style="width: 80px; height: 80px; margin: 0 auto 1em; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22V12H15V22" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <p class="ov-text-muted ov-mb-3"><?php echo $text_store_connected; ?></p>
            <div class="ov-badge ov-badge-success"><?php echo $text_status_active; ?></div>
          </div>
        </div>
      </div>

      <!-- Flow Arrow 0 -->
      <div class="ov-flow-arrow">
        <svg width="30" height="24" viewBox="0 0 30 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 12H28M28 12L22 6M28 12L22 18" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <!-- Card 1 -->
      <?php echo $generate_content_card; ?>

      <!-- Flow Arrow 1 -->
      <div class="ov-flow-arrow">
        <svg width="30" height="24" viewBox="0 0 30 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 12H28M28 12L22 6M28 12L22 18" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <!-- Card 2 -->
      <?php echo $generate_seo_card; ?>

      <!-- Flow Arrow 2 -->
      <div class="ov-flow-arrow">
        <svg width="30" height="24" viewBox="0 0 30 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 12H28M28 12L22 6M28 12L22 18" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <!-- Card 3 -->
      <?php echo $translate_card; ?>

    </div>
  </div>

  <!-- CRON Information Well -->
  <div class="ov-mb-3 <?php echo $connected ? '' : 'ov-hidden'; ?>" id="general_cron_info">
    <h4 class="ov-mb-2"><?php echo $text_cron_info; ?></h4>
    <div class="ov-cron-section">
      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_cron_url; ?>:</label>
        <input type="text" class="ov-form-control" value="<?php echo $url_cron; ?>" readonly>
      </div>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_cron_command; ?>:</label>
        <input type="text" class="ov-form-control" value="*/5 * * * * wget -q -O - <?php echo $url_cron; ?> > /dev/null 2>&1" readonly>
      </div>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_cron_interval; ?>:</label>
        <div class="ov-cron-info">
          <span class="ov-badge ov-badge-info">1-5 minute</span>
          <small class="ov-text-sm ov-text-muted"><?php echo $text_cron_interval_helper; ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="ov-callback-info">
    <?php echo $text_translation_callback; ?>:
    <a href="<?php echo $url_callback; ?>" target="_blank" class="ov-text-sm"><?php echo $url_callback; ?></a>
    <small class="ov-text-sm ov-text-info"><?php echo $text_translation_callback_helper; ?></small>
  </div>

  <br>
  <br>
  <br>
  <br>
  <br>
</div>

<!-- Modal Structure (hidden by default) -->
<div id="ovesioModal" class="ov-modal">
  <div class="ov-reset ov-modal-dialog">
    <div class="ov-card">
      <div class="ov-card-header ov-modal-header">
        <h4 class="ov-card-title ov-mb-0" id="modalTitle"><?php echo $text_edit_configuration; ?></h4>
        <button type="button" class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="ovesio.closeModal()">&times;</button>
      </div>
      <div class="ov-card-body" id="modalContent">
        <p>Modal content will be loaded here...</p>
      </div>
      <div class="ov-card-footer ov-modal-footer">
        <button type="button" class="ov-btn ov-btn-secondary" onclick="ovesio.closeModal()"><?php echo $button_cancel; ?></button>
        <button type="button" class="ov-btn ov-btn-primary ov-ml-2" id="btn_save_modal" onclick="ovesio.saveModal()"><?php echo $button_save_changes; ?></button>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo _MODULE_DIR_; ?>ovesio/views/js/ovesio.js"></script>