<?php
session_start();
include 'bd/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Types de documents
$types = [
    "livre" => ["titre" => "Livres", "icon" => "book", "color" => "#2c3e50", "description" => "Ouvrages complets sur divers sujets"],
    "fascicule" => ["titre" => "Fascicules", "icon" => "journal", "color" => "#16a085", "description" => "Documents courts et spécifiques"],
    "cours" => ["titre" => "Cours", "icon" => "mortarboard", "color" => "#e74c3c", "description" => "Supports pédagogiques et cours complets"],
    "sujet" => ["titre" => "Sujets/Concours", "icon" => "file-text", "color" => "#8e44ad", "description" => "Sujets d'examens et concours nationaux"],
    "type_bac" => ["titre" => "Type Bac", "icon" => "award", "color" => "#f39c12", "description" => "Sujets d'entraînement pour le baccalauréat"]
];

// Récupérer la vue sélectionnée (grille ou liste)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'grid';

// Récupérer les filtres de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_pays = isset($_GET['filter_pays']) ? $_GET['filter_pays'] : '';
$filter_matiere = isset($_GET['filter_matiere']) ? $_GET['filter_matiere'] : '';
$filter_serie = isset($_GET['filter_serie']) ? $_GET['filter_serie'] : '';
$filter_annee = isset($_GET['filter_annee']) ? (int)$_GET['filter_annee'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_desc';

// Construction de la requête SQL avec filtres
$sql = "SELECT * FROM documents WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (titre LIKE ? OR auteur LIKE ? OR matiere LIKE ? OR pays LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($filter_type)) {
    $sql .= " AND type_document = ?";
    $params[] = $filter_type;
}

if (!empty($filter_pays)) {
    $sql .= " AND pays = ?";
    $params[] = $filter_pays;
}

if (!empty($filter_matiere)) {
    $sql .= " AND matiere = ?";
    $params[] = $filter_matiere;
}

if (!empty($filter_serie)) {
    $sql .= " AND serie = ?";
    $params[] = $filter_serie;
}

if ($filter_annee > 0) {
    $sql .= " AND annee = ?";
    $params[] = $filter_annee;
}

// Options de tri
switch ($sort_by) {
    case 'title_asc':
        $sql .= " ORDER BY titre ASC";
        break;
    case 'title_desc':
        $sql .= " ORDER BY titre DESC";
        break;
    case 'year_desc':
        $sql .= " ORDER BY annee DESC";
        break;
    case 'year_asc':
        $sql .= " ORDER BY annee ASC";
        break;
    case 'date_desc':
    default:
        $sql .= " ORDER BY date_creation DESC, type_document, pays, matiere";
        break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$offset = ($page - 1) * $per_page;

// Requête pour le nombre total de résultats
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $per_page);

// Ajouter LIMIT pour la pagination
$sql .= " LIMIT $offset, $per_page";

// Exécuter la requête principale
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les listes pour les filtres (sans limites pour avoir toutes les options)
$stmt_pays = $pdo->query("SELECT DISTINCT pays FROM documents WHERE pays IS NOT NULL AND pays != '' ORDER BY pays");
$pays_list = $stmt_pays->fetchAll(PDO::FETCH_COLUMN);

$stmt_matieres = $pdo->query("SELECT DISTINCT matiere FROM documents WHERE matiere IS NOT NULL AND matiere != '' ORDER BY matiere");
$matieres_list = $stmt_matieres->fetchAll(PDO::FETCH_COLUMN);

$stmt_series = $pdo->query("SELECT DISTINCT serie FROM documents WHERE serie IS NOT NULL AND serie != '' ORDER BY serie");
$series_list = $stmt_series->fetchAll(PDO::FETCH_COLUMN);

$stmt_annees = $pdo->query("SELECT DISTINCT annee FROM documents WHERE annee IS NOT NULL ORDER BY annee DESC");
$annees_list = $stmt_annees->fetchAll(PDO::FETCH_COLUMN);

// Comptage par catégorie (pour les statistiques)
$cat_counts = [];
foreach ($types as $key => $type) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE type_document = ?");
    $stmt->execute([$key]);
    $cat_counts[$key] = $stmt->fetchColumn();
}

