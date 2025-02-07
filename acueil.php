<?php
include 'config.php';

// Suppression de document
if (isset($_GET['sup']) && is_numeric($_GET['sup'])) {
    $id = $_GET['sup'];
    $sqlDelete = "DELETE FROM documents WHERE id = :id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([':id' => $id]);
    header("Location: acueil.php?success=2");
    exit();
}

// Message de succès
$message = isset($_GET['success']) 
    ? ($_GET['success'] == 1 
        ? '<div class="alert alert-success">Document ajouté avec succès !</div>'
        : '<div class="alert alert-warning">Document supprimé avec succès !</div>')
    : '';

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
    <title>Bibliothèque ZanouTek</title>
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
        <h2 class="text-center my-4">Bibliothèque ZanouTek</h2>
        

        <section id="publication">
        <h2>PUBLIEZ VOTRE DOCUMENT</h2>
        <p>Envoyez votre document rapidement et sans inscription en <a href="submit.php">cliquant ici</a></p>
    </section>
        <?= $message ?>

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

                                        <div class="d-flex justify-content-between">
                                            <a href="uploads/<?= $doc['fichier'] ?>" 
                                               target="_blank" class="btn btn-sm btn-primary">
                                                Voir le document
                                            </a>
                                            <a href="acueil.php?sup=<?= $doc['id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Supprimer ce document ?');">
                                                Supprimer
                                            </a>
                                        </div>
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