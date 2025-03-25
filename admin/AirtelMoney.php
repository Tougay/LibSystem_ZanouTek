<?php
// config.php
define('AIRTEL_CLIENT_ID', 'd48b3db3-b82a-4000-b50e-29a8bd23cd27');
define('AIRTEL_CLIENT_SECRET', '****************************');
define('AIRTEL_BASE_URL', 'https://openapiuat.airtel.africa');

function getAirtelToken() {
    $url = AIRTEL_BASE_URL . '/auth/oauth2/token';
    
    $data = [
        'client_id' => AIRTEL_CLIENT_ID,
        'client_secret' => AIRTEL_CLIENT_SECRET,
        'grant_type' => 'client_credentials'
    ];

    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        throw new Exception("Erreur cURL : " . $err);
    }

    $result = json_decode($response, true);
    
    if (!isset($result['access_token'])) {
        throw new Exception("Échec d'authentification : " . 
            (isset($result['error_description']) ? $result['error_description'] : "Erreur inconnue"));
    }

    return $result['access_token'];
}

function initiateAirtelPayment($phone, $amount, $reference) {
    $token = getAirtelToken();
    
    $url = AIRTEL_BASE_URL . '/merchant/v1/payments/';
    
    $data = [
        'reference' => $reference,
        'subscriber' => [
            'country' => 'TD',
            'currency' => 'XAF',
            'msisdn' => $phone
        ],
        'transaction' => [
            'amount' => $amount,
            'country' => 'TD',
            'currency' => 'XAF',
            'id' => $reference
        ]
    ];

    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'X-Country: TD',
            'X-Currency: XAF'
        ],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        throw new Exception("Erreur cURL : " . $err);
    }

    return json_decode($response, true);
}