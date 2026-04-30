<?php

declare(strict_types=1);

namespace App\Application\Bus;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
