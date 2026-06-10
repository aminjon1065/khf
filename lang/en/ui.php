<?php

declare(strict_types=1);

/*
 * English interface dictionary shared with the React front end (ТЗ §14). Keep keys identical
 * across lang/{tj,ru,en}/ui.php; the client `t()` helper falls back to the key when a string
 * is missing.
 */

return [
    'site' => [
        'short_name' => 'CoES',
        'full_name' => 'Committee of Emergency Situations and Civil Defense under the Government of the Republic of Tajikistan',
    ],
    'nav' => [
        'home' => 'Home',
        'news' => 'News',
        'situation' => 'Situation',
        'map' => 'Map',
        'documents' => 'Documents',
        'reception' => 'Public Reception',
        'tourism' => 'Tourism',
        'subscribe' => 'Subscribe',
        'login' => 'Log in',
    ],
    'footer' => [
        'hotline' => 'Unified helpline',
        'sections' => 'Sections',
    ],
    'lang' => [
        'switch' => 'Change language',
    ],
    'errors' => [
        '403' => ['title' => 'Access denied', 'message' => 'You do not have permission to view this page.'],
        '404' => ['title' => 'Page not found', 'message' => 'The requested page does not exist or has been moved.'],
        '419' => ['title' => 'Session expired', 'message' => 'Please refresh the page and try again.'],
        '429' => ['title' => 'Too many requests', 'message' => 'Please wait a moment and try again.'],
        '500' => ['title' => 'Internal server error', 'message' => 'Something went wrong. We are already working on it.'],
        '503' => ['title' => 'Under maintenance', 'message' => 'The site is temporarily unavailable. Please check back later.'],
    ],
    'common' => [
        'back' => 'Back',
        'check' => 'Check',
        'close' => 'Close',
        'email' => 'Email',
        'emergency_map' => 'Emergency map',
        'find' => 'Search',
        'latest_news' => 'Latest news',
        'next' => 'Next',
        'no_publications' => 'No publications yet.',
        'operational_situation' => 'Operational situation',
        'reference_number' => 'Registration number',
        'track_status' => 'Track status',
    ],
    'home' => [
        'meta_title' => 'Home',
        'hero' => [
            'title' => 'Committee of Emergency Situations and Civil Defense',
            'subtitle' => 'Up-to-date information on threats and emergencies, safety guidelines, and public alerts for the Republic of Tajikistan.',
            'emergency_call' => 'Emergency call: 112',
        ],
        'quick_links' => [
            'emergency_phone' => 'Emergency phone',
            'safety_guides_label' => 'Safety guidelines',
            'safety_guides_hint' => 'How to act in an emergency',
            'subscribe_label' => 'Subscription',
            'subscribe_hint' => 'Threat notifications',
        ],
        'news' => [
            'view_all' => 'All news →',
        ],
    ],
    'news' => [
        'title' => 'News',
        'heading' => 'News and materials',
        'back_to_list' => '← Back to news list',
        'author' => 'Author: :author',
    ],
    'incidents' => [
        'subtitle' => 'Incidents and emergencies',
        'empty' => 'No registered incidents.',
    ],
    'map' => [
        'heading' => 'Interactive emergency map',
        'subtitle' => 'Active incidents in the territory of the Republic of Tajikistan',
    ],
    'documents' => [
        'title' => 'Documents',
        'empty' => 'No documents found.',
        'form' => [
            'search_placeholder' => 'Search by title…',
            'type_placeholder' => 'Type',
            'all_types' => 'All types',
        ],
    ],
    'appeals' => [
        'title' => 'Online reception',
        'subtitle' => 'Citizen appeals to the Committee of Emergency Situations',
        'track_existing' => 'Track a previously submitted appeal',
        'form' => [
            'category' => 'Category',
            'name' => 'Your name',
            'phone_optional' => 'Phone (optional)',
            'subject' => 'Subject',
            'message' => 'Message',
            'submit' => 'Submit appeal',
        ],
        'success' => [
            'title' => 'Appeal received',
            'reference_hint' => 'Your registration number for tracking the status:',
            'new_appeal' => 'New appeal',
        ],
        'track' => [
            'title' => 'Appeal tracking',
            'hint' => 'Enter the appeal registration number',
            'reference_placeholder' => 'OBR-2026-XXXXXX',
            'not_found' => 'No appeal found with this number.',
            'category_label' => 'Category: :category',
            'submitted_label' => 'Submitted: :created_at',
            'updated_label' => 'Updated: :updated_at',
        ],
    ],
    'tourism' => [
        'create' => [
            'page_title' => 'Tourist group registration',
            'subtitle' => 'Notify the mountain rescue service of your route for your safety',
            'success_page_title' => 'Application received',
            'success_heading' => 'Application registered',
            'reference_hint' => 'Registration number for tracking:',
            'new_application_button' => 'New application',
            'track_link' => 'Track application',
        ],
        'form' => [
            'leader_name' => 'Group leader',
            'leader_phone' => 'Phone',
            'leader_email' => 'Email (optional)',
            'participants_count' => 'Number of participants',
            'region' => 'Region',
            'region_placeholder' => 'Select a region',
            'region_none' => 'Not specified',
            'start_date' => 'Departure date',
            'end_date' => 'Return date',
            'route' => 'Route',
            'equipment' => 'Equipment and special notes (optional)',
            'submit' => 'Register group',
            'reference_placeholder' => 'TUR-2026-XXXXXX',
        ],
        'track' => [
            'title' => 'Application tracking',
            'hint' => 'Enter the tourist group application registration number',
            'not_found' => 'No application found with this number.',
            'route' => 'Route: :route',
        ],
    ],
    'subscribe' => [
        'title' => 'Subscription to notifications',
        'subtitle' => 'Receive emergency alerts and news by email',
        'form' => [
            'topics' => 'Topics',
            'region_optional' => 'Region (optional)',
            'all_regions' => 'All regions',
            'consent' => 'I consent to the processing of personal data and to receiving the newsletter.',
            'submit' => 'Subscribe',
        ],
        'status' => [
            'pending' => 'Check your email and confirm the subscription.',
            'confirmed' => 'Subscription confirmed. Thank you!',
            'unsubscribed' => 'You have unsubscribed from notifications.',
            'invalid' => 'The link is invalid or has expired.',
        ],
    ],
];
