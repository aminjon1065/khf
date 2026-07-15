<?php

declare(strict_types=1);

/*
 * English e-mail strings (ТЗ §6.4.3, §14). Keep the key sets identical across
 * lang/{tj,ru,en}/mail.php — guarded by InterfaceDictionaryTest.
 */

return [
    'alert' => [
        'subject_prefix' => 'CoES',
        'level' => 'Hazard level',
        'view' => 'View details on the portal',
        'auto_notice' => 'This is an automated alert from the Committee of Emergency Situations and Civil Defense of the Republic of Tajikistan.',
        'unsubscribe' => 'To unsubscribe from notifications, follow this [link](:url).',
    ],
    'subscription' => [
        'subject' => 'Subscription confirmation — CoES',
        'heading' => 'Subscription confirmation',
        'greeting' => 'Hello! You have subscribed to notifications from the Committee of Emergency Situations and Civil Defense of the Republic of Tajikistan.',
        'action_intro' => 'To activate your subscription, please confirm your email address:',
        'action' => 'Confirm subscription',
        'ignore' => 'If you did not subscribe, simply ignore this email.',
        'thanks' => 'Thank you,',
    ],
];
