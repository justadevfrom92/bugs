<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\User;

/** Adding and removing products (AutoPay, Paperless Billing, Peak Perks, …) on an account. */
class Products
{
    public static function add(Customer $c, string $product, ?User $by = null): bool
    {
        if ($c->hasProduct($product)) {
            return false;
        }
        CustomerProduct::create(['customer_id' => $c->id, 'product' => $product, 'user_id' => $by?->id]);
        if ($field = config('corral.product_fields.'.$product)) {
            $c->update([$field => true]);
        }

        return true;
    }

    public static function remove(Customer $c, string $product): bool
    {
        $rows = CustomerProduct::where('customer_id', $c->id)->where('product', $product)->whereNull('removed_at')->get();
        foreach ($rows as $row) {
            $row->update(['removed_at' => now()]);
        }
        if ($field = config('corral.product_fields.'.$product)) {
            $c->update([$field => false]);
        }

        return $rows->isNotEmpty();
    }
}
