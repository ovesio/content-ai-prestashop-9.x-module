<div class="ov-card ov-card-fixed" id="generate_content_card" style="flex: 1">
  <div class="ov-card-body ov-card-body-relative">
    <h4 class="ov-card-title ov-text-xl"><?php echo $text_content_generator; ?></h4>
    <div class="ov-status-container">
      <?php if ($generate_content_status): ?>
      <span class="ov-badge ov-badge-success"><?php echo $text_enabled; ?></span>
      <?php else: ?>
      <span class="ov-badge ov-badge-danger"><?php echo $text_disabled; ?></span>
      <?php endif; ?>
    </div>

    <ul class="ov-bullet-list">
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-red" style="align-self: start; margin-top: 8px;"></span>
        <div>
          <?php if ($generate_content_for): ?>
            <?php echo $text_generating_content_for; ?>:<br>
            <?php foreach ($generate_content_for as $resource => $value): ?>
              <small class="ov-text-sm ov-text-muted">- <?php echo $generate_content_sumary[$resource]; ?></small><br>
            <?php endforeach; ?>
          <?php else: ?>
          <span class="ov-text-danger"><?php echo $text_no_resources_targeted; ?></span>
          <?php endif; ?>
        </div>
      </li>

      <?php if (!empty($generate_content_for['product'])): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-blue"></span>
        <?php if ($generate_content_include_stock_0): ?>
          Including out of stock products
        <?php else: ?>
          Excluding out of stock products
        <?php endif; ?>
      </li>
      <?php endif; ?>

      <?php if ($generate_content_for): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-green"></span>
        <?php if ($generate_content_live_update): ?>
          Live content generation on resource update
        <?php else: ?>
          One time content generation
        <?php endif; ?>
      </li>
      <?php endif; ?>

      <?php if ($generate_content_workflow): ?>
      <li class="ov-bullet-item">
        <span class="ov-bullet ov-bullet-orange"></span>
        <b>Workflow:</b>&nbsp;<?php echo $generate_content_workflow['name']; ?>
      </li>
      <?php endif; ?>
    </ul>

    <div class="ov-card-bottom">
      <div class="ov-feedback-container"></div>

      <div class="ov-edit-button">
        <a href="javascript:;" class="ov-btn ov-btn-sm ov-btn-outline-primary" onclick="ovesio.modalButton(event)" data-title="AI Content Generator Settings" data-url="<?php echo $url_edit; ?>">Edit</a>
      </div>
    </div>

  </div>
</div>
