<!-- Connection Card Container -->
<div class="ov-connection-container <?php echo $connected ? 'ov-hidden' : ''; ?>" id="connection_card">
  <div class="ov-card ov-w-full ov-max-w-sm">
    <div class="ov-card-header">
      <h3 class="ov-card-title ov-text-xl ov-mb-0"><?php echo $text_connect; ?></h3>
    </div>
    <div class="ov-card-body">
      <form action="<?php echo $url_connect; ?>" method="post" enctype="multipart/form-data" id="connection_form" onsubmit="ovesio.connectAPI(event)">
        <div class="ov-text-center">
          <a href="https://ovesio.com/account/login" target="_blank">
            <img src="<?php echo _MODULE_DIR_; ?>ovesio/views/img/logo-ovesio-sm.png" style="max-width: 100%; margin-bottom: 20px;" alt="Ovesio Logo">
          </a>
        </div>
        <small class="ov-text-sm ov-helper-text"><?php echo $text_api_token_helper; ?></small>

        <div class="ov-form-group">
          <label class="ov-form-label ov-required"><?php echo $text_api_url; ?>:</label>
          <div class="ov-form-field">
            <input name="api_url" value="<?php echo $api_url; ?>" type="text" class="ov-form-control" placeholder="Introduceți token-ul API" id="api_url">
          </div>
        </div>

        <div class="ov-form-group">
          <label class="ov-form-label ov-required"><?php echo $text_api_token; ?>:</label>
          <div class="ov-form-field">
            <input name="api_token" value="<?php echo $api_token; ?>" type="password" class="ov-form-control" placeholder="Introduceți token-ul API" id="api_token">
          </div>
        </div>

        <div class="ov-form-group ov-hidden" id="default_language_group">
          <label class="ov-form-label"><?php echo $text_default_language; ?>:</label>
          <div class="ov-form-field">
            <div class="ov-field-wrapper">
              <select name="default_language" class="ov-form-control" id="default_language" disabled value="<?php echo $default_language; ?>"></select>
            </div>
            <small class="ov-text-sm ov-helper-text"><?php echo $text_default_language_helper; ?></small>
          </div>
        </div>

        <div class="ov-button-group">
          <button type="submit" class="ov-btn ov-btn-primary"><?php echo $button_connect; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
