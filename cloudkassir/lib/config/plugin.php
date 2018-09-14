<?php
return array(
    'name'     => 'Онлайн-касса Cloudkassir',
    'img'      => 'img/cloudkassir.png',
    'description' => 'Интеграция с онлайн-кассой CloudKassir',
    'version'  => '1.0.0',
    'vendor'   => 'cloudpayments',
    'handlers' =>
        array(
            'order_action.*' => 'processOrderStatus',
        ),
);
