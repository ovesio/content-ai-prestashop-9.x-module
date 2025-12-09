<link rel="stylesheet" type="text/css" href="<?php echo _MODULE_DIR_; ?>ovesio/views/css/ovesio.css" />
<div class="ov-reset">

  <!-- Activity Log Header -->
  <div class="ov-activity-header">
    <h1 class="ov-activity-title">
      <svg class="ov-icon ov-icon-lg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 0.5em;">
        <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <?php echo $text_activity_list; ?>
    </h1>

    <a href="<?php echo $url_settings; ?>" class="ov-btn ov-btn-outline-secondary">
      <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="2"/>
      </svg>
      <?php echo $text_go_to_settings; ?>
    </a>
  </div>

  <!-- Enhanced Filters Bar -->
  <div class="ov-well ov-filters" style="margin-bottom: 1.5em;">
    <!-- First Row - 3 Columns -->
    <div class="ov-row" style="margin-bottom: 1em;">
      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_resource_type; ?>:</label>
          <select class="ov-form-control" id="resourceTypeFilter">
            <option value=""><?php echo $text_all_types; ?></option>
            <?php foreach ($resource_types as $code => $option): ?>
              <?php if ($code == $resource_type): ?>
              <option value="<?php echo $code; ?>" SELECTED><?php echo $option['text']; ?></option>
              <?php else: ?>
              <option value="<?php echo $code; ?>"><?php echo $option['text']; ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_resource_id; ?>:</label>
          <input type="text" class="ov-form-control" id="resourceIdFilter" placeholder="<?php echo $text_search_by_id; ?>..." value="<?php echo $resource_id; ?>">
        </div>
      </div>

      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_status; ?>:</label>
          <select class="ov-form-control" id="statusFilter">
            <option value=""><?php echo $text_all_status; ?></option>
            <?php foreach ($status_types as $code => $status_type): ?>
              <?php if ($code == $status): ?>
              <option value="<?php echo $code; ?>" SELECTED><?php echo $status_type['text']; ?></option>
              <?php else: ?>
              <option value="<?php echo $code; ?>"><?php echo $status_type['text']; ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="ov-row" style="margin-bottom: 1em;">
      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_language; ?>:</label>
          <select class="ov-form-control" id="languageFilter">
            <option value=""><?php echo $text_all_languages; ?></option>
            <?php foreach ($language_options as $code => $name): ?>
              <?php if ($code == $language): ?>
              <option value="<?php echo $code; ?>" SELECTED><?php echo $name; ?></option>
              <?php else: ?>
              <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_activity_type; ?>:</label>
          <select class="ov-form-control" id="activityTypeFilter">
            <option value=""><?php echo $text_all_activities; ?></option>
            <?php foreach ($activity_types as $code => $option): ?>
              <?php if ($code == $activity_type): ?>
              <option value="<?php echo $code; ?>" SELECTED><?php echo $option['text']; ?></option>
              <?php else: ?>
              <option value="<?php echo $code; ?>"><?php echo $option['text']; ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_resource_name; ?>:</label>
          <input type="text" class="ov-form-control" id="resourceNameFilter" placeholder="<?php echo $text_search_by_name; ?>..." value="<?php echo $resource_name; ?>">
        </div>
      </div>
    </div>

    <!-- Second Row - 3 Columns -->
    <div class="ov-row" style="margin-bottom: 1em;">
      <div class="ov-col-md-4">
        <div class="ov-form-group">
          <label class="ov-form-label"><?php echo $text_date_range; ?>:</label>
          <select class="ov-form-control" id="dateRangeFilter">
            <option value=""><?php echo $text_all_time; ?></option>
            <option value="today" <?php echo $date == 'today' ? 'SELECTED' : ''; ?>><?php echo $text_today; ?></option>
            <option value="yesterday" <?php echo $date == 'yesterday' ? 'SELECTED' : ''; ?>><?php echo $text_yesterday; ?></option>
            <option value="last7days" <?php echo $date == 'last7days' ? 'SELECTED' : ''; ?>><?php echo $text_last_7_days; ?></option>
            <option value="last30days" <?php echo $date == 'last30days' ? 'SELECTED' : ''; ?>><?php echo $text_last_30_days; ?></option>
            <option value="thismonth" <?php echo $date == 'thismonth' ? 'SELECTED' : ''; ?>><?php echo $text_this_month; ?></option>
            <option value="lastmonth" <?php echo $date == 'lastmonth' ? 'SELECTED' : ''; ?>><?php echo $text_last_month; ?></option>
            <option value="custom" <?php echo $date == 'custom' ? 'SELECTED' : ''; ?>><?php echo $text_custom; ?></option>
          </select>
        </div>

        <!-- Custom Date Range (hidden by default) -->
        <div id="customDateRange" class="ov-row" style="<?php echo $date == 'custom' ? : 'display: none;'; ?> margin-bottom: 1em;">
          <div class="ov-col-6">
            <div class="ov-form-group">
              <label class="ov-form-label"><?php echo $text_date_from; ?>:</label>
              <input type="date" class="ov-form-control" id="dateFrom" value="<?php echo $date_from; ?>">
            </div>
          </div>
          <div class="ov-col-6">
            <div class="ov-form-group">
              <label class="ov-form-label"><?php echo $text_date_to; ?>:</label>
              <input type="date" class="ov-form-control" id="dateTo" value="<?php echo $date_to; ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons Row -->
    <div class="ov-row">
      <div class="ov-col-12">
        <div class="ov-d-flex ov-justify-end ov-gap-2">
          <button type="button" class="ov-btn ov-btn-outline-secondary" onclick="clearAllFilters()">
            <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php echo $text_clear_filters; ?>
          </button>
          <button type="button" class="ov-btn ov-btn-primary" onclick="applyFilters()">
            <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22 3H2L10 12.46V19L14 21V12.46L22 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php echo $text_apply_filters; ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Activity Log Table -->
  <div class="ov-card">
    <div class="ov-card-body" style="padding: 0; overflow-x: auto;">
      <table class="ov-table" style="margin-bottom: 0; min-width: 600px;">
        <thead class="ov-table-header">
          <tr>
            <th width="100px"><?php echo $text_resource_type; ?></th>
            <th><?php echo $text_resource_id; ?></th>
            <th width="100px" class="ov-text-center"><?php echo $text_activity_type; ?></th>
            <th width="5%" class="ov-text-center"><?php echo $text_language; ?></th>
            <th width="5%" style="min-width:120px" class="ov-text-center"><?php echo $text_status; ?></th>
            <th width="20%"><?php echo $text_message; ?></th>
            <th width="100px"><?php echo $text_last_updated; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $activity): ?>
              <tr>
                <td>
                  <span class="ov-badge <?php echo $activity['resource_display']['class']; ?>"><?php echo $activity['resource_display']['text']; ?></span>
                </td>
                <td>
                  <a href="<?php echo $activity['resource_url'] ?: 'javascript:;'; ?>" target="_blank" class="ov-resource-link">
                    #<?php echo $activity['resource_id']; ?> - <small class="ov-text-muted"><?php echo $activity['resource_name_escaped']; ?></small>
                  </a>
                </td>
                <td class="ov-text-center">
                  <span class="ov-badge <?php echo $activity['activity_display']['class']; ?>"><?php echo $activity['activity_display']['text']; ?></span>
                </td>
                <td class="ov-text-center">
                  <div class="ov-d-flex ov-align-center">
                    <?php if ($activity['language_flag']) { ?>
                      <img src="<?php echo $activity['language_flag']; ?>" alt="<?php echo $activity['language_name']; ?>" title="<?php echo $activity['language_name']; ?>" style="width: 16px; margin-right: 0.4em" />
                    <?php } ?>
                    <span>
                      <?php echo $activity['lang_upper']; ?>
                    </span>
                  </div>
                </td>
                <td class="ov-text-center">
                  <span class="ov-status-badge <?php echo $activity['status_display']['class']; ?>"><?php echo $activity['status_display']['text']; ?></span>
                  <div class="ov-status-actions">
                    <button type="button" class="ov-mini-btn ov-tooltip" data-tooltip="<?php echo $text_tooltip_view_request; ?>" onclick="ovesio.modalButton(event)" data-title="<?php echo $text_request; ?>" data-url="<?php echo $url_view_request . '&activity_id=' . $activity['id']; ?>">
                      <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 12S5 4 12 4S23 12 23 12S19 20 12 20S1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                      </svg>
                    </button>

                    <button type="button" id="buttonStatus<?php echo $activity['id']; ?>" class="ov-mini-btn ov-tooltip <?php echo $activity['status'] != 'error' ? 'ov-show' : 'ov-hidden'; ?>" data-tooltip="<?php echo $text_tooltip_update_status; ?>" onclick="ovesio.updateActivityStatus(`<?php echo $activity['id']; ?>`)">
                      <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4V10H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 20V14H17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10M23 14L18.36 18.36A9 9 0 0 1 3.51 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <button type="button" id="buttonResponse<?php echo $activity['id']; ?>" class="ov-mini-btn ov-tooltip <?php echo $activity['status'] == 'completed' ? 'ov-show' : 'ov-hidden'; ?>" data-tooltip="<?php echo $text_tooltip_view_response; ?>" onclick="ovesio.modalButton(event)" data-title="<?php echo $text_response; ?>" data-url="<?php echo $url_view_response . '&activity_id=' . $activity['id']; ?>">
                      <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <?php if (!empty($activity['activity_url'])): ?>
                    <a href="<?php echo $activity['activity_url']; ?>" target="_blank" class="ov-mini-btn ov-tooltip" data-tooltip="<?php echo $text_tooltip_view_in_app; ?>">
                      <svg class="ov-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 13V19C18 19.5304 17.7893 20.0391 17.4142 20.4142C17.0391 20.7893 16.5304 21 16 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V8C3 7.46957 3.21071 6.96086 3.58579 6.58579C3.96086 6.21071 4.46957 6 5 6H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 3H21V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 14L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </a>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <small class="ov-text-muted"><?php echo $activity['message']; ?></small>
                </td>
                <td>
                  <small class="ov-text-muted"><?php echo $activity['time_ago']; ?></small>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2em; color: #6c757d;">
                <p><?php echo $text_no_results_found; ?></p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($total > 0) { ?>
  <div class="ov-pagination">
    <div style="display: flex; gap: 0.25em;">
      <?php
        $total_pages = ceil($total / $limit);
        $pagination_range = 2; // Show 2 pages before and after current page
        $start_page = max(1, $page - $pagination_range);
        $end_page = min($total_pages, $page + $pagination_range);
      ?>

      <!-- Previous Button -->
      <?php if ($page > 1) { ?>
        <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="changePage('<?php echo $page - 1; ?>')"><?php echo $text_previous; ?></button>
      <?php } else { ?>
        <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" disabled><?php echo $text_previous; ?></button>
      <?php } ?>

      <!-- First page if not in range -->
      <?php if ($start_page > 1) { ?>
        <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="changePage(1)">1</button>
        <?php if ($start_page > 2) { ?>
          <span style="padding: 0.375em 0.5em; color: #6c757d;">...</span>
        <?php } ?>
      <?php } ?>

      <!-- Page numbers -->
      <?php for ($i = $start_page; $i <= $end_page; $i++) { ?>
        <?php if ($i == $page) { ?>
          <button class="ov-btn ov-btn-primary ov-btn-sm"><?php echo $i; ?></button>
        <?php } else { ?>
          <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="changePage('<?php echo $i; ?>')"><?php echo $i; ?></button>
        <?php } ?>
      <?php } ?>

      <!-- Last page if not in range -->
      <?php if ($end_page < $total_pages) { ?>
        <?php if ($end_page < $total_pages - 1) { ?>
          <span style="padding: 0.375em 0.5em; color: #6c757d;">...</span>
        <?php } ?>
        <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="changePage('<?php echo $total_pages; ?>')"><?php echo $total_pages; ?></button>
      <?php } ?>

      <!-- Next Button -->
      <?php if ($page < $total_pages) { ?>
        <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="changePage('<?php echo $page + 1; ?>')"><?php echo $text_next; ?></button>
      <?php } else { ?>
        <button class="ov-btn ov-btn-outline-secondary ov-btn-sm" disabled><?php echo $text_next; ?></button>
      <?php } ?>
    </div>
    <div>
      <?php
        $start = (($page - 1) * $limit) + 1;
        $end = min($page * $limit, $total);
      ?>
      <small class="ov-text-muted"><?php echo $text_showing_entries; ?> <?php echo $start; ?>-<?php echo $end; ?> <?php echo $text_of; ?> <?php echo $total; ?> <?php echo $text_entries; ?></small>
    </div>
  </div>
  <?php } ?>

