<div class="ov-card ov-card-fixed" id="generate_seo_card" style="flex: 1">
  <div class="ov-card-body ov-card-body-relative">
    <h4 class="ov-card-title ov-text-xl"><?php echo $text_seo_generator; ?></h4>
    <div class="ov-status-container">
      <?php if ($generate_seo_status): ?>
      <span class="ov-badge ov-badge-success"><?php echo $text_enabled; ?></span>
      <?php else: ?>
      <span class="ov-badge ov-badge-danger"><?php echo $text_disabled; ?></span>
      <?php endif; ?>
    </div>

    <ul class="ov-bullet-list">
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-red" style="align-self: start; margin-top: 8px;"></span>
        <div>
          <?php if ($generate_seo_for): ?>
            <?php echo $text_generating_seo_for; ?>:<br>
            <?php foreach ($generate_seo_for as $resource => $value): ?>
              <small class="ov-text-sm ov-text-muted">- <?php echo $generate_seo_sumary[$resource]; ?></small><br>
            <?php endforeach; ?>
          <?php else: ?>
          <span class="ov-text-danger"><?php echo $text_no_resources_targeted; ?></span>
          <?php endif; ?>
        </div>
      </li>

      <?php if (!empty($generate_seo_for['product'])): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-blue"></span>
        <?php if ($generate_seo_include_stock_0): ?>
          Including out of stock products
        <?php else: ?>
          Excluding out of stock products
        <?php endif; ?>
      </li>
      <?php endif; ?>

      <?php if ($generate_seo_for): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-green"></span>
        <?php if ($generate_seo_live_update): ?>
          Live seo generation on resource update
        <?php else: ?>
          One time seo generation
        <?php endif; ?>
      </li>
      <?php endif; ?>

      <?php if ($generate_seo_workflow): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-orange"></span>
        <b>Workflow:</b>&nbsp;<?php echo $generate_seo_workflow['name']; ?>
      </li>
      <?php endif; ?>
    </ul>

    <div class="ov-card-bottom">
      <div class="ov-feedback-container"></div>

      <div class="ov-edit-button">
        <a href="javascript:;" class="ov-btn ov-btn-sm ov-btn-outline-primary" onclick="ovesio.modalButton(event)" data-title="AI Seo Generator Settings" data-url="<?php echo $url_edit; ?>">Edit</a>
      </div>
    </div>

  </div>
</div>
