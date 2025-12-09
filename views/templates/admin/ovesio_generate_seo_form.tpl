<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="generate_seo_form" onsubmit="ovesio.generateSeoFormSave(event)">
  <div class="ov-reset">
    <fieldset class="ov-fieldset">
      <!-- Status Switch -->
      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $entry_status; ?>:</label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="generate_seo_status" value="" type="hidden">
            <?php if ($generate_seo_status): ?>
            <input name="generate_seo_status" type="checkbox" id="meta_status" value="1" checked>
            <?php else: ?>
            <input name="generate_seo_status" type="checkbox" id="meta_status" value="1">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <!-- Only for Generated Content -->
      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_only_for_generated_content; ?>:</label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="generate_seo_only_for_action" value="" type="hidden">
            <?php if ($generate_seo_only_for_action): ?>
            <input name="generate_seo_only_for_action" type="checkbox" id="meta_only_for_action" value="1" checked>
            <?php else: ?>
            <input name="generate_seo_only_for_action" type="checkbox" id="meta_only_for_action" value="1">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <!-- Generate on Resource Update -->
      <div class="ov-form-group">
        <label class="ov-form-label">
          <?php echo $text_generate_seo_live_update; ?>:<br>
          <small class="ov-text-sm ov-helper-text"><?php echo $text_generate_seo_live_update_helper; ?></small>
        </label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="generate_seo_live_update" value="" type="hidden">
            <?php if ($generate_seo_live_update): ?>
            <input name="generate_seo_live_update" type="checkbox" id="meta_regenerate_on_update" checked>
            <?php else: ?>
            <input name="generate_seo_live_update" type="checkbox" id="meta_regenerate_on_update">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <!-- Workflow -->
      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_workflow_used; ?>:</label>
        <div class="ov-form-field">
          <select name="generate_seo_workflow" class="ov-form-control" id="meta_workflow_type">
            <?php foreach ($workflows_list as $workflow): ?>
            <?php if ( $generate_seo_workflow && $workflow['id'] == $generate_seo_workflow['id']): ?>
            <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>" selected><?php echo $workflow['name']; ?></option>
            <?php else: ?>
            <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>"><?php echo $workflow['name']; ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </fieldset>

    <div class="ov-mb-3 ov-text-muted"><u><?php echo $text_generate_seo_for; ?></u></div>

    <fieldset class="ov-fieldset">
      <legend class="ov-d-flex" style="gap: 10px;">
        <?php echo $text_products; ?>

        <label class="ov-form-switch">
          <input name="generate_seo_for[products]" value="" type="hidden">
          <?php if (!empty($generate_seo_for['products'])): ?>
          <input name="generate_seo_for[products]" type="checkbox" value="1" checked>
          <?php else: ?>
          <input name="generate_seo_for[products]" type="checkbox" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </legend>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo sprintf($text_include_disabled_resources, strtolower($text_products)); ?></label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="generate_seo_include_disabled[products]" value="" type="hidden">
            <?php if (!empty($generate_seo_include_disabled['products'])): ?>
            <input name="generate_seo_include_disabled[products]" value="1" type="checkbox" checked>
            <?php else: ?>
            <input name="generate_seo_include_disabled[products]" value="1" type="checkbox">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_include_stock_0; ?></label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="generate_seo_include_stock_0" value="" type="hidden">
            <?php if ($generate_seo_include_stock_0): ?>
            <input name="generate_seo_include_stock_0" value="1" type="checkbox" id="meta_include_out_of_stock" checked>
            <?php else: ?>
            <input name="generate_seo_include_stock_0" value="1" type="checkbox" id="meta_include_out_of_stock">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>
    </fieldset>

    <fieldset class="ov-fieldset">
      <legend class="ov-d-flex" style="gap: 10px;">
        <?php echo $text_categories; ?>

        <label class="ov-form-switch">
          <input name="generate_seo_for[categories]" value="" type="hidden">
          <?php if (!empty($generate_seo_for['categories'])): ?>
          <input name="generate_seo_for[categories]" type="checkbox" value="1" checked>
          <?php else: ?>
          <input name="generate_seo_for[categories]" type="checkbox" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </legend>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo sprintf($text_include_disabled_resources, strtolower($text_categories)); ?></label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="generate_seo_include_disabled[categories]" value="" type="hidden">
            <?php if (!empty($generate_seo_include_disabled['categories'])): ?>
            <input name="generate_seo_include_disabled[categories]" value="1" type="checkbox" checked>
            <?php else: ?>
            <input name="generate_seo_include_disabled[categories]" value="1" type="checkbox">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>
    </fieldset>
  </div>
</form>