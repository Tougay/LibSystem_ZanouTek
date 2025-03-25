<?php
include 'config.php';
include 'AirtelMoney.php';

if (isset($_GET['ref'])) {
    $reference = $_GET['ref'];
    
    // Récupérer la transaction
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->execute([$reference]);
    $transaction = $stmt->fetch();
    
    if ($transaction) {
        $airtel = new AirtelMoney();
        $status = $airtel->checkPaymentStatus($transaction['payment_id']);
        
        // Mettre à jour le statut
        if (isset($status['data']['transaction']['status'])) {
            $newStatus = $status['data']['transaction']['status'];
            $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE reference = ?");
            $stmt->execute([$newStatus, $reference]);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statut du paiement - ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body text-center">
                <h3>Statut du paiement</h3>
                <?php if (isset($status['data']['transaction']['status']) && $status['data']['transaction']['status'] === 'SUCCESS'): ?>
                    <div class="alert alert-success">
                        Paiement réussi ! Vous pouvez maintenant télécharger votre document.
                        <a href="download.php?ref=<?= $reference ?>" class="btn btn-primary mt-3">Télécharger le document</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Paiement en cours de traitement...
                        <p>Veuillez vérifier votre téléphone et valider la transaction.</p>
                        <small>La page se rafraîchira automatiquement dans 10 secondes...</small>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-secondary mt-3">Retour à l'accueil</a>
            </div>
        </div>
    </div>
    
    <?php if (!isset($status['data']['transaction']['status']) || $status['data']['transaction']['status'] !== 'SUCCESS'): ?>
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 10000);
    </script>
    <?php endif; ?>
</body>
</html>