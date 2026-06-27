<?php

namespace App\Enums;

enum GlossaryRule: string
{
    case NEVER_TRANSLATE = 'Never translate';
    case ALWAYS_TRANSLATE = 'Always translate';
}
