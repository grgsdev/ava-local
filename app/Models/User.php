<?php

namespace App\Models;

use App\Models\AbstractContracts\User as AbstractContractsUser;

class User extends AbstractContractsUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
}
