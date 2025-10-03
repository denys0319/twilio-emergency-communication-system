<?php
/**
 * Contacts management page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_service = new ECS_Service();

// Get groups from WordPress database
$groups_result = $ecs_service->get_contact_groups();
$groups = $groups_result['success'] ? $groups_result['groups'] : array();

// Handle pagination and filtering
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$filter_group = isset($_GET['group']) ? $_GET['group'] : null;

// Convert filter_group for database query
$db_filter_group = null;
if ($filter_group === 'no-group') {
    $db_filter_group = 'no-group'; // Special value for contacts without groups
} elseif ($filter_group && is_numeric($filter_group)) {
    $db_filter_group = intval($filter_group);
}

// Get contacts with filter
$contacts_result = $ecs_service->get_contacts($db_filter_group, $per_page, $offset);
$contacts = $contacts_result['success'] ? $contacts_result['contacts'] : array();

// Get total contacts for pagination (with filter)
$total_contacts_result = $ecs_service->get_contacts($db_filter_group, 1000, 0);
$total_contacts = $total_contacts_result['success'] ? count($total_contacts_result['contacts']) : 0;
$total_pages = ceil($total_contacts / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Contacts Management', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-phone-lists">
        <div class="ecs-actions-bar">
            <button type="button" class="button button-primary" id="ecs-add-contact-btn">
                <?php _e('Add Contact', 'emergency-communication-system'); ?>
            </button>
            <button type="button" class="button" id="ecs-upload-contacts-btn">
                <?php _e('Upload from File', 'emergency-communication-system'); ?>
            </button>
        </div>
        
        <!-- Add/Edit Contact Modal -->
        <div id="ecs-contact-modal" class="ecs-modal" style="display: none;">
            <div class="ecs-modal-content">
                <div class="ecs-modal-header">
                    <h2 id="ecs-modal-title"><?php _e('Add Contact', 'emergency-communication-system'); ?></h2>
                    <span class="ecs-modal-close">&times;</span>
                </div>
                <div class="ecs-modal-body">
                    <form id="ecs-contact-form">
                        <?php wp_nonce_field('ecs_contact', 'ecs_contact_nonce'); ?>
                        <input type="hidden" id="ecs_contact_id" name="ecs_contact_id" value="" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ecs_contact_phone"><?php _e('Phone Number', 'emergency-communication-system'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="ecs_contact_phone" name="ecs_contact_phone" 
                                        class="regular-text" placeholder="+1234567890" required />
                                    <p class="description">
                                        <?php _e('Enter phone number in E.164 format (e.g., +1234567890)', 'emergency-communication-system'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ecs_contact_name"><?php _e('Name', 'emergency-communication-system'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="ecs_contact_name" name="ecs_contact_name" 
                                        class="regular-text" placeholder="<?php _e('Contact Name', 'emergency-communication-system'); ?>" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ecs_contact_group"><?php _e('Group', 'emergency-communication-system'); ?></label>
                                </th>
                                <td>
                                    <select id="ecs_contact_group" name="ecs_contact_group">
                                        <option value=""><?php _e('No Group', 'emergency-communication-system'); ?></option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?php echo esc_attr($group['id']); ?>">
                                                <?php echo esc_html($group['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="ecs_contact_submit" class="button-primary" 
                                value="<?php _e('Save Contact', 'emergency-communication-system'); ?>" />
                            <button type="button" class="button ecs-modal-cancel">
                                <?php _e('Cancel', 'emergency-communication-system'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Upload Modal -->
        <div id="ecs-upload-modal" class="ecs-modal" style="display: none;">
            <div class="ecs-modal-content">
                <div class="ecs-modal-header">
                    <h2><?php _e('Upload Contacts from File', 'emergency-communication-system'); ?></h2>
                    <span class="ecs-modal-close">&times;</span>
                </div>
                <div class="ecs-modal-body">
                    <form id="ecs-upload-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('ecs_upload', 'ecs_upload_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ecs_contact_file"><?php _e('Select File', 'emergency-communication-system'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="ecs_contact_file" name="ecs_contact_file" 
                                        accept=".csv,.xlsx,.xls" required />
                                    <p class="description">
                                        <?php _e('Upload CSV file with "Display name" column and either "Phone number" or "Mobile Phone" column. Phone numbers will be automatically normalized to E.164 format.', 'emergency-communication-system'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="ecs_upload_submit" class="button-primary" 
                                value="<?php _e('Upload Contacts', 'emergency-communication-system'); ?>" />
                            <button type="button" class="button ecs-modal-cancel">
                                <?php _e('Cancel', 'emergency-communication-system'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="ecs-filter-panel">
            <h3><?php _e('Filter Contacts', 'emergency-communication-system'); ?></h3>
            <form method="get" class="ecs-filter-form">
                <input type="hidden" name="page" value="ecs-phone-lists" />
                <div class="ecs-filter-controls">
                    <label for="ecs-group-filter"><?php _e('Filter by Group:', 'emergency-communication-system'); ?></label>
                    <select name="group" id="ecs-group-filter" class="ecs-filter-select">
                        <option value=""><?php _e('All Groups', 'emergency-communication-system'); ?></option>
                        <option value="no-group" <?php selected($filter_group, 'no-group'); ?>><?php _e('No Group', 'emergency-communication-system'); ?></option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group['id']); ?>" <?php selected($filter_group, $group['id']); ?>>
                                <?php echo esc_html($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button button-primary" value="<?php _e('Apply Filter', 'emergency-communication-system'); ?>" />
                </div>
            </form>
            <div class="ecs-filter-results">
                <span><?php echo $total_contacts; ?> <?php _e('contacts found', 'emergency-communication-system'); ?></span>
                <?php if ($filter_group): ?>
                    <span class="ecs-active-filter">
                        <?php 
                        if ($filter_group === 'no-group') {
                            $group_name = __('No Group', 'emergency-communication-system');
                        } else {
                            $selected_group = array_filter($groups, function($g) use ($filter_group) { return $g['id'] == $filter_group; });
                            $group_name = !empty($selected_group) ? reset($selected_group)['name'] : 'Unknown Group';
                        }
                        ?>
                        - <?php printf(__('Filtered by: %s', 'emergency-communication-system'), esc_html($group_name)); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Contacts List -->
        <div class="ecs-contacts-list">
            <h2><?php _e('Contacts', 'emergency-communication-system'); ?></h2>
            
            <?php if (empty($contacts)): ?>
                <p><?php _e('No contacts found. Add contacts or upload from file.', 'emergency-communication-system'); ?></p>
            <?php else: ?>
                <!-- Bulk Actions -->
                <div class="ecs-bulk-actions">
                    <div class="ecs-bulk-controls">
                        <select id="ecs-bulk-action" class="ecs-bulk-select">
                            <option value=""><?php _e('Bulk Actions', 'emergency-communication-system'); ?></option>
                            <option value="delete"><?php _e('Delete Selected', 'emergency-communication-system'); ?></option>
                            <option value="move-to-group"><?php _e('Move to Group', 'emergency-communication-system'); ?></option>
                        </select>
                        <select id="ecs-bulk-group" class="ecs-bulk-group-select" style="display: none;">
                            <option value=""><?php _e('Select Group', 'emergency-communication-system'); ?></option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo esc_attr($group['id']); ?>">
                                    <?php echo esc_html($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="ecs-apply-bulk-action" class="button">
                            <?php _e('Apply', 'emergency-communication-system'); ?>
                        </button>
                        <button type="button" id="ecs-select-all" class="button">
                            <?php _e('Select All', 'emergency-communication-system'); ?>
                        </button>
                        <button type="button" id="ecs-clear-selection" class="button">
                            <?php _e('Clear Selection', 'emergency-communication-system'); ?>
                        </button>
                    </div>
                    <div class="ecs-selection-info">
                        <span id="ecs-selected-count">0 <?php _e('contacts selected', 'emergency-communication-system'); ?></span>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped" id="ecs-contacts-table">
                    <thead>
                        <tr>
                            <th class="ecs-check-column">
                                <input type="checkbox" id="ecs-select-all-checkbox" />
                            </th>
                            <th><?php _e('Name', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Phone', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Group', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Actions', 'emergency-communication-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr data-contact-id="<?php echo esc_attr($contact['id']); ?>" 
                                data-group-id="<?php echo esc_attr($contact['group_id'] ?: ''); ?>">
                                <td class="ecs-check-column">
                                    <input type="checkbox" class="ecs-contact-checkbox" value="<?php echo esc_attr($contact['id']); ?>" />
                                </td>
                                <td><?php echo esc_html($contact['name'] ?: '—'); ?></td>
                                <td><?php echo esc_html($contact['phone']); ?></td>
                                <td><?php echo esc_html($contact['group_name'] ?: '—'); ?></td>
                                <td>
                                    <button type="button" class="button button-small ecs-edit-contact" 
                                            data-contact-id="<?php echo esc_attr($contact['id']); ?>">
                                        <?php _e('Edit', 'emergency-communication-system'); ?>
                                    </button>
                                    <button type="button" class="button button-small ecs-delete-contact" 
                                            data-contact-id="<?php echo esc_attr($contact['id']); ?>">
                                        <?php _e('Delete', 'emergency-communication-system'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="ecs-pagination">
                        <?php
                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Previous'),
                            'next_text' => __('Next &raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        );
                        
                        // Preserve filter in pagination
                        if ($filter_group) {
                            $pagination_args['add_args'] = array('group' => $filter_group);
                        }
                        
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bulk Action Confirmation Modal -->
    <div id="ecs-bulk-confirm-modal" class="ecs-bulk-confirm-modal">
        <div class="ecs-bulk-confirm-content">
            <div class="ecs-bulk-confirm-header">
                <h3 id="ecs-confirm-title"><?php _e('Confirm Bulk Action', 'emergency-communication-system'); ?></h3>
            </div>
            <div class="ecs-bulk-confirm-body">
                <p id="ecs-confirm-message"></p>
            </div>
            <div class="ecs-bulk-confirm-actions">
                <button type="button" id="ecs-confirm-cancel" class="button"><?php _e('Cancel', 'emergency-communication-system'); ?></button>
                <button type="button" id="ecs-confirm-proceed" class="button button-primary"><?php _e('Proceed', 'emergency-communication-system'); ?></button>
            </div>
        </div>
    </div>
</div>
