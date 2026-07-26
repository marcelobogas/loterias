<?php

namespace App\Enums;

enum LotteryFreshnessEnum: string
{
    case UpToDate = 'up_to_date';

    // A draw was expected by now (per draw_days_of_week + cutoff hour) but
    // Caixa hasn't published it yet — not our sync's fault.
    case AwaitingCaixa = 'awaiting_caixa';

    // Caixa already published a contest we haven't synced locally yet.
    case Behind = 'behind';
}
