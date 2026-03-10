<?php

namespace Gandalf\Component\Security\Action\AskPasswordReset;

use Cortex\ValueObject\Email;

class Command
{
    public function __construct(
        public readonly Email $email,
    ) {
    }
}
