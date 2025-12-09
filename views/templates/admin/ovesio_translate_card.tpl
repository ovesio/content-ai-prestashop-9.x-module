<div class="ov-card ov-card-fixed" id="translate_card" style="flex: 1">
  <div class="ov-card-body ov-card-body-relative">
    <h4 class="ov-card-title ov-text-xl"><?php echo $text_translate; ?></h4>
    <div class="ov-status-container">
      <?php if ($translate_status): ?>
      <span class="ov-badge ov-badge-success"><?php echo $text_enabled; ?></span>
      <?php else: ?>
      <span class="ov-badge ov-badge-danger"><?php echo $text_disabled; ?></span>
      <?php endif; ?>
    </div>

    <ul class="ov-bullet-list">
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-red" style="align-self: start; margin-top: 8px;"></span>
        <div>
          <?php if ($translate_for): ?>
            <?php echo $text_translating; ?>:<br>
            <?php foreach ($translate_for as $resource => $value): ?>
              <small class="ov-text-sm ov-text-muted">- <?php echo $translate_sumary[$resource]; ?></small><br>
            <?php endforeach; ?>
          <?php else: ?>
          <span class="ov-text-danger"><?php echo $text_no_resources_targeted; ?></span>
          <?php endif; ?>
        </div>
      </li>

      <?php if (!empty($translate_for['product'])): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-blue"></span>
        <?php if ($translate_include_stock_0): ?>
          Including out of stock products
        <?php else: ?>
          Excluding out of stock products
        <?php endif; ?>
      </li>
      <?php endif; ?>

      <?php if ($translate_for): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-green"></span>
        <?php if ($translate_live_update): ?>
          Live translate on resource update
        <?php else: ?>
          One time translate
        <?php endif; ?>
      </li>
      <?php endif; ?>

      <?php if ($translate_workflow): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-orange"></span>
        <b>Workflow:</b>&nbsp;<?php echo $translate_workflow['name']; ?>
      </li>
      <?php endif; ?>

      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-cyan" style="align-self: start; margin-top: 8px;"></span>
        <div>
          <b>Languages:</b><br>
          <div class="ov-d-flex ov-flex-wrap ov-gap-2">
            <?php $count = 0; ?>
            <?php foreach ($language_settings as $lang): ?>
              <?php if ($lang['translate']): ?>
                <?php $count++; ?>
                <img src="<?php echo $lang['flag']; ?>" width="16px" height="auto" title="<?php echo $lang['name']; ?>" onerror="this.title=' '">
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($count == 0): ?>
              <span class="ov-text-danger"><?php echo $text_warning_no_language; ?></span>
            <?php endif; ?>
          </div>
        </div>
      </li>
    </ul>

    <div class="ov-card-bottom">
      <div class="ov-feedback-container"></div>

      <div class="ov-edit-button">
        <a href="javascript:;" class="ov-btn ov-btn-sm ov-btn-outline-primary" onclick="ovesio.modalButton(event)" data-title="Translate Settings" data-url="<?php echo $url_edit; ?>">Edit</a>
      </div>

    </div>

  </div>
</div>
