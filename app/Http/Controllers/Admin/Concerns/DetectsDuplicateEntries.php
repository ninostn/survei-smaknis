<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\QueryException;

trait DetectsDuplicateEntries
{
    /**
     * Hanya duplicate entry MySQL (driver code 1062) yang dianggap
     * pelanggaran unique. SQLSTATE 23000 juga mencakup foreign key,
     * jadi SQLSTATE saja tidak cukup.
     */
    protected function isDuplicateEntry(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
