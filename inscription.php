<?php
session_start();
include 'bd/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $confirmation = $_POST['confirmation'];

    // Vérifier que les mots de passe correspondent
    if ($_POST['mot_de_passe'] !== $confirmation) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email");
            $check->execute([':email' => $email]);
            if ($check->fetchColumn() > 0) {
                $error = "Cette adresse email est déjà utilisée";
            } else {
                $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, date_inscription) 
                        VALUES (:nom, :prenom, :email, :mot_de_passe, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':mot_de_passe' => $mot_de_passe
                ]);
                
                // Redirection avec message de succès
                header("Location: index.php?inscription=success");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erreur d'inscription: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --light-accent: #e3f2fd;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --text-color: #2c3e50;
            --text-muted: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }
        
        .signup-sidebar {
            width: 40%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background: linear-gradient(45deg, #3498db, #2c3e50);
            z-index: 0;
        }
        
        .animated-bg:before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: repeating-linear-gradient(
                60deg,
                transparent,
                transparent 40px,
                rgba(255, 255, 255, 0.1) 40px,
                rgba(255, 255, 255, 0.1) 80px
            );
            top: -25%;
            left: -25%;
            animation: slide 20s linear infinite;
        }
        
        @keyframes slide {
            0% {
                transform: translateX(-50px) translateY(0);
            }
            100% {
                transform: translateX(0) translateY(-50px);
            }
        }
        
        .sidebar-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .sidebar-header {
            margin-bottom: 3rem;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }
        
        .tagline {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }
        
        .benefits-list {
            margin-top: 3rem;
            list-style-type: none;
            padding: 0;
        }
        
        .benefits-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .benefits-list li i {
            color: var(--accent-color);
            margin-right: 1rem;
            margin-top: 0.2rem;
        }
        
        .benefits-list li span {
            line-height: 1.5;
        }
        
        .sidebar-footer {
            margin-top: auto;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .signup-main {
            width: 60%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            background-color: white;
            overflow-y: auto;
        }
        
        .signup-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        .signup-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .signup-header h2 {
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .signup-header p {
            color: var(--text-muted);
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control-wrapper {
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 3rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: var(--transition);
        }
        
        .form-control:focus + .form-icon {
            color: var(--accent-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--accent-color);
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            background-color: var(--border-color);
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: var(--transition);
        }
        
        .password-feedback {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            color: var(--text-muted);
        }
        
        .form-check {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            margin-right: 0.75rem;
            margin-top: 0.25rem;
            width: 1rem;
            height: 1rem;
        }
        
        .form-check-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .form-check-label a {
            color: var(--accent-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .form-check-label a:hover {
            text-decoration: underline;
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: none;
            padding: 0.85rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 8px;
            transition: var(--transition);
            width: 100%;
            cursor: pointer;
        }
        
        .btn-primary {
            color: white;
            background-color: var(--accent-color);
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .divider:before, .divider:after {
            content: "";
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }
        
        .divider:before {
            margin-right: 1rem;
        }
        
        .divider:after {
            margin-left: 1rem;
        }
        
        .social-signup {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--light-accent);
            color: var(--accent-color);
            transition: var(--transition);
            text-decoration: none;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .social-btn.google {
            background-color: #fbe9e7;
            color: #db4437;
        }
        
        .social-btn.facebook {
            background-color: #e8f4f8;
            color: #4267B2;
        }
        
        .social-btn.twitter {
            background-color: #e8f5fd;
            color: #1DA1F2;
        }
        
        .login-prompt {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .login-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .signup-sidebar, .signup-main {
                width: 100%;
            }
            
            .signup-sidebar {
                padding: 2rem;
                order: 2;
                text-align: center;
            }
            
            .benefits-list li {
                justify-content: center;
            }
            
            .signup-main {
                padding: 2rem 1rem;
                order: 1;
            }
            
            .signup-container {
                padding: 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 576px) {
            .signup-container {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Left sidebar with branding and benefits -->
    <div class="signup-sidebar">
        <div class="animated-bg"></div>
        <div class="sidebar-content">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-book-open"></i> ZanouTek
                </div>
                <h1 class="tagline">Rejoignez notre communauté</h1>
                <p>Créez un compte et commencez à partager vos ressources documentaires.</p>
            </div>
            
            <ul class="benefits-list">
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Accès à une bibliothèque de ressources éducatives</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Publication et partage de vos propres documents</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Suivi des statistiques de consultation</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Interaction avec une communauté d'apprenants</span>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <p>&copy; <?php echo date('Y'); ?> ZanouTek. Tous droits réservés.</p>
            </div>
        </div>
    </div>
    
    <!-- Main signup form area -->
    <div class="signup-main">
        <div class="signup-container">
            <div class="signup-header">
                <h2>Créer un compte</h2>
                <p>Complétez le formulaire ci-dessous pour vous inscrire</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" id="inscriptionForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom</label>
                        <div class="form-control-wrapper">
                            <input type="text" name="nom" id="nom" class="form-control" placeholder="Votre nom" required>
                            <i class="fas fa-user form-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom" class="form-label">Prénom</label>
                        <div class="form-control-wrapper">
                            <input type="text" name="prenom" id="prenom" class="form-control" placeholder="Votre prénom" required>
                            <i class="fas fa-user form-icon"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email</label>
                    <div class="form-control-wrapper">
                        <input type="email" name="email" id="email" class="form-control" placeholder="votre.email@exemple.com" required>
                        <i class="fas fa-envelope form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <div class="form-control-wrapper">
                        <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" placeholder="Créez un mot de passe sécurisé" required>
                        <i class="fas fa-lock form-icon"></i>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <p id="passwordFeedback" class="password-feedback">Utilisez au moins 8 caractères avec des lettres, chiffres et symboles</p>
                </div>
                
                <div class="form-group">
                    <label for="confirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="form-control-wrapper">
                        <input type="password" name="confirmation" id="confirmation" class="form-control" placeholder="Confirmez votre mot de passe" required>
                        <i class="fas fa-lock form-icon"></i>
                    </div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="acceptTerms" name="acceptTerms" required>
                    <label class="form-check-label" for="acceptTerms">
                        J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a> de ZanouTek
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i> Créer mon compte
                </button>
            </form>
            
            <div class="divider">ou inscrivez-vous avec</div>
            
            <div class="social-signup">
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
            
            <div class="login-prompt">
                Vous avez déjà un compte? <a href="index.php" class="login-link">Connectez-vous</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('mot_de_passe');
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
        
        // Password strength meter
        const passwordInput = document.getElementById('mot_de_passe');
        const strengthBar = document.getElementById('passwordStrength');
        const feedback = document.getElementById('passwordFeedback');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) {
                strength += 25;
            }
            
            if (password.match(/[A-Z]/)) {
                strength += 25;
            }
            
            if (password.match(/[0-9]/)) {
                strength += 25;
            }
            
            if (password.match(/[^A-Za-z0-9]/)) {
                strength += 25;
            }
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.backgroundColor = '#e74c3c'; // Rouge
                message = 'Mot de passe faible';
            } else if (strength <= 50) {
                strengthBar.style.backgroundColor = '#f39c12'; // Orange
                message = 'Mot de passe moyen';
            } else if (strength <= 75) {
                strengthBar.style.backgroundColor = '#f1c40f'; // Jaune
                message = 'Mot de passe acceptable';
            } else {
                strengthBar.style.backgroundColor = '#2ecc71'; // Vert
                message = 'Mot de passe fort';
            }
            
            feedback.textContent = message;
        });
        
        // Form validation
        const form = document.getElementById('inscriptionForm');
        const confirmPassword = document.getElementById('confirmation');
        
        form.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPassword.value) {
                event.preventDefault();
                feedback.textContent = 'Les mots de passe ne correspondent pas!';
                feedback.style.color = '#e74c3c';
            }
        });
    </script>
</body>
</html>