<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Developer = 'developer';
    case Admin = 'admin';
}
