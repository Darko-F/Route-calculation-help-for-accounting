<?php
declare(strict_types=1);

// Keep the public endpoint at /downloads/download.php while sharing the
// validation and streaming implementation maintained at the project root.
require dirname(__DIR__) . '/download.php';
