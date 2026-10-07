<?php

namespace App\Models;

use App\Models\Concerns\RecordsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A product on an account. The model name depends on the product (config/corral.php). */
class CustomerProduct extends Model
{
    use RecordsHistory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'removed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** [model name, log group] for History. */
    public function historyName(): array
    {
        return [config('corral.products.'.$this->product, 'ItemProduct_model'), 'Products'];
    }

    public function historyLabel(): string
    {
        return 'Product '.$this->product;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
