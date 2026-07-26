<?php

use App\Jobs\RunLotteryBackfillJob;

test('the job throws when the underlying command fails', function () {
    $job = new RunLotteryBackfillJob('does-not-exist', 1, 5);

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);
});
