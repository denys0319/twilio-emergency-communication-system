# Emergency Communication System - User Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Getting Started](#getting-started)
3. [Managing Contacts & Groups](#managing-contacts--groups)
4. [Sending Messages](#sending-messages)
5. [Understanding Message Statuses](#understanding-message-statuses)
6. [Message History](#message-history)
7. [Scheduled Messages](#scheduled-messages)
8. [Troubleshooting](#troubleshooting)

---

## System Overview

The Emergency Communication System is a WordPress plugin that enables administrators to send SMS messages to contacts using Twilio's API. The system provides:

- ✅ Contact and group management
- ✅ Immediate and scheduled message sending
- ✅ Real-time delivery tracking
- ✅ Message history with filtering
- ✅ Bulk operations

### System Architecture

```
WordPress Plugin
    ↓
Twilio API (SMS Sending)
    ↓
WordPress Database (Contact & Message Storage)
    ↓
Message History & Status Tracking
```

---

## Getting Started

### 1. Initial Setup

1. **Install Plugin**: Activate the Emergency Communication System plugin
2. **Install Dependencies**: Run `composer install` in the plugin directory
3. **Configure Twilio**:
   - Go to **Emergency Communication > Settings**
   - Enter your Twilio Account SID
   - Enter your Twilio Auth Token
   - Enter your Twilio Phone Number (in E.164 format: +1234567890)
   - Click **Save Settings**

### 2. Twilio Account Setup

If you don't have a Twilio account:
1. Sign up at [twilio.com](https://www.twilio.com/try-twilio)
2. From the Twilio Console Dashboard, get your:
   - Account SID
   - Auth Token
3. Purchase a phone number: **Console > Phone Numbers > Buy a number**

---

## Managing Contacts & Groups

### Contact Management

#### Adding Individual Contacts
1. Go to **Emergency Communication > Contacts**
2. Click **Add Contact**
3. Fill in:
   - **Phone Number** (required, E.164 format)
   - **Name** (required)
   - **Group** (optional)
4. Click **Save Contact**

#### Bulk Upload from CSV
1. Go to **Emergency Communication > Contacts**
2. Click **Upload Contacts**
3. Prepare CSV file with format:
   ```
   phone,name,group
   +12345678901,John Doe,Emergency Team
   +12345678902,Jane Smith,Staff
   ```
4. Select file and click **Upload**
5. Review imported contacts

#### Editing/Deleting Contacts
- **Edit**: Click pencil icon next to contact
- **Delete**: Click trash icon or use bulk delete
- **Bulk Actions**: Select multiple contacts and choose delete

### Group Management

#### Creating Groups
1. Go to **Emergency Communication > Contact Groups**
2. Click **Add Group**
3. Enter:
   - **Group Name** (required)
   - **Description** (optional)
4. Click **Save Group**

#### Assigning Contacts to Groups
- When adding/editing a contact, select a group from dropdown
- Or use bulk upload with group column in CSV

---

## Sending Messages

### Compose Alert Page

Go to **Emergency Communication > Compose Alert**

### Message Options

#### 1. Message Content
- Enter your message text in the text area
- Character count is displayed (SMS standard: 160 characters per segment)
- Multiple segments will be sent as a single message

#### 2. Select Recipients

**Option A: Send to Groups**
- Select one or more contact groups
- All contacts in selected groups will receive the message

**Option B: Send to Individual Contacts**
- Search and select individual contacts
- Can select multiple contacts

**Option C: Custom Phone Numbers**
- Enter phone numbers directly (E.164 format)
- Useful for one-time sends to non-contacts

**Mix and Match**: You can select groups, individuals, AND custom numbers in the same message

#### 3. Choose Sending Method

**Send Immediately**
- Message is sent right away
- All recipients receive the message within seconds

**Schedule for Later**
- Select date and time for future sending
- Time is in your WordPress timezone
- Minimum: 2 minutes in the future
- Scheduled messages appear in "Scheduled Messages" section

#### 4. Preview & Send

- Click **Preview Message** to see recipients and message content
- Click **Send Message** to send immediately
- Or click **Schedule Message** if scheduling for later

---

## Understanding Message Statuses

### Message History Statuses (Individual Messages)

When messages are sent to Twilio, each individual message goes through these statuses:

| Status | Meaning | Description |
|--------|---------|-------------|
| **Queued** | In Queue | Message is queued in Twilio and waiting to be sent |
| **Sending** | Being Sent | Twilio is currently sending the message to the carrier |
| **Sent** | Sent to Carrier | Message successfully delivered to the carrier (not yet to device) |
| **Delivered** | ✅ Delivered | Message successfully delivered to recipient's device |
| **Undelivered** | ❌ Not Delivered | Carrier could not deliver (phone off, out of range, etc.) |
| **Failed** | ❌ Failed | Message failed to send (invalid number, blocked, etc.) |

**Status Flow:**
```
Queued → Sending → Sent → Delivered ✅
                         → Undelivered ❌
                         → Failed ❌
```

### Scheduled Message Statuses

Scheduled messages represent the overall scheduled job:

| Status | Meaning | Description |
|--------|---------|-------------|
| **Pending** | Waiting | Scheduled job waiting for the scheduled time |
| **Sent** | Completed | Scheduled job executed and messages were sent |
| **Cancelled** | Cancelled | User cancelled the scheduled job before sending |
| **Failed** | System Error | System failed to execute the scheduled job |

**Important:** When a scheduled message is sent (status: `sent`), it creates individual message records in Message History with their own Twilio statuses.

### Key Differences

| Scheduled Message (Job) | Message History (Individual SMS) |
|------------------------|----------------------------------|
| Represents one scheduled job | Each recipient gets one message record |
| Statuses: pending, sent, cancelled, failed | Statuses: queued, sending, sent, delivered, undelivered, failed |
| One entry per scheduled send | Multiple entries (one per recipient) |

**Example:**
```
Scheduled Message #123 (Status: sent)
   ├─> Individual Message to John (Status: delivered) ✅
   ├─> Individual Message to Jane (Status: delivered) ✅
   ├─> Individual Message to Bob (Status: failed) ❌
   └─> Individual Message to Alice (Status: delivered) ✅
```

---

## Message History

### Viewing Message History

Go to **Emergency Communication > Message History**

### Features

#### Filter by Status
Use the filter dropdown to view:
- **All Statuses**: Show all messages
- **Queued**: Messages waiting to be sent
- **Sending**: Messages currently being sent
- **Sent**: Messages sent to carrier
- **Delivered**: Successfully delivered messages
- **Undelivered**: Messages that could not be delivered
- **Failed**: Messages that failed to send

#### Bulk Actions
- **Select All**: Check all messages on current page
- **Delete Selected**: Remove multiple messages at once

#### Individual Actions
- **Check Status**: Manually refresh status from Twilio
- **Delete**: Remove individual message
- **View Full**: See complete message text

#### Refresh Status Button
- Automatically updates statuses for all messages less than 24 hours old
- Useful for getting latest delivery confirmations

### Understanding the Display

**Message Table Columns:**
- **Recipient**: Name and phone number
- **Message**: Preview of message text (click "View Full" for complete text)
- **Status**: Current delivery status (with color coding)
- **Sent**: When message was sent to Twilio
- **Delivered**: When message was delivered to device (if delivered)
- **Error**: Error code and message (if failed)
- **Actions**: Buttons for check status and delete

**Status Badge Colors:**
- 🟦 Blue: queued, sending
- 🟢 Green: sent, delivered
- 🔴 Red: failed, undelivered

### Pagination
- 20 messages per page
- Use navigation at bottom to view more

---

## Scheduled Messages

### Viewing Scheduled Messages

Scheduled messages appear at the bottom of **Emergency Communication > Message History**

### Features

#### Filter by Status
- **All Statuses**: Show all scheduled messages
- **Pending**: Messages waiting to be sent
- **Sent**: Messages that have been sent
- **Cancelled**: Messages cancelled by user
- **Failed**: Messages that failed to execute

#### Bulk Actions (Available when messages exist)
- **Select All**: Check all scheduled messages
- **Delete Selected**: Remove multiple scheduled messages

#### Individual Actions
- **Cancel**: Mark pending message as cancelled (prevents sending)
- **Delete**: Permanently remove the scheduled message record

### Scheduled Message Details

**Table Columns:**
- **Message**: Preview of message content
- **Recipients**: Number of recipients
- **Scheduled For**: Date and time message will be sent
- **Status**: Current status of scheduled job
- **Created**: When the scheduled message was created
- **Actions**: Cancel (pending only) or Delete

### Important Notes

1. **Cancel vs Delete**:
   - **Cancel**: Marks as cancelled, keeps for record-keeping
   - **Delete**: Permanently removes from database

2. **Only Pending Messages Can Be Cancelled**:
   - Once sent, cancelled, or failed, the Cancel button disappears
   - You can still delete sent/cancelled/failed messages for cleanup

3. **Automatic Cleanup**:
   - Old scheduled messages (sent, cancelled, failed) are automatically deleted after 30 days
   - This keeps the database clean

4. **Time Zone**:
   - All times are in your WordPress timezone
   - Check **Settings > General** in WordPress admin

---

## Troubleshooting

### Messages Not Sending

**Check:**
1. ✅ Twilio credentials are correct in Settings
2. ✅ Twilio account has sufficient balance
3. ✅ Phone numbers are in E.164 format (+1234567890)
4. ✅ "From" phone number is active in your Twilio account

**Common Errors:**
- **Error 21211**: Invalid 'To' phone number
- **Error 21608**: Phone number is not permitted
- **Error 21610**: Attempt to send to unsubscribed recipient

### Messages Stuck in "Queued" or "Sending"

**Normal Behavior:**
- Messages can take 1-30 seconds to move through queued → sending → sent
- Carrier delays can extend this

**If Stuck for >5 minutes:**
1. Click **Refresh Status** button
2. Or click **Check Status** on individual message
3. Check Twilio logs in your Twilio Console

### Scheduled Messages Not Sending

**Check:**
1. ✅ WordPress cron is working (check with WP Crontrol plugin)
2. ✅ Scheduled time is in the future (minimum 2 minutes)
3. ✅ Status is "pending" (not cancelled or failed)

**Test WordPress Cron:**
```bash
# Manually trigger WordPress cron
wp cron event run --due-now
```

### Status Not Updating

**Auto-Update:**
- Message History page automatically updates statuses for messages < 24 hours old
- This happens every time you view the page

**Manual Update:**
1. Click **Refresh Status** button for all messages
2. Or click **Check Status** on individual messages

### File Upload Issues

**CSV Format:**
```
phone,name,group
+12345678901,John Doe,Emergency Team
+12345678902,Jane Smith,Staff
```

**Requirements:**
- ✅ First row must be header (phone,name,group)
- ✅ Phone numbers must be in E.164 format
- ✅ Name is required for each contact
- ✅ Group is optional

### Permission Issues

**Ensure:**
- You are logged in as Administrator
- Plugin is properly activated
- Database tables were created (check with phpMyAdmin)

---

## Best Practices

### 1. Message Content
- ✅ Keep messages clear and concise
- ✅ Include action items or next steps
- ✅ Add contact info for follow-up
- ✅ Test with yourself first

### 2. Recipient Management
- ✅ Keep contact lists up to date
- ✅ Remove old/invalid numbers
- ✅ Use groups for organized sending
- ✅ Verify numbers before mass sending

### 3. Scheduling
- ✅ Schedule at least 5 minutes in advance
- ✅ Consider recipient time zones
- ✅ Test scheduled messages with small groups first
- ✅ Review scheduled messages before send time

### 4. Monitoring
- ✅ Check message history regularly
- ✅ Review failed messages and fix issues
- ✅ Monitor Twilio usage and balance
- ✅ Archive old messages to keep database clean

### 5. Compliance
- ✅ Only send to opted-in recipients
- ✅ Include opt-out instructions
- ✅ Follow local SMS regulations
- ✅ Respect quiet hours (avoid late night sends)

---

## Database Tables

The plugin creates the following WordPress database tables:

| Table | Purpose |
|-------|---------|
| `wp_ecs_contact_groups` | Contact groups |
| `wp_ecs_contacts` | Individual contacts |
| `wp_ecs_message_templates` | Message templates |
| `wp_ecs_messages` | Individual message history with Twilio statuses |
| `wp_ecs_scheduled_messages` | Scheduled message jobs |

---

## Support & Updates

For support, feature requests, or bug reports, please contact your system administrator.

---

## Quick Reference Card

### Phone Number Format
✅ Correct: `+12345678901`
❌ Wrong: `123-456-7890`, `(123) 456-7890`

### Message Limits
- Standard SMS: 160 characters
- With special characters: 70 characters
- Long messages automatically split into segments

### Status Quick Reference
- 🟦 **Queued/Sending**: In progress
- 🟢 **Sent/Delivered**: Success
- 🔴 **Failed/Undelivered**: Error

### Schedule Message Requirements
- ⏰ Minimum: 2 minutes in future
- 🕒 Time zone: WordPress timezone
- 📅 Can schedule weeks/months in advance

---

**Last Updated**: October 2025
**Version**: 1.0