</div>

<!-- Modal Structure (hidden by default) -->
<div id="ovesioModal" class="ov-modal">
  <div class="ov-reset ov-modal-dialog">
    <div class="ov-card">
      <div class="ov-card-header ov-modal-header">
        <h4 class="ov-card-title ov-mb-0" id="modalTitle">Activity Details</h4>
        <button type="button" class="ov-btn ov-btn-outline-secondary ov-btn-sm" onclick="ovesio.closeModal()">&times;</button>
      </div>
      <div class="ov-card-body" id="modalContent">
        <p>Loading...</p>
      </div>
      <div class="ov-card-footer ov-modal-footer">
        <button type="button" class="ov-btn ov-btn-secondary" onclick="ovesio.closeModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
window.url_update_status = '<?php echo $url_update_status; ?>';

// Enhanced Filter Functions
function applyFilters() {
  // Get current URL and update page parameter
  const url = new URL(window.location);

  // Apply current filters to maintain state
  const filters = getFilters();

  // Add filters to URL
  Object.keys(filters).forEach(key => {
    url.searchParams.set(key, filters[key]);
  });

  url.searchParams.set('page', '1'); // Reset to first page on filter apply

  // Navigate to new page
  window.location.href = url.toString();
}

function clearAllFilters() {
  // Reset all form controls
  document.getElementById('resourceTypeFilter').value = '';
  document.getElementById('statusFilter').value       = '';
  document.getElementById('activityTypeFilter').value = '';
  document.getElementById('dateRangeFilter').value    = '';
  document.getElementById('languageFilter').value    = '';
  document.getElementById('dateFrom').value           = '';
  document.getElementById('dateTo').value             = '';
  document.getElementById('resourceIdFilter').value   = '';
  document.getElementById('resourceNameFilter').value = '';

  // Hide custom date range
  document.getElementById('customDateRange').style.display = 'none';

  // Auto-apply filters after clearing
  setTimeout(applyFilters, 100);
}

