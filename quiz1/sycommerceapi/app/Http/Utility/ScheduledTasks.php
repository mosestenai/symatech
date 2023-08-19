<?php

namespace App\Http\Utility;

use App\Models\Deletedaccounts;


class ScheduledTasks
{
    // //delete accounts permanently that have exceeded thirty days since they were deleted
    public function __invoke()
    {
       
        Deletedaccounts::whereDate("deletiondate", "<", now())->delete();
        // Deletedaccounts::truncate();
    
    }
}
