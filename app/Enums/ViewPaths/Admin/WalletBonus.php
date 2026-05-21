<?php

namespace App\Enums\ViewPaths\Admin;

enum WalletBonus
{
    const INDEX = [
        URI => '/',
        VIEW => 'admin-views.wallet-bonus.index'
    ];

    const ADD = [
        URI => 'store',
        VIEW => 'admin-views.wallet-bonus.index'
    ];

    const UPDATE = [
        URI => 'edit',
        VIEW => 'admin-views.wallet-bonus.edit'
    ];

    const DELETE = [
        URI => 'delete',
        VIEW => ''
    ];

    const UPDATE_STATUS = [
        URI => 'status',
        VIEW => ''
    ];


}
