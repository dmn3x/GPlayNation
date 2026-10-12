<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case EWallet = 'e_wallet';
    case VirtualAccount = 'virtual_account';
    case CreditCard = 'credit_card';
}
