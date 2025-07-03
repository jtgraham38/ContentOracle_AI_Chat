<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//load the current value of the filters setting
$filters_setting = get_option($this->prefixed('filters'), array());

//get available post types for filtering
$post_types = get_post_types(array(), 'objects');
$exclude = array(
    'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 
    'oembed_cache', 'user_request', 'wp_block', 'acf-field-group', 'acf-field', 
    'wp_font_family', 'wp_font_face', 'wp_global_styles', 'coai_chat_shortcode'
);
foreach ($exclude as $ex){
    unset($post_types[$ex]);
}

//available operators
$operators = array(
    '=' => 'Equals',
    '!=' => 'Not Equals',
    '>' => 'Greater Than',
    '>=' => 'Greater Than or Equal',
    '<' => 'Less Than',
    '<=' => 'Less Than or Equal',
    'LIKE' => 'Contains',
    'NOT LIKE' => 'Does Not Contain',
    'IN' => 'In List',
    'NOT IN' => 'Not In List'
);

//available fields
$fields = array(
    'post_author' => ['label' => 'Post Author', 'type' => 'number'],
    'post_date' => ['label' => 'Post Date', 'type' => 'date'],
    'post_modified' => ['label' => 'Post Modified Date', 'type' => 'date'],
    'comment_count' => ['label' => 'Comment Count', 'type' => 'number'],
    'meta' => ['label' => 'Custom Field (Meta)', 'type' => 'text']
);

//echo the filters setting
echo '<pre>';
print_r($filters_setting);
echo '</pre>';

?>

<ul>
    <hr>
    <li>I need to account for the type of the data I am passing for the compare value, for example, for price, it is passed as a string by default, but i need it passed as a number.  or for date comparison</li>
    <li>Account for passing in useful constants, like today's date, or the current user's ID</li>
    <li>Account for logic for formatting for operators like In, Not In, etc.</li>
    <li>apply filters to the keyword search implementation too!</li>
    <hr>
</ul>

