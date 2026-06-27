<?php

namespace App\Enums;

enum TranslationAudience: string
{
    case GENERAL = 'GENERAL';
    case TECHNICAL = 'TECHNICAL';
    case NONTECHNICAL = 'NON-TECHNICAL';
}
