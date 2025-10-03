jQuery(document).ready(function($) {
    'use strict';
    
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
                // Set current time as default and minimum
                setCurrentTimeAsDefault();
                updateScheduleTimeMin();
            } else {
                $('#ecs-schedule-row').hide();
            }
        });
        
        // Validate scheduled time on change
        $('#ecs-schedule-time').on('change', function() {
            validateScheduledTime();
        });
        
        // Update minimum datetime periodically
        setInterval(updateScheduleTimeMin, 30000); // Update every 30 seconds
        
        // Preview message
        $('#ecs-preview-message').on('click', function() {
            previewMessage();
        });
        
        // Send message
        $('#ecs-send-message').on('click', function(e) {
            e.preventDefault();
            sendMessage();
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
        
        // Delete single message
        $(document).on('click', '.ecs-delete-message', function() {
            var messageId = $(this).data('message-id');
            if (confirm('Are you sure you want to delete this message?')) {
                deleteMessage(messageId);
            }
        });
        
        // Cancel scheduled message
        $(document).on('click', '.ecs-cancel-scheduled', function() {
            var scheduledId = $(this).data('scheduled-id');
            if (confirm(ecs_ajax.strings.confirm_cancel)) {
                cancelScheduledMessage(scheduledId);
            }
        });
        
        // Delete single scheduled message
        $(document).on('click', '.ecs-delete-scheduled-single', function() {
            var scheduledId = $(this).data('scheduled-id');
            if (confirm('Are you sure you want to delete this scheduled message?')) {
                deleteScheduledMessage(scheduledId);
            }
        });
        
        // Refresh status
        $('#ecs-refresh-status').on('click', function() {
            refreshAllStatuses();
        });
        
        // Initialize bulk delete for messages
        initBulkDeleteMessages();
        
        // Initialize bulk delete for scheduled messages
        initBulkDeleteScheduled();
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
        var formData = $('#ecs-group-form').serialize();
        formData += '&action=ecs_save_group';
        
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Group saved successfully!');
                    location.reload();
                } else {
                    alert(response.data.message || ecs_ajax.strings.error);
                }
            },
            error: function(xhr, status, error) {
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
    
    function getWordPressTime() {
        // Get current WordPress time using the calculated offset
        if (typeof window.ecsTimeOffset !== 'undefined') {
            return new Date(new Date().getTime() + window.ecsTimeOffset);
        }
        // Fallback to browser time if offset not available
        return new Date();
    }
    
    function formatDateTimeLocal(date) {
        // Format date as YYYY-MM-DDTHH:MM for datetime-local input
        // Use UTC methods to avoid timezone conversion
        var year = date.getUTCFullYear();
        var month = String(date.getUTCMonth() + 1).padStart(2, '0');
        var day = String(date.getUTCDate()).padStart(2, '0');
        var hours = String(date.getUTCHours()).padStart(2, '0');
        var minutes = String(date.getUTCMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }
    
    function setCurrentTimeAsDefault() {
        var wpNow = getWordPressTime();
        var currentDateTime = formatDateTimeLocal(wpNow);
        $('#ecs-schedule-time').val(currentDateTime);
        $('#ecs-schedule-time').attr('min', currentDateTime);
    }
    
    function updateScheduleTimeMin() {
        var wpNow = getWordPressTime();
        var minDateTime = formatDateTimeLocal(wpNow);
        $('#ecs-schedule-time').attr('min', minDateTime);
    }
    
    function validateScheduledTime() {
        var scheduleTimeInput = $('#ecs-schedule-time');
        var selectedValue = scheduleTimeInput.val();
        
        if (!selectedValue) {
            return false;
        }
        
        // Parse the selected value as WordPress timezone
        // The input value is in format YYYY-MM-DDTHH:MM
        var parts = selectedValue.split('T');
        var dateParts = parts[0].split('-');
        var timeParts = parts[1].split(':');
        
        // Create a UTC date from the selected value (since input is in WP timezone which is UTC)
        var selectedTime = Date.UTC(
            parseInt(dateParts[0]), // year
            parseInt(dateParts[1]) - 1, // month (0-indexed)
            parseInt(dateParts[2]), // day
            parseInt(timeParts[0]), // hours
            parseInt(timeParts[1]), // minutes
            0 // seconds
        );
        
        // Get current WordPress time in UTC milliseconds
        var wpNow = getWordPressTime().getTime();
        
        // Add 2 minute buffer (120000 ms)
        var minTime = wpNow + 120000;
        
        console.log('Selected time (UTC ms):', selectedTime, 'Date:', new Date(selectedTime).toUTCString());
        console.log('WordPress now (UTC ms):', wpNow, 'Date:', new Date(wpNow).toUTCString());
        console.log('Min time (UTC ms):', minTime, 'Date:', new Date(minTime).toUTCString());
        console.log('Valid?', selectedTime >= minTime);
        
        if (selectedTime < minTime) {
            alert('You cannot schedule a message in the past or too soon. Please select a time at least 2 minutes in the future.');
            // Reset to WordPress time + 2 minutes
            var futureTime = new Date(wpNow + 120000);
            scheduleTimeInput.val(formatDateTimeLocal(futureTime));
            return false;
        }
        return true;
    }
    
    function sendMessage() {
        var message = $('#ecs_message_text').val();
        var recipients = getSelectedRecipients();
        var fromNumber = $('#ecs_from_number').val();
        var sendType = $('#ecs-send-type').val();
        var scheduleTime = $('#ecs-schedule-time').val();
        
        console.log('Send Type:', sendType);
        console.log('Schedule Time (raw):', scheduleTime);
        console.log('Schedule Time Input Element:', $('#ecs-schedule-time')[0]);
        
        if (!message || recipients.length === 0) {
            alert('Please enter a message and select recipients.');
            return;
        }
        
        // Validate scheduled time is not in the past
        if (sendType === 'scheduled') {
            if (!scheduleTime) {
                alert('Please select a schedule time.');
                return;
            }
            
            // Parse the selected value as WordPress timezone
            var parts = scheduleTime.split('T');
            var dateParts = parts[0].split('-');
            var timeParts = parts[1].split(':');
            
            // Create a UTC timestamp from the selected value
            var selectedTimeMs = Date.UTC(
                parseInt(dateParts[0]),
                parseInt(dateParts[1]) - 1,
                parseInt(dateParts[2]),
                parseInt(timeParts[0]),
                parseInt(timeParts[1]),
                0
            );
            
            // Get current WordPress time in milliseconds
            var wpNow = (typeof window.ecsTimeOffset !== 'undefined') 
                ? new Date().getTime() + window.ecsTimeOffset
                : new Date().getTime();
            
            // Add 2-minute buffer
            var minTime = wpNow + 120000;
            
            console.log('=== VALIDATION DEBUG ===');
            console.log('Selected:', new Date(selectedTimeMs).toUTCString());
            console.log('WP Now:', new Date(wpNow).toUTCString());
            console.log('Min Time:', new Date(minTime).toUTCString());
            console.log('Is valid?', selectedTimeMs >= minTime);
            console.log('======================');
            
            if (selectedTimeMs < minTime) {
                var wpNowDate = new Date(wpNow);
                var selectedDate = new Date(selectedTimeMs);
                alert('You cannot schedule a message in the past or too soon.\n\n' +
                      'WordPress current time: ' + wpNowDate.toUTCString() + '\n' +
                      'Selected time: ' + selectedDate.toUTCString() + '\n\n' +
                      'Please select a time at least 2 minutes in the future.');
                return;
            }
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
            error: function(xhr, status, error) {
                alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.data.message : error));
            }
        });
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
                action: 'ecs_cancel_scheduled',
                message_id: scheduledId,
                action_type: 'cancel',
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Scheduled message cancelled successfully!');
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
    
    function refreshAllStatuses() {
        $('.ecs-check-status').each(function() {
            var messageSid = $(this).data('message-sid');
            if (messageSid) {
                checkMessageStatus(messageSid);
            }
        });
    }
    
    function deleteMessage(messageId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_delete_message',
                message_id: messageId,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('tr[data-message-id="' + messageId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Failed to delete message');
                }
            },
            error: function() {
                alert('Error deleting message');
            }
        });
    }
    
    function deleteScheduledMessage(scheduledId) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_cancel_scheduled',
                message_id: scheduledId,
                action_type: 'delete',
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('tr[data-scheduled-id="' + scheduledId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Failed to delete scheduled message');
                }
            },
            error: function() {
                alert('Error deleting scheduled message');
            }
        });
    }
    
    function initBulkDeleteMessages() {
        // Select all checkbox (both top and header)
        $('#ecs-select-all-messages, #ecs-select-all-messages-header').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.ecs-message-checkbox').prop('checked', isChecked);
            $('#ecs-select-all-messages, #ecs-select-all-messages-header').prop('checked', isChecked);
            updateMessageSelectionCount();
        });
        
        // Individual checkbox change
        $(document).on('change', '.ecs-message-checkbox', function() {
            updateMessageSelectionCount();
            updateSelectAllCheckboxState('#ecs-select-all-messages', '.ecs-message-checkbox');
        });
        
        // Delete selected button
        $('#ecs-delete-selected-messages').on('click', function() {
            var selectedIds = $('.ecs-message-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                alert('Please select at least one message to delete');
                return;
            }
            
            if (confirm('Are you sure you want to delete ' + selectedIds.length + ' message(s)? This cannot be undone.')) {
                bulkDeleteMessages(selectedIds);
            }
        });
    }
    
    function initBulkDeleteScheduled() {
        // Select all checkbox (both top and header)
        $('#ecs-select-all-scheduled, #ecs-select-all-scheduled-header').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.ecs-scheduled-checkbox').prop('checked', isChecked);
            $('#ecs-select-all-scheduled, #ecs-select-all-scheduled-header').prop('checked', isChecked);
            updateScheduledSelectionCount();
        });
        
        // Individual checkbox change
        $(document).on('change', '.ecs-scheduled-checkbox', function() {
            updateScheduledSelectionCount();
            updateSelectAllCheckboxState('#ecs-select-all-scheduled', '.ecs-scheduled-checkbox');
        });
        
        // Delete selected button
        $('#ecs-delete-selected-scheduled').on('click', function() {
            var selectedIds = $('.ecs-scheduled-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                alert('Please select at least one scheduled message to delete');
                return;
            }
            
            if (confirm('Are you sure you want to delete ' + selectedIds.length + ' scheduled message(s)? This cannot be undone.')) {
                bulkDeleteScheduled(selectedIds);
            }
        });
    }
    
    function updateMessageSelectionCount() {
        var count = $('.ecs-message-checkbox:checked').length;
        $('#ecs-selected-messages-count').text(count > 0 ? count + ' selected' : '');
    }
    
    function updateScheduledSelectionCount() {
        var count = $('.ecs-scheduled-checkbox:checked').length;
        $('#ecs-selected-scheduled-count').text(count > 0 ? count + ' selected' : '');
    }
    
    function updateSelectAllCheckboxState(selectAllId, checkboxClass) {
        var total = $(checkboxClass).length;
        var checked = $(checkboxClass + ':checked').length;
        
        if (checked === 0) {
            $(selectAllId).prop('indeterminate', false).prop('checked', false);
        } else if (checked === total) {
            $(selectAllId).prop('indeterminate', false).prop('checked', true);
        } else {
            $(selectAllId).prop('indeterminate', true);
        }
    }
    
    function bulkDeleteMessages(messageIds) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_bulk_delete_messages',
                message_ids: messageIds,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to delete messages');
                }
            },
            error: function() {
                alert('Error deleting messages');
            }
        });
    }
    
    function bulkDeleteScheduled(scheduledIds) {
        $.ajax({
            url: ecs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ecs_bulk_delete_scheduled',
                scheduled_ids: scheduledIds,
                nonce: ecs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to delete scheduled messages');
                }
            },
            error: function() {
                alert('Error deleting scheduled messages');
            }
        });
    }
    
    // Initialize bulk actions
    $(document).ready(function() {
        initBulkActions();
    });
    
    // Bulk Actions Functionality
    function initBulkActions() {
        var $bulkAction = $('#ecs-bulk-action');
        var $bulkGroup = $('#ecs-bulk-group');
        var $applyButton = $('#ecs-apply-bulk-action');
        var $selectAllCheckbox = $('#ecs-select-all-checkbox');
        var $selectAllButton = $('#ecs-select-all');
        var $clearSelectionButton = $('#ecs-clear-selection');
        var $selectedCount = $('#ecs-selected-count');
        var $confirmModal = $('#ecs-bulk-confirm-modal');
        var $confirmCancel = $('#ecs-confirm-cancel');
        var $confirmProceed = $('#ecs-confirm-proceed');
        
        // Show/hide group selector based on bulk action
        $bulkAction.on('change', function() {
            if ($(this).val() === 'move-to-group') {
                $bulkGroup.show();
            } else {
                $bulkGroup.hide();
            }
        });
        
        // Update selected count
        function updateSelectedCount() {
            var count = $('.ecs-contact-checkbox:checked').length;
            $selectedCount.text(count + ' contacts selected');
            $applyButton.prop('disabled', count === 0);
        }
        
        // Handle individual checkbox changes
        $(document).on('change', '.ecs-contact-checkbox', function() {
            updateSelectedCount();
            updateSelectAllCheckbox();
        });
        
        // Handle select all checkbox
        $selectAllCheckbox.on('change', function() {
            $('.ecs-contact-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedCount();
        });
        
        // Update select all checkbox state
        function updateSelectAllCheckbox() {
            var totalCheckboxes = $('.ecs-contact-checkbox').length;
            var checkedCheckboxes = $('.ecs-contact-checkbox:checked').length;
            
            if (checkedCheckboxes === 0) {
                $selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                $selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
            } else {
                $selectAllCheckbox.prop('indeterminate', true);
            }
        }
        
        // Select all button
        $selectAllButton.on('click', function() {
            $('.ecs-contact-checkbox').prop('checked', true);
            $selectAllCheckbox.prop('checked', true);
            updateSelectedCount();
        });
        
        // Clear selection button
        $clearSelectionButton.on('click', function() {
            $('.ecs-contact-checkbox').prop('checked', false);
            $selectAllCheckbox.prop('checked', false);
            updateSelectedCount();
        });
        
        // Apply bulk action
        $applyButton.on('click', function() {
            var selectedContacts = $('.ecs-contact-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedContacts.length === 0) {
                alert('Please select at least one contact.');
                return;
            }
            
            var action = $bulkAction.val();
            if (!action) {
                alert('Please select a bulk action.');
                return;
            }
            
            if (action === 'move-to-group') {
                var groupId = $bulkGroup.val();
                if (!groupId) {
                    alert('Please select a group.');
                    return;
                }
                
                showBulkConfirmModal(
                    'Move Contacts to Group',
                    'Are you sure you want to move ' + selectedContacts.length + ' contact(s) to the selected group?',
                    function() {
                        performBulkAction('move-to-group', selectedContacts, groupId);
                    }
                );
            } else if (action === 'delete') {
                showBulkConfirmModal(
                    'Delete Contacts',
                    'Are you sure you want to delete ' + selectedContacts.length + ' contact(s)? This action cannot be undone.',
                    function() {
                        performBulkAction('delete', selectedContacts);
                    }
                );
            }
        });
        
        // Show confirmation modal
        function showBulkConfirmModal(title, message, callback) {
            $('#ecs-confirm-title').text(title);
            $('#ecs-confirm-message').text(message);
            $confirmModal.show();
            
            $confirmProceed.off('click').on('click', function() {
                $confirmModal.hide();
                callback();
            });
        }
        
        // Hide modal on cancel
        $confirmCancel.on('click', function() {
            $confirmModal.hide();
        });
        
        // Hide modal on outside click
        $confirmModal.on('click', function(e) {
            if (e.target === this) {
                $confirmModal.hide();
            }
        });
        
        // Perform bulk action
        function performBulkAction(action, contactIds, groupId) {
            $.ajax({
                url: ecs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ecs_bulk_action_contacts',
                    bulk_action: action,
                    contact_ids: contactIds,
                    group_id: groupId,
                    nonce: ecs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'An error occurred.');
                    }
                },
                error: function() {
                    alert('An error occurred while performing the bulk action.');
                }
            });
        }
        
        // Initialize
        updateSelectedCount();
    }
});
