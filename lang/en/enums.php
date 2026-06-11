<?php

declare(strict_types=1);

/*
 * English labels for the application enums (ТЗ §14). Keys are the enum backing values; keep the
 * key sets identical across lang/{tj,ru,en}/enums.php — guarded by InterfaceDictionaryTest.
 */

return [
    'alert_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'cancelled' => 'Cancelled',
    ],
    'appeal_category' => [
        'general' => 'General question',
        'complaint' => 'Complaint',
        'proposal' => 'Proposal',
        'gratitude' => 'Gratitude',
    ],
    'appeal_status' => [
        'new' => 'New',
        'in_progress' => 'In progress',
        'answered' => 'Answered',
        'closed' => 'Closed',
    ],
    'content_status' => [
        'draft' => 'Draft',
        'moderation' => 'Under review',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    'guide_audience' => [
        'general' => 'General public',
        'children' => 'For children',
    ],
    'document_type' => [
        'law' => 'Legislation',
        'regulation' => 'Regulations',
        'departmental' => 'Departmental documents',
        'plan' => 'Plans',
        'report' => 'Reports',
        'form' => 'Forms and templates',
    ],
    'hazard_level' => [
        'normal' => 'Normal',
        'elevated' => 'Heightened alert',
        'danger' => 'Danger',
        'critical' => 'Critical danger',
    ],
    'incident_status' => [
        'active' => 'Active',
        'controlled' => 'Under control',
        'resolved' => 'Resolved',
    ],
    'incident_type' => [
        'earthquake' => 'Earthquake',
        'mudflow' => 'Mudflow and flash flood',
        'flood' => 'Flood',
        'avalanche' => 'Avalanche',
        'landslide' => 'Landslide',
        'fire' => 'Fire',
        'glof' => 'Glacial lake outburst flood',
    ],
    'post_type' => [
        'news' => 'News',
        'press_release' => 'Press release',
        'announcement' => 'Announcement',
        'summary' => 'Operational summary',
    ],
    'role' => [
        'super-admin' => 'Super administrator',
        'moderator' => 'Moderator',
    ],
    'subscription_status' => [
        'pending' => 'Pending confirmation',
        'confirmed' => 'Confirmed',
        'unsubscribed' => 'Unsubscribed',
    ],
    'subscription_topic' => [
        'alerts' => 'Emergency alerts',
        'news' => 'News',
        'announcements' => 'Announcements',
    ],
];
