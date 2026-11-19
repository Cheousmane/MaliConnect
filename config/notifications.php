<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canaux de diffusion des notifications
    |--------------------------------------------------------------------------
    |
    | Le canal « database » alimente le centre de notifications. Le canal
    | « mail » est pris en charge par le socle ApplicationNotification ; le
    | canal « push » pourra être ajouté via un canal dédié sans modifier les
    | classes concrètes.
    |
    */

    'channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NOTIFICATION_CHANNELS', 'database'))
    ))),

];
