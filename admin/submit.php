<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validation et nettoyage des entrées
        $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_STRING);
        $auteur = filter_input(INPUT_POST, 'auteur', FILTER_SANITIZE_STRING);
        $universite = filter_input(INPUT_POST, 'universite', FILTER_SANITIZE_STRING);
        $annee = filter_input(INPUT_POST, 'annee', FILTER_VALIDATE_INT);
        $type_document = filter_input(INPUT_POST, 'type_document', FILTER_SANITIZE_STRING);
        $serie = filter_input(INPUT_POST, 'serie', FILTER_SANITIZE_STRING);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_SANITIZE_STRING);
        $matiere = filter_input(INPUT_POST, 'matiere', FILTER_SANITIZE_STRING);

        // Validation du fichier
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
            $fichier = $_FILES['fichier']['name'];
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($fichier);
            
            // Validation de l'extension
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowedTypes = array("pdf", "doc", "docx");
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Seuls les fichiers PDF, DOC et DOCX sont autorisés.");
            }

            // Génération d'un nom de fichier unique
            $newFileName = uniqid() . "." . $fileType;
            $newFilePath = $target_dir . $newFileName;

            // Déplacement du fichier
            if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $newFilePath)) {
                throw new Exception("Erreur lors du téléchargement du fichier.");
            }

            // Préparation de la requête SQL
            $sql = "INSERT INTO documents (titre, auteur, universite, annee, type_document, serie, niveau, matiere, fichier) 
                    VALUES (:titre, :auteur, :universite, :annee, :type_document, :serie, :niveau, :matiere, :fichier)";
            
            $stmt = $pdo->prepare($sql);
            
            // Exécution de la requête avec les paramètres
            $stmt->execute([
                ':titre' => $titre,
                ':auteur' => $auteur,
                ':universite' => $universite,
                ':annee' => $annee,
                ':type_document' => $type_document,
                ':serie' => $serie ?: null,
                ':niveau' => $niveau ?: null,
                ':matiere' => $matiere ?: null,
                ':fichier' => $newFileName
            ]);

            // Redirection en cas de succès
            header("Location: index.php?success=1");
            exit();
        }
    } catch (PDOException $e) {
        // Log de l'erreur pour l'administrateur
        error_log("Erreur base de données: " . $e->getMessage());
        $error = "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Publier un document sur ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h3 class="text-center mb-3">Publier un document</h3>
            <form action="submit.php" method="post" enctype="multipart/form-data" id="documentForm">
                
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
                <!-- Série (visible uniquement pour certains types) -->
            <div class="mb-3" id="serieDiv" style="display:none;">
                <label for="serie" class="form-label">Série :</label>
                <select name="serie" id="serie" class="form-select">
                    <option value="">Sélectionnez une série</option>
                    <option value="A">A</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="G">G</option>
                </select>
            </div>

            <!-- Niveau (pour séries A et F) -->
            <div class="mb-3" id="niveauDiv" style="display:none;">
                <label for="niveau" class="form-label">Niveau :</label>
                <select name="niveau" id="niveau" class="form-select">
                    <option value="">Sélectionnez un niveau</option>
                    <option value="A">A</option>
                    <option value="F">F</option>
                </select>
            </div>

            <!-- Matière -->
            <div class="mb-3" id="matiereDiv" style="display:none;">
                <label for="matiere" class="form-label">Matière :</label>
                <select name="matiere" id="matiere" class="form-select">
                    <option value="">Sélectionnez une matière</option>
                    <option value="math">Mathématiques</option>
                    <option value="pc">Physique-Chimie</option>
                    <option value="svt">SVT</option>
                    <option value="arabe">Arabe</option>
                </select>
            </div>
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
                    <input type="number" name="annee" id="annee" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label for="fichier" class="form-label">Fichier :</label>
                    <input type="file" name="fichier" id="fichier" class="form-control" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" name="conditions" id="conditions" class="form-check-input" required>
                    <label class="form-check-label" for="conditions">
                        J'accepte les <a href="conditions_de_publication.html">conditions de publication</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Publier le document</button>
            </form>
        </div>
    </div>

    <footer class="text-center mt-4">
        <p>Si vous rencontrez des difficultés, contactez le <a href="mailto:@gmail.com">webmaster</a></p>
        <p><a href="index.php">Retour page d'accueil</a></p>
    </footer>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type_document');
        const serieDiv = document.getElementById('serieDiv');
        const niveauDiv = document.getElementById('niveauDiv');
        const matiereDiv = document.getElementById('matiereDiv');

        typeSelect.addEventListener('change', function() {
            // Réinitialiser l'affichage
            serieDiv.style.display = 'none';
            niveauDiv.style.display = 'none';
            matiereDiv.style.display = 'none';

            // Afficher les champs appropriés selon le type de document
            switch(this.value) {
                case 'type_bac':
                    serieDiv.style.display = 'block';
                    matiereDiv.style.display = 'block';
                    break;
                case 'fascicule':
                case 'cours':
                    matiereDiv.style.display = 'block';
                    break;
                case 'sujet':
                    serieDiv.style.display = 'block';
                    break;
            }
        });

        // Gérer l'affichage du niveau pour les séries A et F
        const serieSelect = document.getElementById('serie');
        serieSelect.addEventListener('change', function() {
            if (this.value === 'A') {
                niveauDiv.style.display = 'block';
            } else {
                niveauDiv.style.display = 'none';
            }
        });

        // Validation du formulaire
        const form = document.getElementById('documentForm');
        form.addEventListener('submit', function(e) {
            const conditions = document.getElementById('conditions');
            if (!conditions.checked) {
                e.preventDefault();
                alert('Vous devez accepter les conditions de publication avant de soumettre votre document.');
            }
        });
    });
    </script>
</body>
</html>