function getFilters() {
  const filters = {
    resource_name: document.getElementById('resourceNameFilter').value,
    resource_type: document.getElementById('resourceTypeFilter').value,
    resource_id  : document.getElementById('resourceIdFilter').value,
    status       : document.getElementById('statusFilter').value,
    activity_type: document.getElementById('activityTypeFilter').value,
    date         : document.getElementById('dateRangeFilter').value,
    language     : document.getElementById('languageFilter').value,
    date_from    : document.getElementById('dateFrom').value,
    date_to      : document.getElementById('dateTo').value,
  };

  return filters;
}

function changePage(newPage) {
  // Get current URL and update page parameter
  const url = new URL(window.location);
  url.searchParams.set('page', newPage);

  // Apply current filters to maintain state
  const filters = getFilters();

  // Add filters to URL
  Object.keys(filters).forEach(key => {
    if (filters[key]) {
      url.searchParams.set(key, filters[key]);
    }
  });

  // Navigate to new page
  window.location.href = url.toString();
}

// Date range filter functionality
document.getElementById('dateRangeFilter').addEventListener('change', function() {
  const customDateRange = document.getElementById('customDateRange');
  if (this.value === 'custom') {
    customDateRange.style.display = 'flex';
  } else {
    customDateRange.style.display = 'none';
  }
});

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  // Set default date for custom range to today
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('dateFrom').value = today;
  document.getElementById('dateTo').value = today;
});
</script>

<script src="<?php echo _MODULE_DIR_; ?>ovesio/views/js/ovesio.js"></script>