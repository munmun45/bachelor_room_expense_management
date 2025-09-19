# Group Leadership System Guide

## Overview
The Group Leadership System allows room admins to assign group leaders who can manage group activities and even remove room admins from groups.

## ⚠️ Important Rule: Only One Leader Per Group
- **Single Leadership**: Each group can have only ONE leader at a time
- **Auto-Replacement**: When a new leader is assigned, the previous leader automatically becomes a regular member
- **Admin Priority**: When a room admin joins a group, they automatically become the leader

## Roles and Permissions

### Room Admin
- **Full Control**: Can add/remove members, assign leaders, create settlements
- **Group Management**: Can manage all groups in their room
- **Leader Assignment**: Can assign or remove group leaders
- **Auto-Leadership**: Automatically becomes leader when joining any group

### Group Leader
- **Group Control**: Can add/remove group members
- **Settlement Creation**: Can create settlements for the group
- **Admin Removal**: Can remove room admins from the group (special power)
- **Leadership Management**: Can assign new leaders (replaces current leader)
- **Single Leader Rule**: Only one leader per group at any time

### Regular Member
- **Basic Access**: Can view group, send messages, add expenses
- **No Management**: Cannot add/remove members or create settlements

## How to Use

### For Room Admins:
1. **Assign Group Leader**:
   - Go to group members modal
   - Click the crown icon next to any member
   - Confirm the assignment (previous leader will be replaced)

2. **Remove Group Leader**:
   - Click the user-minus icon next to a leader
   - Confirm the removal

3. **Auto-Leadership**:
   - When you join any group, you automatically become the leader
   - This replaces any existing leader in that group

### For Group Leaders:
1. **Add Members**:
   - Click the users icon in header
   - Click the plus icon to add members
   - Select members from room to add

2. **Remove Members**:
   - Click the user-minus icon next to any member
   - Confirm the removal

3. **Remove Room Admin** (Special Power):
   - Click the shield icon next to room admins
   - Confirm the removal (this removes admin from group only)

4. **Create Settlements**:
   - Click the calculator icon in header
   - Set date range and settlement type
   - Create settlement

5. **Assign New Leader**:
   - Click the crown icon next to any member
   - Confirm the assignment (you will lose leadership)

## Visual Indicators

- **👑 Leader Badge**: Purple badge with crown icon
- **🛡️ Admin Badge**: Green badge with shield icon
- **👤 Regular Member**: No special badge

## Important Notes

- **Single Leader Rule**: Only ONE member can be group leader at a time
- **Auto-Replacement**: Assigning a new leader automatically removes leadership from the current leader
- **Admin Priority**: Room admins automatically become leaders when they join groups
- **Leader Power**: Group leaders can remove room admins from groups, but admins retain their room admin status
- **Self-Protection**: Leaders cannot remove their own leadership (only room admins can)
- **Notifications**: All leadership changes are logged in group messages
- **Settlement Access**: Both admins and leaders can create settlements

## Database Changes

The system adds an `is_leader` field to the `group_members` table:
```sql
ALTER TABLE `group_members` ADD COLUMN `is_leader` TINYINT(1) DEFAULT 0 AFTER `user_id`;
```

## Security Features

- **Permission Checks**: All actions verify user permissions
- **Transaction Safety**: Database operations use transactions
- **Audit Trail**: All changes are logged in group messages
- **Confirmation Dialogs**: Important actions require confirmation
