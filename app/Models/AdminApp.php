<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/** An admin app created from the launcher. Menu rows are {heading, label, route|url}. */
class AdminApp extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['menu' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /** Menu rows grouped by heading, in the shape the admin layout uses. */
    public function menuSections(): array
    {
        $sections = [];
        foreach ($this->menu ?? [] as $row) {
            $sections[$row['heading'] ?: 'Links'][] = [
                'label' => $row['label'],
                'url' => ! empty($row['route']) && Route::has($row['route']) ? route($row['route']) : ($row['url'] ?? '#'),
                'external' => empty($row['route']) && preg_match('#^https?://#', $row['url'] ?? ''),
            ];
        }

        return $sections;
    }
}
