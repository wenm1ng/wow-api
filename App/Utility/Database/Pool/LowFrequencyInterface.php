<?php
declare(strict_types=1);

namespace App\Utility\Database\Pool;

interface LowFrequencyInterface
{
    public function __construct(?Pool $pool = null);

    public function isLowFrequency(): bool;
}
