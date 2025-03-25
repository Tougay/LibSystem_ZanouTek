<?php
include 'config.php';

// Configuration Airtel Money
define('AIRTEL_CLIENT_ID', 'd48b3db3-b82a-4000-b50e-29a8bd23cd27');
define('AIRTEL_CLIENT_SECRET', '****************************');
define('AIRTEL_BASE_URL', 'https://openapiuat.airtel.africa');

function getAirtelToken() {
    $url = AIRTEL_BASE_URL . '/auth/oauth2/token';
    
    // Vérification du format des identifiants
    if (empty(AIRTEL_CLIENT_ID) || empty(AIRTEL_CLIENT_SECRET)) {
        throw new Exception("Client ID ou Client Secret manquant");
    }
    
    // Formatage précis de la requête comme spécifié par Airtel
    $data = json_encode([
        'client_id' => trim(AIRTEL_CLIENT_ID),
        'client_secret' => trim(AIRTEL_CLIENT_SECRET),
        'grant_type' => 'client_credentials'
    ]);

    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($data)
        ],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_VERBOSE => true
    ]);

    // Capture la sortie verbose pour le debug
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    // Log détaillé
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("Verbose log: " . $verboseLog);
    error_log("Response: " . $response);

    curl_close($curl);

    if ($err) {
        throw new Exception("Erreur cURL : " . $err);
    }

    $result = json_decode($response, true);
    
    if (!isset($result['access_token'])) {
        error_log("Réponse complète : " . print_r($result, true));
        throw new Exception("Échec d'authentification : " . 
            (isset($result['error_description']) ? $result['error_description'] : json_encode($result)));
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
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_VERBOSE => true
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    // Log de debug
    $info = curl_getinfo($curl);
    error_log("Airtel Payment Response: " . $response);
    error_log("Airtel Payment Info: " . print_r($info, true));

    curl_close($curl);

    if ($err) {
        throw new Exception("Erreur cURL : " . $err);
    }

    return json_decode($response, true);
}

try {
    // Vérification des données POST
    if (!isset($_POST['phone']) || !isset($_POST['amount']) || !isset($_POST['document_id'])) {
        throw new Exception("Données de paiement manquantes");
    }

    // Nettoyage et validation du numéro de téléphone
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    if (strlen($phone) !== 8) {
        throw new Exception("Numéro de téléphone invalide");
    }
    $phone = "235" . $phone; // Ajouter l'indicatif pays

    // Récupération des informations du document
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$_POST['document_id']]);
    $document = $stmt->fetch();

    if (!$document) {
        throw new Exception("Document non trouvé");
    }

    // Création d'une référence unique
    $reference = 'PAY-' . time() . '-' . rand(1000, 9999);

    // Initialisation du paiement
    $response = initiateAirtelPayment($phone, $_POST['amount'], $reference);

    if (!isset($response['data']['transaction']['id'])) {
        $errorMsg = isset($response['error']['message']) 
            ? $response['error']['message'] 
            : json_encode($response);
        throw new Exception("Erreur lors de l'initialisation du paiement: " . $errorMsg);
    }

    // Enregistrement de la transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (reference, document_id, phone, amount, payment_id, status) 
                          VALUES (?, ?, ?, ?, ?, 'PENDING')");
    $stmt->execute([
        $reference,
        $_POST['document_id'],
        $phone,
        $_POST['amount'],
        $response['data']['transaction']['id']
    ]);

    // Redirection vers la page de statut
    header("Location: check_payment.php?ref=" . $reference);
    exit();

} catch (Exception $e) {
    error_log("Erreur de paiement: " . $e->getMessage());
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}