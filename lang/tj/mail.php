<?php

declare(strict_types=1);

/*
 * Tajik e-mail strings (ТЗ §6.4.3, §14). Keep the key sets identical across
 * lang/{tj,ru,en}/mail.php — guarded by InterfaceDictionaryTest.
 */

return [
    'alert' => [
        'subject_prefix' => 'КҲФ',
        'level' => 'Сатҳи хатар',
        'auto_notice' => 'Ин огоҳонии худкори Кумитаи ҳолатҳои фавқулодда ва мудофиаи граждании Ҷумҳурии Тоҷикистон мебошад.',
        'unsubscribe' => 'Барои лағви обуна ба ин [пайванд](:url) гузаред.',
    ],
    'subscription' => [
        'subject' => 'Тасдиқи обуна — КҲФ',
        'heading' => 'Тасдиқи обуна',
        'greeting' => 'Салом! Шумо ба огоҳониҳои Кумитаи ҳолатҳои фавқулодда ва мудофиаи граждании Ҷумҳурии Тоҷикистон обуна шудед.',
        'action_intro' => 'Барои фаъол кардани обуна суроғаи почтаи электронии худро тасдиқ кунед:',
        'action' => 'Тасдиқи обуна',
        'ignore' => 'Агар шумо обуна нашуда бошед, ин мактубро нодида гиред.',
        'thanks' => 'Бо эҳтиром,',
    ],
];
