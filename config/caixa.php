<?php

return [

    'base_url' => env('CAIXA_LOTTERY_BASE_URL', 'https://servicebus2.caixa.gov.br/portaldeloterias/api'),

    'timeout' => (int) env('CAIXA_LOTTERY_TIMEOUT', 15),

    'retries' => (int) env('CAIXA_LOTTERY_RETRIES', 3),

    'retry_sleep_ms' => (int) env('CAIXA_LOTTERY_RETRY_SLEEP_MS', 300),

    // Delay between sequential requests during backfill, to avoid hammering
    // an undocumented, unrated third-party endpoint.
    'backfill_sleep_ms' => (int) env('CAIXA_LOTTERY_BACKFILL_SLEEP_MS', 250),

    // Draws happen in Brasília time regardless of the app timezone; used by
    // the sync schedule and by draw-resolution logic that compares against
    // the ~20h draw time.
    'draw_timezone' => env('CAIXA_LOTTERY_DRAW_TIMEZONE', 'America/Sao_Paulo'),

    'draw_cutoff_hour' => (int) env('CAIXA_LOTTERY_DRAW_CUTOFF_HOUR', 20),

    'user_agent' => env(
        'CAIXA_LOTTERY_USER_AGENT',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36'
    ),

];
