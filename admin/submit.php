<?php
include 'config.php';

// Initialiser les variables pour éviter les erreurs
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validation et nettoyage des entrées
        $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $auteur = filter_input(INPUT_POST, 'auteur', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $annee = filter_input(INPUT_POST, 'annee', FILTER_VALIDATE_INT);
        $type_document = filter_input(INPUT_POST, 'type_document', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        // Champs spécifiques selon le type de document
        $serie = filter_input(INPUT_POST, 'serie', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $matiere = filter_input(INPUT_POST, 'matiere', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $pays = filter_input(INPUT_POST, 'pays', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $concours = filter_input(INPUT_POST, 'concours', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Validation du fichier
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
            $fichier = $_FILES['fichier']['name'];
            $target_dir = "uploads/";
            
            // Validation de l'extension
            $fileType = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
            $allowedTypes = array("pdf");
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Seuls les fichiers PDF sont autorisés.");
            }

            // Génération d'un nom de fichier unique
            $newFileName = uniqid() . "." . $fileType;
            $newFilePath = $target_dir . $newFileName;

            // Création du répertoire si nécessaire
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Déplacement du fichier
            if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $newFilePath)) {
                throw new Exception("Erreur lors du téléchargement du fichier.");
            }

            // Préparation de la requête SQL
            $sql = "INSERT INTO documents (titre, auteur, annee, type_document, serie, matiere, pays, concours, fichier, date_creation) 
                    VALUES (:titre, :auteur, :annee, :type_document, :serie, :matiere, :pays, :concours, :fichier, NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            // Exécution de la requête avec les paramètres
            $stmt->execute([
                ':titre' => $titre,
                ':auteur' => $auteur,
                ':annee' => $annee,
                ':type_document' => $type_document,
                ':serie' => $serie ?: null,
                ':matiere' => $matiere ?: null,
                ':pays' => $pays ?: 'International',
                ':concours' => $concours ?: null,
                ':fichier' => $newFileName
            ]);

            // Message de succès
            $success = "Document ajouté avec succès !";
            
            // Redirection possible
             header("Location: index.php?success=1");
             exit();
        } else {
            throw new Exception("Veuillez sélectionner un fichier PDF valide.");
        }
    } catch (PDOException $e) {
        // Log de l'erreur pour l'administrateur
        error_log("Erreur base de données: " . $e->getMessage());
        $error = "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Liste des matières
$matieres_list = [
    "mathematiques" => "Mathématiques",
    "physique_chimie" => "Physique-Chimie",
    "svt" => "SVT",
    "histoire_geo" => "Histoire-Géographie",
    "francais" => "Français",
    "philosophie" => "Philosophie",
    "anglais" => "Anglais",
    "informatique" => "Informatique",
    "economie" => "Économie"
];

// Liste des pays
$pays_list = [
    'Tchad', 'Burkina Faso', 'Cameroun', 'Congo', 'Côte d\'Ivoire', 
    'Gabon', 'Guinée', 'Mali', 'Niger', 'Sénégal', 'Togo', 'International'
];

// Liste des concours
$concours_list = [
    'CAFOP', 'ENS', 'Fonction Publique', 'INFAS', 'INPHB', 'ENA', 'Autre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Publier un document sur ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 700px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        .error-message {
            color: #dc3545;
            font-weight: bold;
        }
        .success-message {
            color: #28a745;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h3 class="text-center mb-4">Publier un document</h3>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form action="submit.php" method="post" enctype="multipart/form-data" id="documentForm">
                <!-- Type de document -->
                <div class="mb-3">
                    <label for="type_document" class="form-label">Type de document :</label>
                    <select name="type_document" id="type_document" class="form-select" required>
                        <option value="">Sélectionnez un type</option>
                        <option value="livre">Livre</option>
                        <option value="type_bac">Type Bac</option>
                        <option value="fascicule">Fascicule</option>
                        <option value="cours">Cours</option>
                        <option value="sujet">Sujet/Concours</option>
                    </select>
                </div>

                <!-- Champs spécifiques -->
                <div class="mb-3 type-specific type-type_bac type-sujet" style="display:none;">
                    <label for="serie" class="form-label">Série :</label>
                    <select name="serie" id="serie" class="form-select">
                        <option value="">Sélectionnez une série</option>
                        <option value="A">A</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                    </select>
                </div>

                <!-- Matière pour Type Bac, Cours et certains documents -->
                <div class="mb-3 type-specific type-type_bac type-cours" style="display:none;">
                    <label for="matiere" class="form-label">Matière :</label>
                    <select name="matiere" id="matiere" class="form-select">
                        <option value="">Sélectionnez une matière</option>
                        <?php foreach ($matieres_list as $key => $value): ?>
                            <option value="<?= $key ?>"><?= $value ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pays pour fascicules et sujets -->
                <div class="mb-3 type-specific type-fascicule type-sujet" style="display:none;">
                    <label for="pays" class="form-label">Pays :</label>
                    <select name="pays" id="pays" class="form-select">
                        <option value="International">Sélectionnez un pays</option>
                        <?php foreach ($pays_list as $pays): ?>
                            <option value="<?= htmlspecialchars($pays) ?>"><?= htmlspecialchars($pays) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Concours pour type sujet -->
                <div class="mb-3 type-specific type-sujet" style="display:none;">
                    <label for="concours" class="form-label">Concours :</label>
                    <select name="concours" id="concours" class="form-select">
                        <option value="">Sélectionnez un concours</option>
                        <?php foreach ($concours_list as $concours): ?>
                            <option value="<?= htmlspecialchars($concours) ?>"><?= htmlspecialchars($concours) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Champs communs -->
                <div class="mb-3">
                    <label for="titre" class="form-label">Titre :</label>
                    <input type="text" name="titre" id="titre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="auteur" class="form-label">Auteur :</label>
                    <input type="text" name="auteur" id="auteur" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="annee" class="form-label">Année :</label>
                    <input type="number" name="annee" id="annee" class="form-control" min="1950" max="<?= date('Y') ?>" value="<?= date('Y') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="fichier" class="form-label">Fichier (PDF uniquement) :</label>
                    <input type="file" name="fichier" id="fichier" class="form-control" accept=".pdf" required>
                    <div class="form-text">Taille maximale : 10 Mo</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="conditions" id="conditions" class="form-check-input" required>
                    <label class="form-check-label" for="conditions">
                        J'accepte les conditions de publication et je certifie avoir les droits sur ce document
                    </label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Publier le document</button>
                </div>
            </form>
        </div>
        
        <div class="text-center mt-4">
            <p><a href="index.php" class="btn btn-outline-secondary">Retour à la bibliothèque</a></p>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type_document');
        const typeSpecificFields = document.querySelectorAll('.type-specific');
        
        // Fonction pour afficher/masquer les champs en fonction du type de document
        function toggleTypeFields() {
            const selectedType = typeSelect.value;
            
            // Masquer tous les champs spécifiques
            typeSpecificFields.forEach(field => {
                field.style.display = 'none';
            });
            
            // Afficher les champs appropriés selon le type sélectionné
            if (selectedType) {
                document.querySelectorAll('.type-' + selectedType).forEach(field => {
                    field.style.display = 'block';
                });
            }
        }
        
        // Exécuter au chargement
        toggleTypeFields();
        
        // Exécuter à chaque changement de type
        typeSelect.addEventListener('change', toggleTypeFields);
        
        // Validation du formulaire
        const form = document.getElementById('documentForm');
        form.addEventListener('submit', function(e) {
            const conditions = document.getElementById('conditions');
            if (!conditions.checked) {
                e.preventDefault();
                alert('Vous devez accepter les conditions de publication avant de soumettre votre document.');
            }
            
            // Vérification de la taille du fichier (10 Mo max)
            const fichierInput = document.getElementById('fichier');
            if (fichierInput.files.length > 0) {
                const fileSize = fichierInput.files[0].size / 1024 / 1024; // taille en Mo
                if (fileSize > 10) {
                    e.preventDefault();
                    alert('Le fichier est trop volumineux. La taille maximale autorisée est de 10 Mo.');
                }
            }
        });
    });
    </script>
</body>
</html>