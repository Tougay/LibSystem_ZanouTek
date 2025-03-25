<?php
require_once ('config.php');

$id = $_GET['edit'];
$sql = "SELECT * FROM memoires WHERE id='$id'";
$stmt = $pdo->prepare($sql);
$stmt->execute();

if (isset($_POST['modifier'])) {
    $id = $_GET['edit'];

    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $universite = $_POST['universite'];
    $annee = $_POST['annee'];
    $domaine = $_POST['domaine'];
    $fichier = $_FILES['fichier']['name'];

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($fichier);

    if (move_uploaded_file($_FILES['fichier']['tmp_name'], $target_file)) {
        $sql2 = "UPDATE `memoires` SET titre=:titre, auteur=:auteur, universite=:universite, annee=:annee, domaine=:domaine, fichier=:fichier WHERE id='$id'";
        $stmt2 = $pdo->prepare($sql2);

        $stmt2->bindParam(":titre", $titre);
        $stmt2->bindParam(":auteur", $auteur);
        $stmt2->bindParam(":universite", $universite);
        $stmt2->bindParam(":annee", $annee);
        $stmt2->bindParam(":domaine", $domaine);
        $stmt2->bindParam(":fichier", $fichier);
        $stmt2->execute();

        header("Location: acueil.php");
    } else {
        echo "Désolé, une erreur s'est produite lors du téléchargement de votre fichier.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Mémoire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        #header {
            background-color: #003366;
            color: white;
            padding: 15px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        #header img {
            height: 50px;
        }
        #header h1 {
            font-size: 24px;
            margin: 0 10px;
            letter-spacing: 1px;
        }
        #header nav {
            display: flex;
            align-items: center;
        }
        #header nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            padding: 8px 16px;
            border-radius: 3px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        #header nav a:hover {
            background-color: #00509e;
            transform: scale(1.1);
        }
        #header form {
            display: flex;
            align-items: center;
        }
        #header input[type="text"] {
            padding: 5px;
            font-size: 14px;
            margin-right: 10px;
            border: none;
            border-radius: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        #header button {
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 3px;
            background-color: #00509e;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #header button:hover {
            background-color: #007bff;
        }
        #main {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-container {
            background-color: white;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            margin-top: 0;
            color: #003366;
        }
        .form-group label {
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group button {
            background-color: #00509e;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-group button:hover {
            background-color: #007bff;
        }
        #footer {
            text-align: center;
            padding: 20px;
            background-color: #003366;
            color: white;
            margin-top: 40px;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }
        #footer a {
            color: #17a2b8;
            text-decoration: none;
        }
        #footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div id="header">
        <img src="5570061.jpg" alt="Mémoire Online Logo" width="200" height="50">
        <h1>DépotMemo</h1>
        <nav>
            <a href="#publication">Publication</a>
            <a href="#categories">Catégories</a>
            <a href="#new">Nouveau</a>
        </nav>
        <form method="get" action="">
            <input type="text" name="search" placeholder="Rechercher sur le site">
            <button type="submit">Recherche</button>
        </form>
    </div>

    <div id="main">
        <div class="form-container">
            <h2>Modifier un mémoire</h2>
            <?php foreach ($stmt as $memoire) { ?>
                <form action="" method="POST" enctype="multipart/form-data" id="memoireForm">
                    <div class="form-group">
                        <label for="titre">Titre du mémoire :</label>
                        <input type="text" name="titre" id="titre" value="<?php echo $memoire['titre'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="auteur">Nom de l'auteur :</label>
                        <input type="text" name="auteur" id="auteur" value="<?php echo $memoire['auteur'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="universite">Université :</label>
                        <input type="text" name="universite" id="universite" value="<?php echo $memoire['universite'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="annee">Année :</label>
                        <input type="number" name="annee" id="annee" value="<?php echo $memoire['annee'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="domaine">Domaine :</label>
                        <select name="domaine" id="domaine" required>
                            <option value="Arts, Philosophie et Sociologie" <?php echo ($memoire['domaine'] == "Arts, Philosophie et Sociologie") ? "selected" : "" ?>>Arts, Philosophie et Sociologie</option>
                            <option value="Communication et Journalisme" <?php echo ($memoire['domaine'] == "Communication et Journalisme") ? "selected" : "" ?>>Communication et Journalisme</option>
                            <option value="Enseignement" <?php echo ($memoire['domaine'] == "Enseignement") ? "selected" : "" ?>>Enseignement</option>
                            <option value="Informatique et Télécommunications" <?php echo ($memoire['domaine'] == "Informatique et Télécommunications") ? "selected" : "" ?>>Informatique et Télécommunications</option>
                            <option value="Sport" <?php echo ($memoire['domaine'] == "Sport") ? "selected" : "" ?>>Sport</option>
                            <option value="Biologie et Médecine" <?php echo ($memoire['domaine'] == "Biologie et Médecine") ? "selected" : "" ?>>Biologie et Médecine</option>
                            <option value="Droit et Sciences Politiques" <?php echo ($memoire['domaine'] == "Droit et Sciences Politiques") ? "selected" : "" ?>>Droit et Sciences Politiques</option>
                            <option value="Géographie" <?php echo ($memoire['domaine'] == "Géographie") ? "selected" : "" ?>>Géographie</option>
                            <option value="Ressources humaines" <?php echo ($memoire['domaine'] == "Ressources humaines") ? "selected" : "" ?>>Ressources humaines</option>
                            <option value="Tourisme" <?php echo ($memoire['domaine'] == "Tourisme") ? "selected" : "" ?>>Tourisme</option>
                            <option value="Commerce et Marketing" <?php echo ($memoire['domaine'] == "Commerce et Marketing") ? "selected" : "" ?>>Commerce et Marketing</option>
                            <option value="Économie et Finance" <?php echo ($memoire['domaine'] == "Économie et Finance") ? "selected" : "" ?>>Économie et Finance</option>
                            <option value="Histoire" <?php echo ($memoire['domaine'] == "Histoire") ? "selected" : "" ?>>Histoire</option>
                            <option value="Sciences" <?php echo ($memoire['domaine'] == "Sciences") ? "selected" : "" ?>>Sciences</option>
                            <option value="Rapports de stage" <?php echo ($memoire['domaine'] == "Rapports de stage") ? "selected" : "" ?>>Rapports de stage</option>
                             <option value="Autre" <?php echo ($memoire['domaine'] == "Autre") ? "selected" : "" ?>>Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fichier">Fichier à publier :</label>
                        <input type="file" name="fichier" id="fichier" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="modifier" class="btn btn-success">Modifier</button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>

    <div id="footer">
        <p>© DépotMemo 2023-2024 - Pour tout problème de consultation ou si vous voulez publier un mémoire: <a href="mailto:depotmemo@gmail.com">depotmemo@gmail.com</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('memoireForm');

            form.addEventListener('submit', function (e) {
                const fichier = document.getElementById('fichier').value;
                if (fichier === '') {
                    e.preventDefault();
                    alert('Vous devez choisir un fichier à publier.');
                }
            });
        });
    </script>
</body>
</html>
