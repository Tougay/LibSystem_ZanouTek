<?php
session_start();
include 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Types de documents
$types = [
    "livre" => ["titre" => "Livres", "icon" => "📚"],
    "fascicule" => ["titre" => "Fascicules", "icon" => "📖"],
    "cours" => ["titre" => "Cours", "icon" => "📝"],
    "sujet" => ["titre" => "Sujets/Concours", "icon" => "✏️"],
    "type_bac" => ["titre" => "Type Bac", "icon" => "🎓"]
];

// Récupérer les documents
$sql = "SELECT * FROM documents ORDER BY type_document, annee DESC";
$stmt = $pdo->query($sql);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Regrouper les documents par type
$groupedDocuments = [];
foreach ($documents as $doc) {
    $type = $doc['type_document'];
    $groupedDocuments[$type][] = $doc;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bibliothèque ZanouTek - Utilisateur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .document-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .document-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .document-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .document-details {
            padding: 15px;
            background-color: #f8f9fa;
        }
        .document-type-section {
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <h2>Bibliothèque ZanouTek</h2>
            <div>
                <span>Connecté en tant que: <?= htmlspecialchars($_SESSION['user_email']) ?></span>
                <a href="deconnexion.php" class="btn btn-danger btn-sm ml-2">Déconnexion</a>
            </div>
        </div>

        <div class="mb-4">
            <label for="typeFilter" class="form-label">Filtrer par type de document :</label>
            <select id="typeFilter" class="form-select">
                <option value="">Tous les documents</option>
                <?php foreach ($types as $typeKey => $typeInfo): ?>
                    <option value="<?= $typeKey ?>"><?= $typeInfo['titre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php foreach ($types as $typeKey => $typeInfo) : ?>
            <?php if (!empty($groupedDocuments[$typeKey])) : ?>
                <div class="document-type-section" data-type="<?= $typeKey ?>">
                    <h3 class="mb-3">
                        <span class="document-icon"><?= $typeInfo['icon'] ?></span>
                        <?= $typeInfo['titre'] ?>
                    </h3>

                    <div class="row">
                        <?php foreach ($groupedDocuments[$typeKey] as $doc) : ?>
                            <div class="col-md-4">
                                <div class="card document-card">
                                    <div class="document-details">
                                        <h5><?= $doc['titre'] ?></h5>
                                        <p>
                                            <strong>Auteur:</strong> <?= $doc['auteur'] ?><br>
                                            <strong>Année:</strong> <?= $doc['annee'] ?>
                                        </p>
                                        
                                        <?php if ($typeKey === 'type_bac'): ?>
                                            <p>
                                                <strong>Série:</strong> <?= $doc['serie'] ?? 'N/A' ?><br>
                                                <strong>Matière:</strong> <?= $doc['matiere'] ?? 'N/A' ?>
                                            </p>
                                        <?php endif; ?>

                                        <a href="uploads/<?= $doc['fichier'] ?>" 
                                           target="_blank" class="btn btn-sm btn-primary">
                                            Voir le document
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeFilter = document.getElementById('typeFilter');
        const documentSections = document.querySelectorAll('.document-type-section');

        typeFilter.addEventListener('change', function() {
            const selectedType = this.value;

            documentSections.forEach(section => {
                section.style.display = (selectedType === '' || section.dataset.type === selectedType) 
                    ? 'block' 
                    : 'none';
            });
        });
    });
    </script>
</body>
</html>