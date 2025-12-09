<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="translate_form" onsubmit="ovesio.translateFormSave(event)">
  <div class="ov-reset">
    <fieldset class="ov-fieldset">
      <!-- Status Switch -->
      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $entry_status; ?>:</label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="translate_status" value="" type="hidden">
            <?php if ($translate_status): ?>
            <input name="translate_status" type="checkbox" id="translation_status" value="1" checked>
            <?php else: ?>
            <input name="translate_status" type="checkbox" id="translation_status" value="1">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <!-- Workflow -->
      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_workflow_used; ?></label>
        <div class="ov-form-field">
          <select name="translate_workflow" class="ov-form-control">
            <?php foreach ($workflows_list as $workflow): ?>
            <?php if ($translate_workflow && $workflow['id'] == $translate_workflow['id']): ?>
            <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>" selected><?php echo $workflow['name']; ?></option>
            <?php else: ?>
            <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>"><?php echo $workflow['name']; ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </fieldset>

    <!-- Products -->
    <fieldset class="ov-fieldset">
      <legend class="ov-d-flex" style="gap: 10px;">
        <?php echo $text_products; ?>

        <label class="ov-form-switch">
          <input name="translate_for[products]" value="" type="hidden">
          <?php if (!empty($translate_for['products'])): ?>
          <input name="translate_for[products]" type="checkbox" value="1" checked>
          <?php else: ?>
          <input name="translate_for[products]" type="checkbox" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </legend>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo sprintf($text_include_disabled_resources, strtolower($text_products)); ?></label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="translate_include_disabled[products]" value="" type="hidden">
            <?php if (!empty($translate_include_disabled['products'])): ?>
            <input name="translate_include_disabled[products]" value="1" type="checkbox" checked>
            <?php else: ?>
            <input name="translate_include_disabled[products]" value="1" type="checkbox">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo $text_include_stock_0; ?></label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="translate_include_stock_0" value="" type="hidden">
            <?php if ($translate_include_stock_0): ?>
            <input name="translate_include_stock_0" value="1" type="checkbox" id="translation_include_out_of_stock" checked>
            <?php else: ?>
            <input name="translate_include_stock_0" value="1" type="checkbox" id="translation_include_out_of_stock">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <div class="ov-form-group ov-form-group-vertical">
        <label class="ov-form-label"><?php echo $text_translated_fields; ?>:</label>
        <div class="ov-form-field">
          <div class="ov-well">
            <div style="display: flex; flex-direction: column; gap: 0.5em;">
              <?php foreach ($translate_fields_schema['products'] as $field_key => $field_label): ?>
              <label class="ov-checkbox-label">
                <input name="translate_fields[products][<?php echo $field_key; ?>]" value="" type="hidden">
                <?php if (!empty($translate_fields['products'][$field_key])): ?>
                <input name="translate_fields[products][<?php echo $field_key; ?>]" value="1" type="checkbox" checked>
                <?php else: ?>
                <input name="translate_fields[products][<?php echo $field_key; ?>]" value="1" type="checkbox">
                <?php endif; ?>
                <span><?php echo $field_label; ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </fieldset>

    <!-- Categories -->
    <fieldset class="ov-fieldset">
      <legend class="ov-d-flex" style="gap: 10px;">
        <?php echo $text_categories; ?>

        <label class="ov-form-switch">
          <input name="translate_for[categories]" value="" type="hidden">
          <?php if (!empty($translate_for['categories'])): ?>
          <input name="translate_for[categories]" type="checkbox" value="1" checked>
          <?php else: ?>
          <input name="translate_for[categories]" type="checkbox" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </legend>

      <div class="ov-form-group">
        <label class="ov-form-label"><?php echo sprintf($text_include_disabled_resources, strtolower($text_categories)); ?></label>
        <div style="display: flex; align-items: center; gap: 0.5em;">
          <label class="ov-form-switch">
            <input name="translate_include_disabled[categories]" value="" type="hidden">
            <?php if (!empty($translate_include_disabled['categories'])): ?>
            <input name="translate_include_disabled[categories]" value="1" type="checkbox" checked>
            <?php else: ?>
            <input name="translate_include_disabled[categories]" value="1" type="checkbox">
            <?php endif; ?>
            <span class="ov-switch-slider"></span>
          </label>
        </div>
      </div>

      <div class="ov-form-group ov-form-group-vertical">
        <label class="ov-form-label"><?php echo $text_translated_fields; ?>:</label>
        <div class="ov-form-field">
          <div class="ov-well">
            <div style="display: flex; flex-direction: column; gap: 0.5em;">
              <?php foreach ($translate_fields_schema['categories'] as $field_key => $field_label): ?>
              <label class="ov-checkbox-label">
                <input name="translate_fields[categories][<?php echo $field_key; ?>]" value="" type="hidden">
                <?php if (!empty($translate_fields['categories'][$field_key])): ?>
                <input name="translate_fields[categories][<?php echo $field_key; ?>]" value="1" type="checkbox" checked>
                <?php else: ?>
                <input name="translate_fields[categories][<?php echo $field_key; ?>]" value="1" type="checkbox">
                <?php endif; ?>
                <span><?php echo $field_label; ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </fieldset>

    <!-- Attributes -->
    <fieldset class="ov-fieldset">
      <legend class="ov-d-flex" style="gap: 10px;">
        <?php echo $text_attributes; ?>

        <label class="ov-form-switch">
          <input name="translate_for[attributes]" value="" type="hidden">
          <?php if (!empty($translate_for['attributes'])): ?>
          <input name="translate_for[attributes]" type="checkbox" value="1" checked>
          <?php else: ?>
          <input name="translate_for[attributes]" type="checkbox" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </legend>
    </fieldset>

    <!-- Attributes -->
    <fieldset class="ov-fieldset">
      <legend class="ov-d-flex" style="gap: 10px;">
        <?php echo $text_features; ?>

        <label class="ov-form-switch">
          <input name="translate_for[features]" value="" type="hidden">
          <?php if (!empty($translate_for['features'])): ?>
          <input name="translate_for[features]" type="checkbox" value="1" checked>
          <?php else: ?>
          <input name="translate_for[features]" type="checkbox" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </legend>
    </fieldset>

    <!-- Language Translation Table -->
    <fieldset class="ov-fieldset">
      <legend><?php echo $text_language_translation; ?></legend>
      <table class="ov-table">
          <thead class="ov-table-header">
            <tr>
              <th><?php echo $text_system_language; ?></th>
              <th><?php echo $text_ovesio_language; ?></th>
              <th><?php echo $text_status; ?></th>
              <th><?php echo $text_translate_from; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($system_languages as $language_id => $language): ?>
            <tr>
              <td><?php echo $language['name']; ?></td>
              <td>
                <select name="language_settings[<?php echo $language_id; ?>][code]" class="ov-form-control">
                  <option disabled><?php echo $text_please_select_matching_language; ?></option>
                  <?php foreach ($ovesio_languages as $code => $ov_lang): ?>
                  <?php if ($language_settings[$language_id]['code'] == $code): ?>
                  <option value="<?php echo $code; ?>" selected><?php echo $ov_lang['name']; ?></option>
                  <?php else: ?>
                  <option value="<?php echo $code; ?>"><?php echo $ov_lang['name']; ?></option>
                  <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <label class="ov-form-switch" style="margin-bottom: 0;">
                  <input name="language_settings[<?php echo $language_id; ?>][translate]" value="" type="hidden">
                  <?php if (!empty($language_settings[$language_id]['translate'])): ?>
                  <input name="language_settings[<?php echo $language_id; ?>][translate]" value="1" type="checkbox" checked>
                  <?php else: ?>
                  <input name="language_settings[<?php echo $language_id; ?>][translate]" value="1" type="checkbox">
                  <?php endif; ?>
                  <span class="ov-switch-slider"></span>
                </label>
              </td>
              <td>
                <select name="language_settings[<?php echo $language_id; ?>][translate_from]" class="ov-form-control">
                  <option value="" disabled><?php echo $text_select_source_language; ?></option>
                  <option value="auto">Auto</option>
                  <?php foreach ($ovesio_languages as $code => $ov_lang): ?>
                  <?php if ($language_settings[$language_id]['translate_from'] == $code): ?>
                  <option value="<?php echo $code; ?>" selected><?php echo $ov_lang['name']; ?></option>
                  <?php else: ?>
                  <option value="<?php echo $code; ?>"><?php echo $ov_lang['name']; ?></option>
                  <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </fieldset>
  </div>
</form>