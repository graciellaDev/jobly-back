<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Model;
trait ModelTrait
{
    public function replaceFields(array $list, Model &$model): void
    {
        if (!empty($list)) {
            foreach ($list as $key => $newKey) {
                $model[$newKey] = $model[$key];
                unset($model[$key]);
            }
        }
    }
}
