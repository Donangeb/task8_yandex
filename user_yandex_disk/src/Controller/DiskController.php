<?php

namespace App\Controller;

use App\Service\YandexDiskManager;

class DiskController
{
    public function handleRequest(): void
    {
        $service = new YandexDiskManager($_SESSION['access_token']);
        $service->process();
    }
}
