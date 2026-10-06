<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The finance app was renamed Strongbox → Caboose; carry each role's access over. */
    public function up(): void
    {
        $this->swap('strongbox', 'caboose');
    }

    public function down(): void
    {
        $this->swap('caboose', 'strongbox');
    }

    private function swap(string $from, string $to): void
    {
        foreach (DB::table('roles')->get(['id', 'perms']) as $role) {
            $perms = json_decode($role->perms, true) ?: [];
            if (in_array($from, $perms, true)) {
                DB::table('roles')->where('id', $role->id)->update(['perms' => json_encode(array_values(array_unique(array_map(fn ($p) => $p === $from ? $to : $p, $perms))))]);
            }
        }
    }
};
