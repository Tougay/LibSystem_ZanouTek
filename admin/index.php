<?php
include 'config.php';

// Suppression de document (uniquement pour les administrateurs)
if (isset($_GET['sup']) && is_numeric($_GET['sup']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $id = $_GET['sup'];
    $sqlDelete = "DELETE FROM documents WHERE id = :id";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([':id' => $id]);
    header("Location: index.php?success=2");
    exit();
}

// Message de succès
$message = isset($_GET['success']) 
    ? ($_GET['success'] == 1 
        ? '<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i>Document ajouté avec succès !<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
        : '<div class="alert alert-warning alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i>Document supprimé avec succès !<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>')
    : '';

// Types de documents
$types = [
    "livre" => ["titre" => "Livres", "icon" => "book", "color" => "#2c3e50"],
    "fascicule" => ["titre" => "Fascicules", "icon" => "journal", "color" => "#16a085"],
    "cours" => ["titre" => "Cours", "icon" => "mortarboard", "color" => "#e74c3c"],
    "sujet" => ["titre" => "Sujets/Concours", "icon" => "file-text", "color" => "#8e44ad"],
    "type_bac" => ["titre" => "Type Bac", "icon" => "award", "color" => "#f39c12"]
];

// Récupérer les documents
$sql = "SELECT * FROM documents ORDER BY type_document, pays, matiere, annee DESC";
$stmt = $pdo->query($sql);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Regrouper les documents par type et par sous-catégorie
$groupedDocuments = [];
foreach ($documents as $doc) {
    $type = $doc['type_document'];
    
    // Pour les types spécifiques, on regroupe différemment
    if ($type === 'type_bac') {
        // Grouper par matière
        $matiere = !empty($doc['matiere']) ? $doc['matiere'] : 'Autre';
        $groupedDocuments[$type][$matiere][] = $doc;
    } 
    elseif ($type === 'fascicule' || $type === 'sujet') {
        // Grouper par pays
        $pays = !empty($doc['pays']) ? $doc['pays'] : 'International';
        $groupedDocuments[$type][$pays][] = $doc;
    } 
    else {
        // Les autres types sont simplement groupés par type
        $groupedDocuments[$type]['all'][] = $doc;
    }
}

// Comptage total de documents
$total_documents = count($documents);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque ZanouTek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e3a8a;
            --light-bg: #f8fafc;
            --dark-bg: #0f172a;
            --text-color: #1e293b;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .navbar-brand span {
            color: var(--secondary-color);
        }
        
        .navbar .nav-link {
            font-weight: 500;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .navbar .nav-link:hover {
            color: var(--primary-color);
        }
        
        .main-container {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .hero-section {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 2.5rem 2rem;
            margin-bottom: 2rem;
        }
        
        .hero-section h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }
        
        .hero-buttons .btn {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 50px;
            margin-right: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-light {
            background-color: white;
            color: var(--primary-color);
        }
        
        .filter-section {
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .document-type-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .section-title .icon {
            margin-right: 0.8rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
        }
        
        .subcategory-title {
            display: inline-block;
            font-weight: 500;
            font-size: 1.1rem;
            margin-bottom: 1.2rem;
            padding: 0.5rem 1rem;
            background-color: var(--light-bg);
            border-radius: 8px;
            color: var(--text-color);
        }
        
        .document-card {
            border-radius: var(--border-radius);
            border: none;
            overflow: hidden;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }
        
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .document-header {
            padding: 0.8rem 1.2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .document-type-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: white;
            font-size: 0.9rem;
        }
        
        .document-title {
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--text-color);
            font-size: 1.1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .document-details {
            padding: 1.2rem;
            background-color: white;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .document-info {
            margin-bottom: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .document-info strong {
            color: var(--text-color);
            font-weight: 500;
        }
        
        .document-badges {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        
        .doc-badge {
            background-color: #e2e8f0;
            color: #475569;
            font-size: 0.8rem;
            padding: 0.3rem 0.7rem;
            border-radius: 4px;
            margin-right: 0.5rem;
            display: inline-block;
        }
        
        .btn-view {
            margin-top: auto;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: #ef4444;
            border-color: #ef4444;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }
        
        .footer {
            background-color: white;
            padding: 2rem 0;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .footer p {
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Responsive styles */
        @media (max-width: 767px) {
            .main-container {
                padding: 1rem;
            }
            
            .hero-section {
                padding: 1.5rem;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Zanou<span>Tek</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?#documents">Documents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="submit.php">Publier</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> Mon compte
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear me-2"></i>Administration</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="deconnexion.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                   
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Section Héro -->
        <div class="hero-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Bibliothèque Numérique ZanouTek</h1>
                    <p>Accédez à une collection de documents éducatifs pour vos études et recherches. Tous les documents sont disponibles gratuitement.</p>
                    <div class="hero-buttons">
                        <a href="#documents" class="btn btn-light">Parcourir les documents</a>
                        <a href="submit.php" class="btn btn-outline-light">Contribuer</a>
                    </div>
                </div>
                <div class="col-md-4 d-none d-md-block text-center">
                    <i class="bi bi-book text-white" style="font-size: 5rem;"></i>
                </div>
            </div>
        </div>
        
        <?= $message ?>

        <!-- Section Filtres -->
        <div class="filter-section" id="documents">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-filter me-2"></i>Parcourir par catégorie</h4>
                <span class="badge bg-primary rounded-pill"><?= $total_documents ?> document<?= $total_documents > 1 ? 's' : '' ?></span>
            </div>
            
            <select id="typeFilter" class="form-select">
                <option value="">Tous les documents</option>
                <?php foreach ($types as $typeKey => $typeInfo): ?>
                    <option value="<?= $typeKey ?>"><?= $typeInfo['titre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Affichage des documents groupés -->
        <?php foreach ($types as $typeKey => $typeInfo) : ?>
            <?php if (!empty($groupedDocuments[$typeKey])) : ?>
                <div class="document-type-section" data-type="<?= $typeKey ?>">
                    <h3 class="section-title">
                        <span class="icon" style="background-color: <?= $typeInfo['color'] ?>">
                            <i class="bi bi-<?= $typeInfo['icon'] ?>"></i>
                        </span>
                        <?= $typeInfo['titre'] ?>
                    </h3>

                    <?php foreach ($groupedDocuments[$typeKey] as $subCategory => $docs) : ?>
                        <div class="subcategory-section">
                            <?php if ($typeKey === 'type_bac' && $subCategory !== 'all'): ?>
                                <h4 class="subcategory-title">
                                    <i class="bi bi-grid-3x3-gap me-2"></i> 
                                    Matière: <?= htmlspecialchars($subCategory) ?>
                                </h4>
                            <?php elseif (($typeKey === 'fascicule' || $typeKey === 'sujet') && $subCategory !== 'all'): ?>
                                <h4 class="subcategory-title">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    Pays: <?= htmlspecialchars($subCategory) ?>
                                </h4>
                            <?php endif; ?>

                            <div class="row">
                                <?php foreach ($docs as $doc) : ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="document-card h-100">
                                            <div class="document-header">
                                                <div class="document-type-icon" style="background-color: <?= $typeInfo['color'] ?>">
                                                    <i class="bi bi-<?= $typeInfo['icon'] ?>"></i>
                                                </div>
                                                <div><?= $typeInfo['titre'] ?></div>
                                            </div>
                                            <div class="document-details">
                                                <h5 class="document-title"><?= $doc['titre'] ?></h5>
                                                
                                                <div class="document-info">
                                                    <strong><i class="bi bi-person-fill me-1"></i> Auteur:</strong> <?= htmlspecialchars($doc['auteur']) ?>
                                                </div>
                                                
                                                <div class="document-info">
                                                    <strong><i class="bi bi-calendar-event me-1"></i> Année:</strong> <?= htmlspecialchars($doc['annee']) ?>
                                                </div>
                                                
                                                <?php if (!empty($doc['serie'])): ?>
                                                <div class="document-info">
                                                    <strong><i class="bi bi-bookmark-fill me-1"></i> Série:</strong> <?= htmlspecialchars($doc['serie']) ?>
                                                </div>
                                                <?php endif; ?>

                                                <?php if (!empty($doc['matiere']) && $typeKey !== 'type_bac'): ?>
                                                <div class="document-info">
                                                    <strong><i class="bi bi-journal-text me-1"></i> Matière:</strong> <?= htmlspecialchars($doc['matiere']) ?>
                                                </div>
                                                <?php endif; ?>

                                                <?php if (!empty($doc['concours'])): ?>
                                                <div class="document-info">
                                                    <strong><i class="bi bi-trophy-fill me-1"></i> Concours:</strong> <?= htmlspecialchars($doc['concours']) ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="document-badges">
                                                    <?php if (!empty($doc['pays']) && $doc['pays'] !== 'International'): ?>
                                                    <span class="doc-badge"><i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($doc['pays']) ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($typeKey === 'type_bac'): ?>
                                                    <span class="doc-badge"><i class="bi bi-mortarboard-fill me-1"></i> Baccalauréat</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($doc['date_ajout'])): ?>
                                                    <span class="doc-badge">
                                                        <i class="bi bi-clock-fill me-1"></i> Ajouté le <?= date('d/m/Y', strtotime($doc['date_ajout'])) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="d-flex justify-content-between mt-auto">
                                                    <a href="uploads/<?= htmlspecialchars($doc['fichier']) ?>" 
                                                       target="_blank" class="btn btn-primary btn-view">
                                                        <i class="bi bi-file-earmark-pdf me-1"></i> Consulter
                                                    </a>
                                                    
                                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                    <a href="index.php?sup=<?= $doc['id'] ?>" 
                                                       class="btn btn-sm btn-danger mt-auto align-self-end" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> ZanouTek Bibliothèque Numérique</p>
            <p>Tous droits réservés</p>
            <div class="footer-links mt-3">
                <a href="index.php">Accueil</a>
                <a href="submit.php">Publier un document</a>
                <a href="#">À propos</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filtre par type de document
        const typeFilter = document.getElementById('typeFilter');
        const documentSections = document.querySelectorAll('.document-type-section');

        typeFilter.addEventListener('change', function() {
            const selectedType = this.value;

            documentSections.forEach(section => {
                section.style.display = (selectedType === '' || section.dataset.type === selectedType) 
                    ? 'block' 
                    : 'none';
            });
            
            // Scroll vers la section des documents
            if (selectedType !== '') {
                document.getElementById('documents').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
        
        // Animation douce lors du scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Auto-fermeture des alertes après 5 secondes
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>
</body>
</html>