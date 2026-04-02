<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Enum;

enum ProviderType: string
{
    case Meta = 'meta';
    case Google = 'google';
    case Discord = 'discord';
    case Brevo = 'brevo';
    case Custom = 'custom';
}
