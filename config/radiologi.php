<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Radiology Image Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL of the SIMRS Khanza webapps folder that serves radiology images
    | (e.g. http://192.168.1.10/webapps/radiologi). Khanza's `gambar_radiologi`
    | table stores only the path relative to it in `lokasi_gambar`. Leave empty
    | to hide radiology images in the portal.
    |
    */
    'image_base_url' => env('RADIOLOGI_IMAGE_BASE_URL'),

];