// Regrouper les documents si on n'est pas en mode recherche/filtre
$groupedDocuments = [];
if (empty($search) && empty($filter_type) && empty($filter_pays) && empty($filter_matiere) && empty($filter_serie) && $filter_annee == 0) {
    // Récupérer tous les documents pour le groupement (sans pagination)
    $all_docs_stmt = $pdo->query("SELECT * FROM documents ORDER BY type_document, pays, matiere, annee DESC");
    $all_documents = $all_docs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_documents as $doc) {
        $type = $doc['type_document'];
        
        if ($type === 'type_bac') {
            $matiere = !empty($doc['matiere']) ? $doc['matiere'] : 'Autre';
            $groupedDocuments[$type][$matiere][] = $doc;
        } elseif ($type === 'fascicule' || $type === 'sujet') {
            $pays = !empty($doc['pays']) ? $doc['pays'] : 'International';
            $groupedDocuments[$type][$pays][] = $doc;
        } else {
            $groupedDocuments[$type]['all'][] = $doc;
        }
    }
}

// Comptage total de documents
$total_documents = $total_count;

// Récupération des derniers documents ajoutés
$recent_stmt = $pdo->query("SELECT * FROM documents ORDER BY date_creation DESC LIMIT 5");
$recent_documents = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des 3 matières les plus populaires
$popular_subjects_stmt = $pdo->query("SELECT matiere, COUNT(*) as count FROM documents WHERE matiere IS NOT NULL AND matiere != '' GROUP BY matiere ORDER BY count DESC LIMIT 3");
$popular_subjects = $popular_subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Générer l'URL avec les paramètres actuels
function buildUrl($new_params = []) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return '?' . http_build_query($params);
}
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="./style/styles.css">
    </head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">Zanou<span>Tek</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="acueil_utilisateur.php">
                            <i class="bi bi-book me-1"></i> Bibliothèque
                        </a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="bi bi-gear me-1"></i> Administration
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= substr($_SESSION['user_email'], 0, 1) ?>
                            </div>
                            <?= htmlspecialchars($_SESSION['user_email']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Mon profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="deconnexion.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- En-tête de page -->
        <div class="page-header">
            <div class="page-header-content">
                <h2>Bibliothèque Numérique</h2>
                <p class="mb-0">Explorez notre collection de <?= $total_documents ?> documents éducatifs</p>
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="submit.php" class="add-document-btn">
                    <i class="bi bi-plus-lg me-2"></i> Ajouter un document
                </a>
            <?php endif; ?>
        </div>

        <!-- Panneau de recherche -->
        <div class="search-panel">
            <div class="search-title" id="searchToggle">
                <h4><i class="bi bi-search"></i> Recherche de documents</h4>
                <span class="ms-auto"><i class="bi bi-chevron-down"></i></span>
            </div>
            
            <div class="search-content active" id="searchContent">
                <form action="" method="GET" class="row g-3">
                    <!-- Recherche simple -->
                    <div class="col-12 mb-3">
                        <div class="input-group search-input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Rechercher par titre, auteur, matière..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary search-btn">Rechercher</button>
                        </div>
                    </div>
                    
                    <!-- Recherche avancée -->
                    <div class="col-12">
                        <div class="adv-search-toggle mb-2" id="advancedSearchToggle">
                            <i class="bi bi-funnel"></i> Filtres avancés
                        </div>
                        
                        <div id="advancedSearch" class="adv-filter-section" style="display: <?= (!empty($filter_type) || !empty($filter_pays) || !empty($filter_matiere) || !empty($filter_serie) || $filter_annee > 0) ? 'block' : 'none' ?>;">
                            <div class="row g-3">
                                <!-- Type de document -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="filter_type" class="form-label">Type de document</label>
                                    <select name="filter_type" id="filter_type" class="form-select">
                                        <option value="">Tous les types</option>
                                        <?php foreach ($types as $typeKey => $typeInfo): ?>
                                            <option value="<?= $typeKey ?>" <?= $filter_type === $typeKey ? 'selected' : '' ?>>
                                                <?= $typeInfo['titre'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Pays -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="filter_pays" class="form-label">Pays</label>
                                    <select name="filter_pays" id="filter_pays" class="form-select">
                                        <option value="">Tous les pays</option>
                                        <?php foreach ($pays_list as $pays): ?>
                                            <option value="<?= htmlspecialchars($pays) ?>" <?= $filter_pays === $pays ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($pays) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Matière -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="filter_matiere" class="form-label">Matière</label>
                                    <select name="filter_matiere" id="filter_matiere" class="form-select">
                                        <option value="">Toutes les matières</option>
                                        <?php foreach ($matieres_list as $matiere): ?>
                                            <option value="<?= htmlspecialchars($matiere) ?>" <?= $filter_matiere === $matiere ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($matiere) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Série -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="filter_serie" class="form-label">Série</label>
                                    <select name="filter_serie" id="filter_serie" class="form-select">
                                        <option value="">Toutes les séries</option>
                                        <?php foreach ($series_list as $serie): ?>
                                            <option value="<?= htmlspecialchars($serie) ?>" <?= $filter_serie === $serie ? 'selected' : '' ?>>
                                                Série <?= htmlspecialchars($serie) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Année -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="filter_annee" class="form-label">Année</label>
                                    <select name="filter_annee" id="filter_annee" class="form-select">
                                        <option value="">Toutes les années</option>
                                        <?php foreach ($annees_list as $annee): ?>
                                            <option value="<?= $annee ?>" <?= $filter_annee === (int)$annee ? 'selected' : '' ?>>
                                                <?= $annee ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tri -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="sort_by" class="form-label">Trier par</label>
                                    <select name="sort_by" id="sort_by" class="form-select">
                                        <option value="date_desc" <?= $sort_by === 'date_desc' ? 'selected' : '' ?>>Date (récent → ancien)</option>
                                        <option value="title_asc" <?= $sort_by === 'title_asc' ? 'selected' : '' ?>>Titre (A → Z)</option>
                                        <option value="title_desc" <?= $sort_by === 'title_desc' ? 'selected' : '' ?>>Titre (Z → A)</option>
                                        <option value="year_desc" <?= $sort_by === 'year_desc' ? 'selected' : '' ?>>Année (récent → ancien)</option>
                                        <option value="year_asc" <?= $sort_by === 'year_asc' ? 'selected' : '' ?>>Année (ancien → récent)</option>
                                    </select>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-funnel me-1"></i> Appliquer les filtres
                                    </button>
                                    <a href="bibliotheque.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            </div>
        
        <!-- Afficher les filtres actifs -->
        <?php if (!empty($search) || !empty($filter_type) || !empty($filter_pays) || !empty($filter_matiere) || !empty($filter_serie) || $filter_annee > 0): ?>
            <div class="mb-4">
                <div class="active-filters-title">
                    <i class="bi bi-funnel-fill"></i> Filtres actifs
                </div>
                <div class="active-filters">
                    <?php if (!empty($search)): ?>
                        <div class="filter-badge">
                            <span>Recherche: <?= htmlspecialchars($search) ?></span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['search' => ''])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($filter_type)): ?>
                        <div class="filter-badge">
                            <span>Type: <?= $types[$filter_type]['titre'] ?></span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['filter_type' => ''])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($filter_pays)): ?>
                        <div class="filter-badge">
                            <span>Pays: <?= htmlspecialchars($filter_pays) ?></span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['filter_pays' => ''])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($filter_matiere)): ?>
                        <div class="filter-badge">
                            <span>Matière: <?= htmlspecialchars($filter_matiere) ?></span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['filter_matiere' => ''])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($filter_serie)): ?>
                        <div class="filter-badge">
                            <span>Série: <?= htmlspecialchars($filter_serie) ?></span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['filter_serie' => ''])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($filter_annee > 0): ?>
                        <div class="filter-badge">
                            <span>Année: <?= $filter_annee ?></span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['filter_annee' => ''])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($sort_by && $sort_by !== 'date_desc'): ?>
                        <div class="filter-badge">
                            <span>Tri: 
                                <?php 
                                    switch($sort_by) {
                                        case 'title_asc': echo 'Titre (A→Z)'; break;
                                        case 'title_desc': echo 'Titre (Z→A)'; break;
                                        case 'year_desc': echo 'Année (récent→ancien)'; break;
                                        case 'year_asc': echo 'Année (ancien→récent)'; break;
                                        default: echo 'Personnalisé';
                                    }
                                ?>
                            </span>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'date_desc'])) ?>" class="close text-white text-decoration-none">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Résultats de recherche -->
        <?php if (empty($documents)): ?>
            <div class="no-results">
                <i class="bi bi-search"></i>
                <h4>Aucun document ne correspond à votre recherche</h4>
                <p>Essayez de modifier vos critères de recherche ou <a href="bibliotheque.php" class="text-primary">réinitialisez tous les filtres</a>.</p>
            </div>
        <?php else: ?>
            <!-- Affichage des documents groupés (quand pas de recherche active) -->
            <?php if (empty($search) && empty($filter_type) && empty($filter_pays) && empty($filter_matiere) && empty($filter_serie) && $filter_annee == 0): ?>
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
                                            <i class="bi bi-grid-3x3-gap"></i> 
                                            Matière: <?= htmlspecialchars($subCategory) ?>
                                        </h4>
                                    <?php elseif (($typeKey === 'fascicule' || $typeKey === 'sujet') && $subCategory !== 'all'): ?>
                                        <h4 class="subcategory-title">
                                            <i class="bi bi-geo-alt"></i>
                                            Pays: <?= htmlspecialchars($subCategory) ?>
                                        </h4>
                                    <?php endif; ?>

                                    <div class="row">
                                        <?php $counter = 0; ?>
                                        <?php foreach ($docs as $doc) : ?>
                                            <?php if ($counter++ < 6): // Limiter à 6 documents par catégorie pour une meilleure présentation ?>
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
                                                                <i class="bi bi-person"></i>
                                                                <strong>Auteur:</strong> <?= htmlspecialchars($doc['auteur']) ?>
                                                            </div>
                                                            
                                                            <div class="document-info">
                                                                <i class="bi bi-calendar"></i>
                                                                <strong>Année:</strong> <?= htmlspecialchars($doc['annee']) ?>
                                                            </div>
                                                            
                                                            <?php if (!empty($doc['serie'])): ?>
                                                            <div class="document-info">
                                                                <i class="bi bi-bookmark"></i>
                                                                <strong>Série:</strong> <?= htmlspecialchars($doc['serie']) ?>
                                                            </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($doc['matiere']) && $typeKey !== 'type_bac'): ?>
                                                            <div class="document-info">
                                                                <i class="bi bi-journal"></i>
                                                                <strong>Matière:</strong> <?= htmlspecialchars($doc['matiere']) ?>
                                                            </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($doc['concours'])): ?>
                                                            <div class="document-info">
                                                                <i class="bi bi-trophy"></i>
                                                                <strong>Concours:</strong> <?= htmlspecialchars($doc['concours']) ?>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="document-badges">
                                                                <?php if (!empty($doc['pays']) && $doc['pays'] !== 'International'): ?>
                                                                <span class="doc-badge"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($doc['pays']) ?></span>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($typeKey === 'type_bac'): ?>
                                                                <span class="doc-badge"><i class="bi bi-mortarboard"></i> Baccalauréat</span>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (isset($doc['date_creation'])): ?>
                                                                <span class="doc-badge">
                                                                    <i class="bi bi-clock"></i> <?= date('d/m/Y', strtotime($doc['date_creation'])) ?>
                                                                </span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="document-actions">
                                                                <a href="admin/uploads/<?= htmlspecialchars($doc['fichier']) ?>" 
                                                                target="_blank" class="btn btn-primary btn-view">
                                                                    <i class="bi bi-file-earmark-pdf me-2"></i> Consulter
                                                                </a>
                                                                
                                                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                                <a href="edit.php?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary btn-view">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if (count($docs) > 6): ?>
                                        <div class="text-center mt-2 mb-4">
                                            <a href="?filter_type=<?= $typeKey ?><?= $subCategory !== 'all' ? ($typeKey === 'type_bac' ? '&filter_matiere='.urlencode($subCategory) : '&filter_pays='.urlencode($subCategory)) : '' ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-plus-lg me-1"></i> Voir tous les <?= count($docs) ?> documents
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
            <!-- Affichage des résultats de recherche -->
            <?php else: ?>
                <div class="row">
                    <?php foreach ($documents as $doc) : ?>
                        <?php $typeInfo = $types[$doc['type_document']] ?? ['titre' => 'Document', 'icon' => 'file-text', 'color' => '#4b5563']; ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="document-card h-100">
                                <div class="document-header">
                                    <div class="document-type-icon" style="background-color: <?= $typeInfo['color'] ?>">
                                        <i class="bi bi-<?= $typeInfo['icon'] ?>"></i>
                                    </div>
                                    <div><?= $typeInfo['titre'] ?></div>
                                </div>
                                <div class="document-details">
                                    <h5 class="document-title"><?= htmlspecialchars($doc['titre']) ?></h5>
                                    
                                    <div class="document-info">
                                        <i class="bi bi-person"></i>
                                        <strong>Auteur:</strong> <?= htmlspecialchars($doc['auteur']) ?>
                                    </div>
                                    
                                    <div class="document-info">
                                        <i class="bi bi-calendar"></i>
                                        <strong>Année:</strong> <?= htmlspecialchars($doc['annee']) ?>
                                    </div>
                                    
                                    <?php if (!empty($doc['serie'])): ?>
                                    <div class="document-info">
                                        <i class="bi bi-bookmark"></i>
                                        <strong>Série:</strong> <?= htmlspecialchars($doc['serie']) ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($doc['matiere'])): ?>
                                    <div class="document-info">
                                        <i class="bi bi-journal"></i>
                                        <strong>Matière:</strong> <?= htmlspecialchars($doc['matiere']) ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($doc['concours'])): ?>
                                    <div class="document-info">
                                        <i class="bi bi-trophy"></i>
                                        <strong>Concours:</strong> <?= htmlspecialchars($doc['concours']) ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="document-badges">
                                        <?php if (!empty($doc['pays']) && $doc['pays'] !== 'International'): ?>
                                        <span class="doc-badge"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($doc['pays']) ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($doc['type_document'] === 'type_bac'): ?>
                                        <span class="doc-badge"><i class="bi bi-mortarboard"></i> Baccalauréat</span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($doc['date_creation'])): ?>
                                        <span class="doc-badge">
                                            <i class="bi bi-clock"></i> <?= date('d/m/Y', strtotime($doc['date_creation'])) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="document-actions">
                                        <a href="admin/uploads/<?= htmlspecialchars($doc['fichier']) ?>" 
                                        target="_blank" class="btn btn-primary btn-view">
                                            <i class="bi bi-file-earmark-pdf me-2"></i> Consulter
                                        </a>
                                        
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <a href="edit.php?id=<?= $doc['id'] ?>" class="btn btn-outline-secondary btn-view">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildUrl(['page' => $page-1]) ?>">
                            <i class="bi bi-chevron-left"></i> Précédent
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link"><i class="bi bi-chevron-left"></i> Précédent</span>
                    </li>
                <?php endif; ?>
                
                <?php 
                // Afficher un nombre limité de liens de pagination
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="' . buildUrl(['page' => 1]) . '">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                    } else {
                        echo '<li class="page-item"><a class="page-link" href="' . buildUrl(['page' => $i]) . '">' . $i . '</a></li>';
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="' . buildUrl(['page' => $total_pages]) . '">' . $total_pages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildUrl(['page' => $page+1]) ?>">
                            Suivant <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Suivant <i class="bi bi-chevron-right"></i></span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> ZanouTek Bibliothèque Numérique | Tous droits réservés</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle pour le panneau de recherche
        const searchToggle = document.getElementById('searchToggle');
        const searchContent = document.getElementById('searchContent');
        
        if (searchToggle && searchContent) {
            searchToggle.addEventListener('click', function() {
                searchContent.classList.toggle('active');
                const icon = this.querySelector('.bi-chevron-down, .bi-chevron-up');
                if (icon) {
                    if (icon.classList.contains('bi-chevron-down')) {
                        icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
                    } else {
                        icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
                    }
                }
            });
        }
        
        // Toggle pour la recherche avancée
        const advancedSearchToggle = document.getElementById('advancedSearchToggle');
        const advancedSearch = document.getElementById('advancedSearch');
        
        if (advancedSearchToggle && advancedSearch) {
            advancedSearchToggle.addEventListener('click', function() {
                if (advancedSearch.style.display === 'none' || advancedSearch.style.display === '') {
                    advancedSearch.style.display = 'block';
                    this.innerHTML = '<i class="bi bi-dash-circle"></i> Masquer les filtres avancés';
                } else {
                    advancedSearch.style.display = 'none';
                    this.innerHTML = '<i class="bi bi-funnel"></i> Filtres avancés';
                }
            });
            
            // Si les filtres avancés sont déjà utilisés, mettre à jour le texte du toggle
            if (advancedSearch.style.display === 'block') {
                advancedSearchToggle.innerHTML = '<i class="bi bi-dash-circle"></i> Masquer les filtres avancés';
            }
        }
        
        // Animation des badges de filtre
        const filterBadges = document.querySelectorAll('.filter-badge');
        if (filterBadges.length > 0) {
            filterBadges.forEach(badge => {
                badge.addEventListener('mouseover', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                badge.addEventListener('mouseout', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        }
        
        // Animation des cartes de document
        const documentCards = document.querySelectorAll('.document-card');
        if (documentCards.length > 0) {
            documentCards.forEach(card => {
                card.addEventListener('mouseover', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = 'var(--hover-shadow)';
                });
                card.addEventListener('mouseout', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'var(--card-shadow)';
                });
            });
        }
    });
    </script>
</body>
</html>