<?php

namespace App\DataTransferObjects;

readonly class CsvImportResult
{
    /**
     * @param  string[]  $errors
     */
    public function __construct(
        public int $imported,
        public array $errors,
    ) {}
}