<div id="<?php $this->pre('filters_input') ?>">
    <p class="description">
        Configure filters to refine AI search results. Filters within each group are combined with OR, while groups are combined with AND.
    </p>
    
    <div id="filter-groups">
        <?php if (empty($filters_setting)): ?>
            <div class="filter-group" data-group-index="0">
                <h4>Filter Group 1</h4>
                <div class="filter-group-filters">
                    <div class="filter-row" data-filter-index="0">
                        <select name="<?php $this->pre('filters[0][0][field_name]') ?>" class="filter-field">
                            <option value="">Select Field</option>
                            <?php foreach ($fields as $field_key => $field_info): ?>
                                <option value="<?php echo esc_attr($field_key); ?>" data-type="<?php echo esc_attr($field_info['type']); ?>"><?php echo esc_html($field_info['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="<?php $this->pre('filters[0][0][meta_key]') ?>" class="filter-meta-key" placeholder="Meta Key" style="display: none;">
                        <select class="meta-type-select" style="display: none;">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                        </select>
                        
                        <select name="<?php $this->pre('filters[0][0][operator]') ?>" class="filter-operator">
                            <option value="">Select Operator</option>
                            <?php foreach ($operators as $op_key => $op_label): ?>
                                <option value="<?php echo esc_attr($op_key); ?>"><?php echo esc_html($op_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="<?php $this->pre('filters[0][0][compare_value]') ?>" class="filter-value" placeholder="Value">
                        
                        <input type="hidden" name="<?php $this->pre('filters[0][0][compare_type]') ?>" class="filter-compare-type" value="<?php echo esc_attr($fields['post_author']['type'] ?? 'text'); ?>">
                        
                        <button type="button" class="button remove-filter">Remove Filter</button>
                    </div>
                </div>
                <button type="button" class="button add-filter">Add Filter to Group</button>
                <button type="button" class="button remove-group">Remove Group</button>
            </div>
        <?php else: ?>
            <?php foreach ($filters_setting as $group_index => $filter_group): ?>
                <div class="filter-group" data-group-index="<?php echo esc_attr($group_index); ?>">
                    <h4>Filter Group <?php echo esc_html($group_index + 1); ?></h4>
                    <div class="filter-group-filters">
                        <?php foreach ($filter_group as $filter_index => $filter): ?>
                            <?php if ($filter_index > 0): ?>
                                <div class="filter-connector">
                                    <span class="connector-label">OR</span>
                                </div>
                            <?php endif; ?>
                            <div class="filter-row" data-filter-index="<?php echo esc_attr($filter_index); ?>">
                                <select name="<?php $this->pre("filters[{$group_index}][{$filter_index}][field_name]") ?>" class="filter-field">
                                    <option value="">Select Field</option>
                                    <?php foreach ($fields as $field_key => $field_info): ?>
                                        <option value="<?php echo esc_attr($field_key); ?>" data-type="<?php echo esc_attr($field_info['type']); ?>" <?php selected($filter['field_name'], $field_key); ?>><?php echo esc_html($field_info['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <input type="text" name="<?php $this->pre("filters[{$group_index}][{$filter_index}][meta_key]") ?>" class="filter-meta-key" placeholder="Meta Key" value="<?php echo esc_attr(isset($filter['is_meta_filter']) && $filter['is_meta_filter'] ? $filter['field_name'] : ($filter['meta_key'] ?? '')); ?>" style="<?php echo (isset($filter['is_meta_filter']) && $filter['is_meta_filter']) ? '' : 'display: none;'; ?>">
                                <select class="meta-type-select" style="<?php echo (isset($filter['is_meta_filter']) && $filter['is_meta_filter']) ? '' : 'display: none;'; ?>">
                                    <option value="text" <?php selected($filter['compare_type'] ?? '', 'text'); ?>>Text</option>
                                    <option value="number" <?php selected($filter['compare_type'] ?? '', 'number'); ?>>Number</option>
                                    <option value="date" <?php selected($filter['compare_type'] ?? '', 'date'); ?>>Date</option>
                                </select>
                                
                                <select name="<?php $this->pre("filters[{$group_index}][{$filter_index}][operator]") ?>" class="filter-operator">
                                    <option value="">Select Operator</option>
                                    <?php foreach ($operators as $op_key => $op_label): ?>
                                        <option value="<?php echo esc_attr($op_key); ?>" <?php selected($filter['operator'], $op_key); ?>><?php echo esc_html($op_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <input type="text" name="<?php $this->pre("filters[{$group_index}][{$filter_index}][compare_value]") ?>" class="filter-value" placeholder="Value" value="<?php echo esc_attr($filter['compare_value']); ?>">
                                
                                <input type="hidden" name="<?php $this->pre("filters[{$group_index}][{$filter_index}][compare_type]") ?>" class="filter-compare-type" value="<?php echo esc_attr($fields[$filter['field_name']]['type'] ?? 'text'); ?>">
                                
                                <button type="button" class="button remove-filter">Remove Filter</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button add-filter">Add Filter to Group</button>
                    <button type="button" class="button remove-group">Remove Group</button>
                </div>
                <?php if ($group_index < count($filters_setting) - 1): ?>
                    <div class="group-connector">
                        <span class="connector-label">AND</span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <button type="button" class="button button-secondary" id="add-filter-group">Add Filter Group</button>
</div>

<script>
jQuery(document).ready(function($) {
    let groupCounter = <?php echo count($filters_setting); ?>;
    let filterCounters = <?php echo json_encode(array_map('count', $filters_setting)); ?>;
    
    // Initialize filter counters for existing groups
    $('.filter-group').each(function(groupIndex) {
        const filterCount = $(this).find('.filter-row').length;
        filterCounters[groupIndex] = filterCount;
    });
    
    // Function to check if all filters are complete
    function validateFilters() {
        let isValid = true;
        let incompleteFilters = [];
        
        // Check each filter group
        $('.filter-group').each(function(groupIndex) {
            const $group = $(this);
            const groupId = groupIndex + 1;
            
            // Check each filter in the group
            $group.find('.filter-row').each(function(filterIndex) {
                const $row = $(this);
                const $field = $row.find('.filter-field');
                const $operator = $row.find('.filter-operator');
                const $value = $row.find('.filter-value');
                const $metaKey = $row.find('.filter-meta-key');
                const $compareType = $row.find('.filter-compare-type');
                
                const fieldVal = $field.val();
                const operatorVal = $operator.val();
                console.log(operatorVal, "operatorVal");
                const valueVal = $value.val().trim();
                const metaKeyVal = $metaKey.val().trim();
                
                // Check if any field is empty
                let isIncomplete = false;
                if (!fieldVal || !operatorVal || !valueVal) {
                    isIncomplete = true;
                }
                
                // If field is 'meta', also check meta key
                if (fieldVal === 'meta' && !metaKeyVal) {
                    isIncomplete = true;
                }
                
                if (isIncomplete) {
                    isValid = false;
                    incompleteFilters.push(`Filter Group ${groupId}, Filter ${filterIndex + 1}`);
                    
                    // Add visual indication
                    $row.addClass('incomplete-filter');
                } else {
                    $row.removeClass('incomplete-filter');
                }
            });
        });
        
        // Update save button state
        const $saveButton = $('input[type="submit"]');
        if (!isValid) {
            $saveButton.prop('disabled', true);
            $saveButton.attr('title', 'Please complete all filters before saving. Incomplete filters: ' + incompleteFilters.join(', '));
            
            // Show validation message
            if ($('.validation-message').length === 0) {
                $('<div class="validation-message notice notice-error"><p><strong>Please complete all filters before saving.</strong> All fields must be filled out. For Custom Field filters, both Meta Key and Value are required.</p></div>').insertBefore('#add-filter-group');
            }
        } else {
            $saveButton.prop('disabled', false);
            $saveButton.attr('title', '');
            $('.validation-message').remove();
        }
        
        return isValid;
    }
    
    // Function to handle field selection changes
    function handleFieldChange($field) {
        const $row = $field.closest('.filter-row');
        const $metaKey = $row.find('.filter-meta-key');
        const $valueInput = $row.find('.filter-value');
        const $compareType = $row.find('.filter-compare-type');
        const selectedField = $field.val();
        const selectedOption = $field.find('option:selected');
        const fieldType = selectedOption.data('type') || 'text';
        const $metaTypeSelect = $row.find('.meta-type-select');

        // Set input type
        if (selectedField === 'meta') {
            $metaKey.show();
            $metaTypeSelect.show();
            $valueInput.attr('type', $metaTypeSelect.val() || 'text');
            $compareType.val($metaTypeSelect.val() || 'text');
        } else {
            $metaKey.hide().val('');
            $metaTypeSelect.hide();
            $valueInput.attr('type', fieldType);
            $compareType.val(fieldType);
        }

        validateFilters();
    }
    
    // Run validation on page load
    validateFilters();
    
    // Initialize existing filters on page load
    $('.filter-field').each(function() {
        const $field = $(this);
        const $row = $field.closest('.filter-row');
        const $metaKey = $row.find('.filter-meta-key');
        
        // Check if this is a meta filter (has meta key value and field is not 'meta')
        if ($metaKey.val() && $field.val() !== 'meta') {
            // This is likely a meta filter, set field to 'meta' and show meta key
            $field.val('meta');
            $metaKey.show();
        }
        
        handleFieldChange($field);
    });
    
    // Handle field selection changes with proper event delegation
    $(document).on('change', '.filter-field', function(e) {
        e.stopPropagation();
        handleFieldChange($(this));
    });
    
    // Run validation when any filter field changes with proper event delegation
    $(document).on('change keyup', '.filter-field, .filter-operator, .filter-value, .filter-meta-key', function(e) {
        e.stopPropagation();
        validateFilters();
    });
    
    // Ensure buttons are clickable with proper event delegation
    $(document).on('click', '.remove-filter, .remove-group, .add-filter', function(e) {
        e.stopPropagation();
    });
    
    // Add filter group
    $('#add-filter-group').on('click', function() {
        const groupIndex = groupCounter++;
        const groupHtml = `
            <div class="group-connector">
                <span class="connector-label">AND</span>
            </div>
            <div class="filter-group" data-group-index="${groupIndex}">
                <h4>Filter Group ${groupIndex + 1}</h4>
                <div class="filter-group-filters">
                    <div class="filter-row" data-filter-index="0">
                        <select name="<?php $this->pre('filters') ?>[${groupIndex}][0][field_name]" class="filter-field">
                            <option value="">Select Field</option>
                            <?php foreach ($fields as $field_key => $field_info): ?>
                                <option value="<?php echo esc_attr($field_key); ?>" data-type="<?php echo esc_attr($field_info['type']); ?>"><?php echo esc_html($field_info['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="<?php $this->pre('filters') ?>[${groupIndex}][0][meta_key]" class="filter-meta-key" placeholder="Meta Key" style="display: none;">
                        <select class="meta-type-select" style="display: none;">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                        </select>
                        
                        <select name="<?php $this->pre('filters') ?>[${groupIndex}][0][operator]" class="filter-operator">
                            <option value="">Select Operator</option>
                            <?php foreach ($operators as $op_key => $op_label): ?>
                                <option value="<?php echo esc_attr($op_key); ?>"><?php echo esc_html($op_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="<?php $this->pre('filters') ?>[${groupIndex}][0][compare_value]" class="filter-value" placeholder="Value">
                        
                        <input type="hidden" name="<?php $this->pre('filters') ?>[${groupIndex}][0][compare_type]" class="filter-compare-type" value="text">
                        
                        <button type="button" class="button remove-filter">Remove Filter</button>
                    </div>
                </div>
                <button type="button" class="button add-filter">Add Filter to Group</button>
                <button type="button" class="button remove-group">Remove Group</button>
            </div>
        `;
        $('#filter-groups').append(groupHtml);
        filterCounters[groupIndex] = 1;
        validateFilters();
    });
    
    // Add filter to group
    $(document).on('click', '.add-filter', function() {
        const $group = $(this).closest('.filter-group');
        const groupIndex = $group.data('group-index');
        const filterIndex = filterCounters[groupIndex] || 0;
        filterCounters[groupIndex] = filterIndex + 1;
        
        const filterHtml = `
            <div class="filter-connector">
                <span class="connector-label">OR</span>
            </div>
            <div class="filter-row" data-filter-index="${filterIndex}">
                <select name="<?php $this->pre('filters') ?>[${groupIndex}][${filterIndex}][field_name]" class="filter-field">
                    <option value="">Select Field</option>
                    <?php foreach ($fields as $field_key => $field_info): ?>
                        <option value="<?php echo esc_attr($field_key); ?>" data-type="<?php echo esc_attr($field_info['type']); ?>"><?php echo esc_html($field_info['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="<?php $this->pre('filters') ?>[${groupIndex}][${filterIndex}][meta_key]" class="filter-meta-key" placeholder="Meta Key" style="display: none;">
                <select name="<?php $this->pre('filters') ?>[${groupIndex}][${filterIndex}][operator]" class="filter-operator" data-operator="">
                    <option value="">Select Operator</option>
                    <?php foreach ($operators as $op_key => $op_label): ?>
                        <option value="<?php echo esc_attr($op_key); ?>"><?php echo esc_html($op_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="meta-type-select" style="display: none;">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                </select>
                <input type="text" name="<?php $this->pre('filters') ?>[${groupIndex}][${filterIndex}][compare_value]" class="filter-value" placeholder="Value">
                
                <input type="hidden" name="<?php $this->pre('filters') ?>[${groupIndex}][${filterIndex}][compare_type]" class="filter-compare-type" value="text">
                
                <button type="button" class="button remove-filter">Remove Filter</button>
            </div>
        `;
        $group.find('.filter-group-filters').append(filterHtml);
        validateFilters();
    });
    
    // Remove filter
    $(document).on('click', '.remove-filter', function() {
        const $filterRow = $(this).closest('.filter-row');
        const $prevConnector = $filterRow.prev('.filter-connector');
        const $nextConnector = $filterRow.next('.filter-connector');
        
        // Remove the filter row
        $filterRow.remove();
        
        // Remove adjacent connectors if they exist
        if ($prevConnector.length) {
            $prevConnector.remove();
        }
        if ($nextConnector.length) {
            $nextConnector.remove();
        }
        
        validateFilters();
    });
    
    // Remove group
    $(document).on('click', '.remove-group', function() {
        const $group = $(this).closest('.filter-group');
        const $prevConnector = $group.prev('.group-connector');
        const $nextConnector = $group.next('.group-connector');
        
        // Remove the group
        $group.remove();
        
        // Remove adjacent connectors if they exist
        if ($prevConnector.length) {
            $prevConnector.remove();
        }
        if ($nextConnector.length) {
            $nextConnector.remove();
        }
        
        validateFilters();
    });
    
    // Prevent form submission if validation fails
    $('form').on('submit', function(e) {
        if (!validateFilters()) {
            e.preventDefault();
            alert('Please complete all filters before saving. All fields (Field, Operator, and Value) must be filled out.');
            return false;
        }
    });

    // js handler for when a meta type is selected for a filter
    $(document).on('change', '.meta-type-select', function() {
        const $row = $(this).closest('.filter-row');
        const $valueInput = $row.find('.filter-value');
        const $compareType = $row.find('.filter-compare-type');
        const type = $(this).val();
        $valueInput.attr('type', type);
        $compareType.val(type);
    });
});
</script> 