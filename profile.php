<?php
session_start();
include 'bd/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Récupérer les données de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

// Traitement du formulaire de mise à jour du profil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
    $website = filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL);
    
    try {
        $sql = "UPDATE utilisateurs SET 
                nom = :nom, 
                prenom = :prenom, 
                bio = :bio, 
                telephone = :telephone, 
                website = :website
                WHERE id = :id";
        
        $update = $pdo->prepare($sql);
        $update->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':bio' => $bio,
            ':telephone' => $telephone,
            ':website' => $website,
            ':id' => $user_id
        ]);
        
        // Mise à jour des données de session
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_prenom'] = $prenom;
        
        $success = "Votre profil a été mis à jour avec succès";
        
        // Rafraîchir les données utilisateur
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
    }
}

// Traitement du formulaire de changement de mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérifier si le mot de passe actuel est correct
    if (!password_verify($current_password, $user['mot_de_passe'])) {
        $pwd_error = "Le mot de passe actuel est incorrect";
    } elseif ($new_password !== $confirm_password) {
        $pwd_error = "Les nouveaux mots de passe ne correspondent pas";
    } elseif (strlen($new_password) < 8) {
        $pwd_error = "Le nouveau mot de passe doit contenir au moins 8 caractères";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateurs SET mot_de_passe = :mot_de_passe WHERE id = :id";
            $update = $pdo->prepare($sql);
            $update->execute([
                ':mot_de_passe' => $hashed_password,
                ':id' => $user_id
            ]);
            
            $pwd_success = "Votre mot de passe a été modifié avec succès";
        } catch(PDOException $e) {
            $pwd_error = "Erreur lors de la modification du mot de passe: " . $e->getMessage();
        }
    }
}

