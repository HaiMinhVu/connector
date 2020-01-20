<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ModelObserver
{
    /**
     * Handle the Model "creating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function creating(Model $model)
    {
        $model->crc = time();
    }

    /**
     * Handle the Model "updating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updating(Model $model)
    {
        $timestamp = time();
        // dd($model->crc);
        if(!$model->crc) {
            $model->crc = $timestamp;
        }
        $model->lastcrc = $timestamp;
    }
}
