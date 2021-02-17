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
        // $timestamp = time();
        // $model->crc = $timestamp;
        // $model->lastcrc = $timestamp;
    }

    /**
     * Handle the Model "updating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updating(Model $model)
    {
        // $timestamp = time();
        // if(!$model->crc) {
        //     $model->crc = $timestamp;
        // }
        // $model->lastcrc = $timestamp;
    }
}
