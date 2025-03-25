<?php 
session_start(); 
include 'bd/config.php';  

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'];
    
    $sql = "SELECT * FROM utilisateurs WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        header("Location: acueil_utilisateur.php");
        exit();
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="./style/style.css">

</head>
<body>
    <!-- Left sidebar with branding and features -->
    <div class="login-sidebar">
        <div class="animated-bg"></div>
        <div class="sidebar-content">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-book-open"></i> ZanouTek
                </div>
                <h1 class="tagline">Votre plateforme de publication documentaire</h1>
                <p>Partagez vos livres, concours et ressources pédagogiques en toute simplicité.</p>
            </div>
            
            <ul class="features-list">
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Publication illimitée de documents</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Organisation des ressources par catégories</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Partage sécurisé avec contrôle d'accès</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Statistiques détaillées sur vos publications</span>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <p>&copy; <?php echo date('Y'); ?> ZanouTek. Tous droits réservés.</p>
            </div>
        </div>
    </div>
    
    <!-- Main login form area -->
    <div class="login-main">
        <div class="login-container">
            <div class="login-header">
                <h2>Bienvenue</h2>
                <p>Connectez-vous pour accéder à votre espace</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email</label>
                    <div class="form-control-wrapper">
                        <input type="email" name="email" id="email" class="form-control" placeholder="Entrez votre email" required>
                        <i class="fas fa-envelope form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="form-control-wrapper">
                        <input type="password" name="mot_de_passe" id="password" class="form-control" placeholder="Entrez votre mot de passe" required>
                        <i class="fas fa-lock form-icon"></i>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                    </div>
                    <a href="mot-de-passe-oublie.php" class="forgot-password">Mot de passe oublié?</a>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                </button>
            </form>
            
            <div class="divider">ou connectez-vous avec</div>
            
            <div class="social-login">
                <a href="#" class="social-btn google">
                    <i class="fab fa-google"></i>
                </a>
                <a href="#" class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-btn twitter">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
            
            <div class="signup-prompt">
                Vous n'avez pas de compte? <a href="inscription.php" class="signup-link">Inscrivez-vous</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>