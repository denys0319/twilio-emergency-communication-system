jQuery(document).ready(function($) {
    'use strict';
    
    console.log('ECS Admin JS loaded!'); // Test if script loads
    console.log('jQuery version:', $.fn.jquery); // Test if jQuery is available
    
    // Show JavaScript test element
    $('#ecs-js-test').show();
    $('#ecs-js-status').text('Loaded successfully!');
    
    // Initialize the admin interface
    initAdminInterface();
    
    function initAdminInterface() {
        initModals();
        initContactManagement();
        initGroupManagement();
        initComposeAlert();
        initMessageHistory();
        initCharacterCount();
    }
    
    // Modal functionality
    function initModals() {
        // Close modal when clicking X or outside
        $(document).on('click', '.ecs-modal-close, .ecs-modal-cancel', function() {
            $(this).closest('.ecs-modal').hide();
        });
        
        $(document).on('click', '.ecs-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                $('.ecs-modal:visible').hide();
            }
        });
    }
    
    // Contact management
    function initContactManagement() {
        // Add contact button
        $('#ecs-add-contact-btn, #ecs-add-individual-contact-btn').on('click', function() {
            resetContactForm();
            $('#ecs-contact-modal, #ecs-individual-contact-modal').show();
        });
        
        // Upload contacts button
        $('#ecs-upload-contacts-btn').on('click', function() {
            $('#ecs-upload-modal').show();
        });
        
        // Edit contact
        $(document).on('click', '.ecs-edit-contact, .ecs-edit-individual-contact', function() {
            var contactId = $(this).data('contact-id');
            loadContactForEdit(contactId);
        });
        
        // Delete contact
        $(document).on('click', '.ecs-delete-contact, .ecs-delete-individual-contact', function() {
            var contactId = $(this).data('contact-id');
            if (confirm(ecs_ajax.strings.confirm_delete)) {
                deleteContact(contactId);
            }
        });
        
        // Contact form submission
        $('#ecs-contact-form, #ecs-individual-contact-form').on('submit', function(e) {
            e.preventDefault();
            submitContactForm();
        });
        
        // Upload form submission
        $('#ecs-upload-form').on('submit', function(e) {
            e.preventDefault();
            submitUploadForm();
        });
    }
    
    // Group management
    function initGroupManagement() {
        console.log('initGroupManagement called'); // Debug log
        
        // Add group button
        $('#ecs-add-group-btn').on('click', function() {
            resetGroupForm();
            $('#ecs-group-modal').show();
        });
        
        // Edit group
        $(document).on('click', '.ecs-edit-group', function() {
            var groupId = $(this).data('group-id');
            loadGroupForEdit(groupId);
        });
        
        // Delete group
        $(document).on('click', '.ecs-delete-group', function() {
            var groupId = $(this).data('group-id');
            if (confirm(ecs_ajax.strings.confirm_delete)) {
                deleteGroup(groupId);
            }
        });
        
        // Group form submission
        $('#ecs-group-form').on('submit', function(e) {
            console.log('Form submitted!'); // Debug log
            e.preventDefault();
            submitGroupForm();
        });
    }
    
    // Compose alert functionality
    function initComposeAlert() {
        // Tab switching
        $('.ecs-tab-button').on('click', function() {
            var tab = $(this).data('tab');
            switchTab(tab);
        });
        
        // Group selection
        $(document).on('change', '.ecs-group-checkbox', function() {
            updateRecipientsSummary();
        });
        
        // Individual contact search
        $('#ecs-individuals-search').on('input', function() {
            var searchTerm = $(this).val();
            searchIndividualContacts(searchTerm);
        });
        
        // Individual contact selection
        $(document).on('change', '.ecs-individual-checkbox', function() {
            updateRecipientsSummary();
        });
        
        // Custom numbers
        $('#ecs-custom-numbers').on('input', function() {
            updateRecipientsSummary();
        });
        
        // Send type change
        $('#ecs-send-type').on('change', function() {
            var sendType = $(this).val();
            if (sendType === 'scheduled') {
                $('#ecs-schedule-row').show();
            } else {
                $('#ecs-schedule-row').hide();
            }
        });
        
        // Preview message
        $('#ecs-preview-message').on('click', function() {
            previewMessage();
        });
        
        // Send message
        $('#ecs-send-message').on('click', function() {
            sendMessage();
        });
        
        // Save draft
        $('#ecs-save-draft').on('click', function() {
            saveDraft();
        });
        
        // Initialize datetime picker (HTML5 datetime-local)
        // No initialization needed for HTML5 datetime-local inputs
    }
    
    // Message history functionality
    function initMessageHistory() {
        // Check status
        $(document).on('click', '.ecs-check-status', function() {
            var messageSid = $(this).data('message-sid');
            checkMessageStatus(messageSid);
        });
        
        // View full message
        $(document).on('click', '.ecs-view-full-message', function() {
            var message = $(this).data('message');
            showFullMessage(message);
        });
        
        // Cancel scheduled message
        $(document).on('click', '.ecs-cancel-scheduled', function() {
            var scheduledId = $(this).data('scheduled-id');
            if (confirm(ecs_ajax.strings.confirm_delete)) {
                cancelScheduledMessage(scheduledId);
            }
        });
        
        // Refresh status
        $('#ecs-refresh-status').on('click', function() {
            refreshAllStatuses();
        });
        
        // Export messages
        $('#ecs-export-messages').on('click', function() {
            exportMessages();
        });
    }
    
    // Character count
    function initCharacterCount() {
        $('#ecs_message_text').on('input', function() {
            var length = $(this).val().length;
            $('#ecs-char-count').text(length);
            
            if (length > 160) {
                $('#ecs-char-count').css('color', '#dc3545');
            } else {
                $('#ecs-char-count').css('color', '#0073aa');
            }
        });
    }
    
    // Helper functions
    function resetContactForm() {
        $('#ecs-contact-form, #ecs-individual-contact-form')[0].reset();
        $('#ecs_contact_id, #ecs_individual_contact_id').val('');
        $('#ecs-modal-title, #ecs-individual-modal-title').text('Add Contact');
    }
    
    function resetGroupForm() {
        $('#ecs-group-form')[0].reset();
        $('#ecs_group_id').val('');
        $('#ecs-group-modal-title').text('Add Contact Group');
    }
    
    function loadContactForEdit(contactId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_get_contact',
                contact_id: contactId,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var contact = response.data;
                    $('#ecs_contact_id, #ecs_individual_contact_id').val(contact.id);
                    $('#ecs_contact_phone, #ecs_individual_contact_phone').val(contact.phone);
                    $('#ecs_contact_name, #ecs_individual_contact_name').val(contact.name);
                    $('#ecs_contact_group, #ecs_individual_contact_group').val(contact.group_id);
                    $('#ecs-modal-title, #ecs-individual-modal-title').text('Edit Contact');
                    $('#ecs-contact-modal, #ecs-individual-contact-modal').show();
                }
            }
        });
    }
    
    function loadGroupForEdit(groupId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_get_group',
                group_id: groupId,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var group = response.data;
                    $('#ecs_group_id').val(group.id);
                    $('#ecs_group_name').val(group.name);
                    $('#ecs_group_description').val(group.description);
                    $('#ecs-group-modal-title').text('Edit Contact Group');
                    $('#ecs-group-modal').show();
                }
            }
        });
    }
    
    function submitContactForm() {
        var formData = $('#ecs-contact-form, #ecs-individual-contact-form').serialize();
        formData += '&action=ecs_save_contact&nonce=' + ecs_ajax.nonce;
        
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function submitGroupForm() {
        console.log('submitGroupForm called'); // Debug log
        var formData = $('#ecs-group-form').serialize();
        formData += '&action=ecs_save_group';
        
        console.log('Form data:', formData); // Debug log
        console.log('AJAX URL:', ecs_ajax.ajax_url); // Debug log
        
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('AJAX Success Response:', response); // Debug log
                if (response.success) {
                    alert('Group saved successfully!');
                    location.reload();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error); // Debug log
                console.log('Response Text:', xhr.responseText); // Debug log
                alert('AJAX Error: ' + error + '\nStatus: ' + status);
            }
        });
    }
    
    function submitUploadForm() {
        var formData = new FormData($('#ecs-upload-form')[0]);
        formData.append('action', 'ecs_upload_contacts');
        formData.append('nonce', ecs_ajax.nonce);
        
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function deleteContact(contactId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_delete_contact',
                contact_id: contactId,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('tr[data-contact-id="' + contactId + '"]').remove();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function deleteGroup(groupId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_delete_group',
                group_id: groupId,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('tr[data-group-id="' + groupId + '"]').remove();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function switchTab(tab) {
        $('.ecs-tab-button').removeClass('active');
        $('.ecs-tab-content').removeClass('active');
        
        $('.ecs-tab-button[data-tab="' + tab + '"]').addClass('active');
        $('#ecs-' + tab + '-tab').addClass('active');
        
        if (tab === 'individuals') {
            loadIndividualContacts();
        }
    }
    
    function loadIndividualContacts() {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_get_contacts',
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayIndividualContacts(response.data);
                }
            }
        });
    }
    
    function displayIndividualContacts(contacts) {
        var html = '';
        contacts.forEach(function(contact) {
            html += '<div class="ecs-individual-item">';
            html += '<input type="checkbox" class="ecs-individual-checkbox" value="' + contact.id + '" data-phone="' + contact.phone + '" data-name="' + contact.name + '" />';
            html += '<div class="ecs-individual-info">';
            html += '<strong>' + (contact.name || 'Unknown') + '</strong>';
            html += '<br><small>' + contact.phone + '</small>';
            if (contact.group_name) {
                html += '<br><small>Group: ' + contact.group_name + '</small>';
            }
            html += '</div>';
            html += '</div>';
        });
        $('#ecs-individuals-list').html(html);
    }
    
    function searchIndividualContacts(searchTerm) {
        var items = $('.ecs-individual-item');
        items.each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(searchTerm.toLowerCase()) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    }
    
    function updateRecipientsSummary() {
        var recipients = [];
        
        // Add selected groups
        $('.ecs-group-checkbox:checked').each(function() {
            var groupName = $(this).data('group-name');
            recipients.push({type: 'group', name: groupName, id: $(this).val()});
        });
        
        // Add selected individuals
        $('.ecs-individual-checkbox:checked').each(function() {
            var name = $(this).data('name') || 'Unknown';
            var phone = $(this).data('phone');
            recipients.push({type: 'individual', name: name, phone: phone, id: $(this).val()});
        });
        
        // Add custom numbers
        var customNumbers = $('#ecs-custom-numbers').val().split('\n').filter(function(num) {
            return num.trim() !== '';
        });
        customNumbers.forEach(function(phone) {
            recipients.push({type: 'custom', phone: phone.trim()});
        });
        
        displayRecipientsSummary(recipients);
    }
    
    function displayRecipientsSummary(recipients) {
        var html = '';
        if (recipients.length === 0) {
            html = '<p>No recipients selected</p>';
        } else {
            html = '<div class="ecs-recipients-list">';
            recipients.forEach(function(recipient) {
                html += '<span class="ecs-recipient-tag">';
                if (recipient.type === 'group') {
                    html += 'Group: ' + recipient.name;
                } else if (recipient.type === 'individual') {
                    html += recipient.name + ' (' + recipient.phone + ')';
                } else {
                    html += recipient.phone;
                }
                html += '<span class="remove" data-type="' + recipient.type + '" data-id="' + (recipient.id || '') + '">&times;</span>';
                html += '</span>';
            });
            html += '</div>';
        }
        $('#ecs-recipients-summary').html(html);
    }
    
    function previewMessage() {
        var message = $('#ecs_message_text').val();
        var recipients = getSelectedRecipients();
        var sendType = $('#ecs-send-type').val();
        var scheduleTime = $('#ecs-schedule-time').val();
        
        $('#ecs-preview-text').text(message);
        
        var recipientsHtml = '';
        recipients.forEach(function(recipient) {
            recipientsHtml += '<div>' + recipient.name + ' (' + recipient.phone + ')</div>';
        });
        $('#ecs-preview-recipients').html(recipientsHtml);
        
        var detailsHtml = '<div><strong>Send Type:</strong> ' + (sendType === 'immediate' ? 'Immediate' : 'Scheduled') + '</div>';
        if (sendType === 'scheduled' && scheduleTime) {
            detailsHtml += '<div><strong>Scheduled For:</strong> ' + scheduleTime + '</div>';
        }
        detailsHtml += '<div><strong>Total Recipients:</strong> ' + recipients.length + '</div>';
        $('#ecs-preview-details').html(detailsHtml);
        
        $('#ecs-preview-modal').show();
    }
    
    function getSelectedRecipients() {
        var recipients = [];
        
        // Get group recipients
        $('.ecs-group-checkbox:checked').each(function() {
            var groupId = $(this).val();
            // This would need to be populated with actual group contacts via AJAX
            recipients.push({name: 'Group: ' + $(this).data('group-name'), phone: 'Multiple', group_id: groupId});
        });
        
        // Get individual recipients
        $('.ecs-individual-checkbox:checked').each(function() {
            recipients.push({
                name: $(this).data('name') || 'Unknown',
                phone: $(this).data('phone'),
                id: $(this).val()
            });
        });
        
        // Get custom recipients
        var customNumbers = $('#ecs-custom-numbers').val().split('\n').filter(function(num) {
            return num.trim() !== '';
        });
        customNumbers.forEach(function(phone) {
            recipients.push({name: 'Custom', phone: phone.trim()});
        });
        
        return recipients;
    }
    
    function sendMessage() {
        var message = $('#ecs_message_text').val();
        var recipients = getSelectedRecipients();
        var fromNumber = $('#ecs_from_number').val();
        var sendType = $('#ecs-send-type').val();
        var scheduleTime = $('#ecs-schedule-time').val();
        
        if (!message || recipients.length === 0) {
            alert('Please enter a message and select recipients.');
            return;
        }
        
        var action = sendType === 'immediate' ? 'ecs_send_message' : 'ecs_schedule_message';
        var data = {
            action: action,
            message: message,
            recipients: JSON.stringify(recipients),
            from_number: fromNumber,
            nonce: ecs_ajax.nonce
        };
        
        if (sendType === 'scheduled') {
            if (!scheduleTime) {
                alert('Please select a schedule time.');
                return;
            }
            data.send_time = scheduleTime;
        }
        
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert('Message ' + (sendType === 'immediate' ? 'sent' : 'scheduled') + ' successfully!');
                    $('#ecs-compose-form')[0].reset();
                    updateRecipientsSummary();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function saveDraft() {
        // Implement draft saving functionality
        alert('Draft saving functionality not yet implemented.');
    }
    
    function checkMessageStatus(messageSid) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_get_message_status',
                message_sid: messageSid,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function showFullMessage(message) {
        $('#ecs-full-message-content').text(message);
        $('#ecs-view-message-modal').show();
    }
    
    function cancelScheduledMessage(scheduledId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_delete_scheduled_message',
                message_id: scheduledId,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('tr[data-scheduled-id="' + scheduledId + '"]').remove();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function() {
                alert(ecs_ajax.strings.error);
            }
        });
    }
    
    function refreshAllStatuses() {
        $('.ecs-check-status').each(function() {
            var messageSid = $(this).data('message-sid');
            if (messageSid) {
                checkMessageStatus(messageSid);
            }
        });
    }
    
    function exportMessages() {
        // Implement message export functionality
        alert('Export functionality not yet implemented.');
    }
});
