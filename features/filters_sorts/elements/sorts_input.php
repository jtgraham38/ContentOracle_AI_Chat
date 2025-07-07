<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//load the current value of the sorts setting
$sorts_setting = get_option($this->prefixed('sorts'), array());

//available fields for sorting
$fields = array(
    'coai_score' => ['label' => 'Relevance Score', 'type' => 'number'],
    'post_date' => ['label' => 'Post Date', 'type' => 'date'],
    'post_modified' => ['label' => 'Post Modified Date', 'type' => 'date'],
    'post_title' => ['label' => 'Post Title', 'type' => 'text'],
    'comment_count' => ['label' => 'Comment Count', 'type' => 'number'],
    'meta' => ['label' => 'Custom Field (Meta)', 'type' => 'text']
);

//available sort directions
$directions = array(
    'ASC' => 'Ascending (A-Z, 1-9, Oldest First)',
    'DESC' => 'Descending (Z-A, 9-1, Newest First)'
);

echo '<pre>';
print_r($sorts_setting);
echo '</pre>';

?>

<div id="<?php $this->pre('sorts_input') ?>">
    <p class="description">
        Configure sorting options for AI search results.  
        Each sort is applied in the order they are defined.
        Sorts are applied after semantic retrieval, meaning that the most relevant posts are retrieved, then sorts are applied.
    </p>
    
    <div id="sort-list">
        <?php if (!empty($sorts_setting)): ?>
            <?php foreach ($sorts_setting as $sort_index => $sort): ?>
                <div class="sort-box filter-group" data-sort-index="<?php echo esc_attr($sort_index); ?>">
                    <div class="sort-row filter-row" data-sort-index="<?php echo esc_attr($sort_index); ?>">
                        <select name="<?php $this->pre("sorts[{$sort_index}][field_name]") ?>" class="sort-field filter-field">
                            <option value="">Select Field</option>
                            <?php foreach ($fields as $field_key => $field_info): ?>
                                <option value="<?php echo esc_attr($field_key); ?>" data-type="<?php echo esc_attr($field_info['type']); ?>" <?php selected($sort['field_name'], $field_key); ?>><?php echo esc_html($field_info['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="<?php $this->pre("sorts[{$sort_index}][meta_key]") ?>" class="sort-meta-key filter-meta-key" placeholder="Meta Key" value="<?php echo esc_attr(isset($sort['is_meta_sort']) && $sort['is_meta_sort'] ? $sort['field_name'] : ($sort['meta_key'] ?? '')); ?>" style="<?php echo (isset($sort['is_meta_sort']) && $sort['is_meta_sort']) ? '' : 'display: none;'; ?>">
                        <select name="<?php $this->pre("sorts[{$sort_index}][direction]") ?>" class="sort-direction">
                            <option value="">Select Direction</option>
                            <?php foreach ($directions as $dir_key => $dir_label): ?>
                                <option value="<?php echo esc_attr($dir_key); ?>" <?php selected($sort['direction'], $dir_key); ?>><?php echo esc_html($dir_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="<?php $this->pre("sorts[{$sort_index}][field_type]") ?>" class="sort-field-type" value="text">
                        <button type="button" class="button remove-sort remove-filter">Remove Sort</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <button type="button" class="button button-secondary" id="add-sort">Add Sort</button>
    
    <div id="sort-validation-message" class="validation-message" style="display: none;">
        <p>Please complete all sort configurations before saving.</p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let sortCounter = <?php echo count($sorts_setting); ?>;
    function validateSorts() {
        let isValid = true;
        let incompleteSorts = [];
        $('#sort-list .sort-box').each(function(sortIndex) {
            const $row = $(this).find('.sort-row');
            const $field = $row.find('.sort-field');
            const $direction = $row.find('.sort-direction');
            const $metaKey = $row.find('.sort-meta-key');
            const fieldVal = $field.val();
            const directionVal = $direction.val();
            const metaKeyVal = $metaKey.val().trim();
            let isIncomplete = false;
            if (!fieldVal || !directionVal) {
                isIncomplete = true;
            }
            if (fieldVal === 'meta' && !metaKeyVal) {
                isIncomplete = true;
            }
            if (isIncomplete) {
                isValid = false;
                incompleteSorts.push(`Sort ${sortIndex + 1}`);
                $row.addClass('incomplete-sort');
            } else {
                $row.removeClass('incomplete-sort');
            }
        });
        const $saveButton = $('input[type="submit"]');
        if (!isValid) {
            $saveButton.prop('disabled', true);
            $saveButton.attr('title', 'Please complete all sorts before saving. Incomplete sorts: ' + incompleteSorts.join(', '));
            if ($('#sort-validation-message').length === 0) {
                $('<div id="sort-validation-message" class="validation-message notice notice-error"><p><strong>Please complete all sorts before saving.</strong> All fields must be filled out. For Custom Field sorts, Meta Key is required.</p></div>').insertBefore('#add-sort');
            }
        } else {
            $saveButton.prop('disabled', false);
            $saveButton.attr('title', '');
            $('#sort-validation-message').remove();
        }
        return isValid;
    }
    function handleFieldChange($field) {
        const $row = $field.closest('.sort-row');
        const $metaKey = $row.find('.sort-meta-key');
        const $fieldType = $row.find('.sort-field-type');
        const selectedField = $field.val();
        if (selectedField === 'meta') {
            $metaKey.show();
            $fieldType.val('text');
        } else {
            $metaKey.hide().val('');
            $fieldType.val('text');
        }
        validateSorts();
    }
    validateSorts();
    $('.sort-field').each(function() {
        const $field = $(this);
        const $row = $field.closest('.sort-row');
        const $metaKey = $row.find('.sort-meta-key');
        if ($metaKey.val() && $field.val() !== 'meta') {
            $field.val('meta');
            $metaKey.show();
        }
        handleFieldChange($field);
    });
    $(document).on('change', '.sort-field', function(e) {
        e.stopPropagation();
        handleFieldChange($(this));
    });
    $(document).on('change keyup', '.sort-field, .sort-direction, .sort-meta-key', function(e) {
        e.stopPropagation();
        validateSorts();
    });
    $(document).on('click', '.remove-sort', function(e) {
        e.stopPropagation();
        $(this).closest('.sort-box').remove();
        validateSorts();
    });
    $('#add-sort').on('click', function() {
        const sortIndex = sortCounter++;
        const sortHtml = `
            <div class="sort-box filter-group" data-sort-index="${sortIndex}">
                <div class="sort-row filter-row" data-sort-index="${sortIndex}">
                    <select name="<?php $this->pre('sorts') ?>[${sortIndex}][field_name]" class="sort-field filter-field">
                        <option value="">Select Field</option>
                        <?php foreach ($fields as $field_key => $field_info): ?>
                            <option value="<?php echo esc_attr($field_key); ?>" data-type="<?php echo esc_attr($field_info['type']); ?>"><?php echo esc_html($field_info['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="<?php $this->pre('sorts') ?>[${sortIndex}][meta_key]" class="sort-meta-key filter-meta-key" placeholder="Meta Key" style="display: none;">
                    <select name="<?php $this->pre('sorts') ?>[${sortIndex}][direction]" class="sort-direction">
                        <option value="">Select Direction</option>
                        <?php foreach ($directions as $dir_key => $dir_label): ?>
                            <option value="<?php echo esc_attr($dir_key); ?>"><?php echo esc_html($dir_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="<?php $this->pre('sorts') ?>[${sortIndex}][field_type]" class="sort-field-type" value="text">
                    <button type="button" class="button remove-sort remove-filter">Remove Sort</button>
                </div>
            </div>
        `;
        $('#sort-list').append(sortHtml);
        validateSorts();
    });
    $('form').on('submit', function(e) {
        if (!validateSorts()) {
            e.preventDefault();
            alert('Please complete all sorts before saving. All fields (Field and Direction) must be filled out.');
            return false;
        }
    });
});
</script> 