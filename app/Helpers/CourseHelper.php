<?php

use Illuminate\Support\Facades\Http;

function createPremiumAccess($data)
{
    try {
        $url = env('URL_SERVICE_COURSE') . 'api/my-courses/premium';
        $http = Http::post($url, $data);
        $data = $http->json();
        $data['http_code'] = $http->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'service course unavailable'
        ];
    }
}
