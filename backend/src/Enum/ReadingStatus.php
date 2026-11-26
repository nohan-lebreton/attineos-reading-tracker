<?php

namespace App\Enum;

enum ReadingStatus: string
{
    case ToRead = 'to_read';
    case Reading = 'reading';
    case Read = 'read';
    case Abandoned = 'abandoned';
}