// Traitement de l'upload d'avatar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $avatar_error = "Seuls les formats JPEG, PNG et GIF sont acceptés";
        } elseif ($_FILES['avatar']['size'] > $max_size) {
            $avatar_error = "La taille de l'image ne doit pas dépasser 2MB";
        } else {
            $upload_dir = 'uploads/avatars/';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                // Supprimer l'ancien avatar s'il existe
                if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                    unlink($user['avatar']);
                }
                
                // Mettre à jour la base de données
                $update = $pdo->prepare("UPDATE utilisateurs SET avatar = :avatar WHERE id = :id");
                $update->execute([
                    ':avatar' => $target_file,
                    ':id' => $user_id
                ]);
                
                $avatar_success = "Votre avatar a été mis à jour avec succès";
                
                // Rafraîchir les données utilisateur
                $stmt->execute([':id' => $user_id]);
                $user = $stmt->fetch();
            } else {
                $avatar_error = "Une erreur est survenue lors de l'upload de l'avatar";
            }
        }
    } else {
        $avatar_error = "Veuillez sélectionner une image";
    }
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Profil - ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6bfa;
            --secondary-color: #764ba2;
            --text-color: #333;
            --light-bg: #f9f9f9;
            --transition: all 0.3s ease;
            --border-color: #e0e0e0;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem 2rem;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        
        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .cover-photo {
            height: 200px;
            width: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        
        .profile-info {
            position: relative;
            z-index: 2;
            margin-top: 120px;
            display: flex;
            align-items: flex-end;
        }
        
        .avatar-container {
            position: relative;
            margin-right: 2rem;
        }
        
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            background-color: #e0e0e0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .avatar-edit {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .profile-details h2 {
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .profile-details .text-muted {
            font-size: 1rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #777;
        }
        
        .profile-tabs {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .nav-tabs {
            border-bottom: none;
            padding: 0 1rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            font-weight: 500;
            color: #777;
            padding: 1rem 1.5rem;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background: transparent;
        }
        
        .tab-content {
            padding: 2rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .profile-card-header {
            background: var(--light-bg);
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .profile-card-title {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .profile-card-body {
            padding: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(74, 107, 250, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .publication-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .publication-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .publication-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .publication-title {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .publication-body {
            padding: 1.5rem;
        }
        
        .publication-footer {
            padding: 1rem 1.5rem;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .publication-date {
            font-size: 0.875rem;
            color: #777;
        }
        
        .publication-actions a {
            margin-left: 1rem;
            color: #777;
            transition: var(--transition);
        }
        
        .publication-actions a:hover {
            color: var(--primary-color);
        }
        
        .avatar-upload-modal .modal-header {
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
        }
        
        .avatar-upload-modal .modal-body {
            padding: 2rem;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid var(--border-color);
            overflow: hidden;
            margin: 0 auto 1.5rem;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .custom-file-upload {
            display: inline-block;
            padding: 0.5rem 1rem;
            cursor: pointer;
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: var(--transition);
            text-align: center;
            width: 100%;
        }
        
        .custom-file-upload:hover {
            background: #e0e0e0;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-text {
            color: #777;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .avatar-container {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-tabs .nav-link {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="accueil_utilisateur.php"><i class="fas fa-book-open me-2"></i>ZanouTek</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="acueil_utilisateur.php"><i class="fas fa-home me-1"></i> Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="acueil_utilisateur.php"><i class="fas fa-compass me-1"></i> Explorer</a>
                    </li>
                s
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $user['prenom'] . ' ' . $user['nom']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="profil.php"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                            <li><a class="dropdown-item" href="parametres.php"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Profile Container -->
    <div class="container profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="cover-photo"></div>
            <div class="profile-info">
                
                <div class="profile-details">
                    <h2><?php echo $user['prenom'] . ' ' . $user['nom']; ?></h2>
                    <br />
                    <p class="text-muted"><?php echo $user['email']; ?></p>
                   
                </div>
            </div>
        </div>
        
        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
               
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                        <i class="fas fa-user me-2"></i>Profil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-lock me-2"></i>Sécurité
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="profileTabsContent">
                <!-- Publications Tab -->
                <div class="tab-pane fade show active" id="publications" role="tabpanel">
                    
                    
                 
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $publication['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirmer la suppression</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Êtes-vous sûr de vouloir supprimer la publication "<?php echo $publication['titre']; ?>" ?</p>
                                            <p class="text-danger"><small>Cette action est irréversible.</small></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <a href="supprimer_publication.php?id=<?php echo $publication['id']; ?>" class="btn btn-danger">Supprimer</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                      
                     
                 
                </div>
                
                <!-- Profile Tab -->
                <div class="tab-pane fade" id="profile" role="tabpanel">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">Informations personnelles</h5>
                        </div>
                        <div class="profile-card-body">
                            <?php if(isset($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo $user['nom']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo $user['prenom']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                                    <div class="form-text">L'adresse email ne peut pas être modifiée.</div>
                                </div>
                                
                              
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo $user['telephone']; ?>">
                                    </div>
                                   
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="profile-card mb-4">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">Changer mon mot de passe</h5>
                        </div>
                        <div class="profile-card-body">
                            <?php if(isset($pwd_success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $pwd_success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($pwd_error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $pwd_error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-lock me-2"></i>Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">Sécurité du compte</h5>
                        </div>
                        <div class="profile-card-body">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-0">Authentification à deux facteurs</h6>
                                        <p class="text-muted mb-0">Ajouter une couche de sécurité supplémentaire</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-0">Notifications de connexion</h6>
                                        <p class="text-muted mb-0">Recevoir des alertes en cas de nouvelle connexion</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="loginNotifications" checked>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <a href="#" class="btn btn-outline-danger">
                                    <i class="fas fa-trash-alt me-2"></i>Supprimer mon compte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Avatar Upload Modal -->
    <div class="modal fade avatar-upload-modal" id="avatarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer votre photo de profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if(isset($avatar_error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $avatar_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="avatar-preview">
                            <img id="avatarPreview" src="<?php echo !empty($user['avatar']) ? $user['avatar'] : 'assets/images/default-avatar.png'; ?>" alt="Avatar Preview">
                        </div>
                        
                        <div class="mb-3">
                            <label for="avatar" class="custom-file-upload">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Choisir une image
                            </label>
                            <input type="file" name="avatar" id="avatar" accept="image/*" style="display: none;">
                            <div class="form-text text-center">Formats acceptés: JPG, PNG, GIF. Taille max: 2MB</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="upload_avatar" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview uploaded avatar
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatarPreview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Tab navigation via URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const tabId = hash.substring(1);
                const tab = new bootstrap.Tab(document.querySelector(`#profileTabs button[data-bs-target="#${tabId}"]`));
                tab.show();
            }
        });
    </script>
</body>
</html>