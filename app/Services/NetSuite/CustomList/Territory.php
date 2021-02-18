<?php

namespace App\Services\NetSuite\CustomList;

class Territory {

    private $response;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        $this->response = [
            1 => 'Territory #4',
            2 => 'Territory #1',
            3 => 'Territory #2',
            4 => 'Territory #3',
            5 => 'Default Round-Robin',
            9 => 'Territory #5',
            10 => 'Territory #6'
        ];
    }

    public function getRequest()
    {
        return $this->response;
    }
}
