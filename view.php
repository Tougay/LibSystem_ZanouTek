<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DépotMemo</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.9.359/pdf.min.js"></script>
   <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #2c3e50;
        }
        .memoire-details {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .memoire-details p {
            font-size: 16px;
            color: #34495e;
            margin: 10px 0;
        }
        .memoire-details p strong {
            color: #2980b9;
        }
        #pdf-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid #ccc;
            overflow: auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        #pdf-canvas {
            display: block;
            margin: 0 auto;
        }
        .navigation-buttons {
            text-align: center;
            margin-top: 20px;
        }
        .navigation-buttons button {
            background-color: #2980b9;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            margin: 0 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .navigation-buttons button:hover {
            background-color: #1a5276;
        }
        .page-info {
            font-size: 16px;
            color: #34495e;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php
    // Inclure le fichier de configuration
    include 'config.php';

    // Vérifier si l'ID du mémoire est passé en paramètre
    if(isset($_GET['id'])) {
        // Récupérer l'ID du mémoire depuis l'URL
        $memoire_id = $_GET['id'];
        
        // Préparer la requête pour récupérer le mémoire par son ID
        $sql = "SELECT * FROM memoires WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        // Exécuter la requête en passant l'ID du mémoire
        $stmt->execute([$memoire_id]);
        
        // Récupérer le mémoire
        $memoire = $stmt->fetch();

        // Vérifier si le mémoire existe
        if($memoire) {
            // Afficher les détails du mémoire
            echo "<h1>{$memoire['titre']}</h1>";
            echo "<p><strong>Auteur:</strong> {$memoire['auteur']}</p>";
            echo "<p><strong>Université:</strong> {$memoire['universite']}</p>";
            echo "<p><strong>Année:</strong> {$memoire['annee']}</p>";
            echo "<p><strong>Domaine:</strong> {$memoire['domaine']}</p>";
            
            // Afficher le contenu du fichier PDF avec PDF.js
            echo "<div id='pdf-viewer'><canvas id='pdf-canvas'></canvas></div>";
            echo "<script>
                var url = 'uploads/{$memoire['fichier']}';

                var pdfjsLib = window['pdfjs-dist/build/pdf'];
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.9.359/pdf.worker.min.js';

                var pdfDoc = null,
                    pageNum = 1,
                    pageRendering = false,
                    pageNumPending = null,
                    scale = 1.5,
                    canvas = document.getElementById('pdf-canvas'),
                    ctx = canvas.getContext('2d');

                function renderPage(num) {
                    pageRendering = true;
                    pdfDoc.getPage(num).then(function(page) {
                        var viewport = page.getViewport({scale: scale});
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        var renderContext = {
                            canvasContext: ctx,
                            viewport: viewport
                        };
                        var renderTask = page.render(renderContext);

                        renderTask.promise.then(function() {
                            pageRendering = false;
                            if (pageNumPending !== null) {
                                renderPage(pageNumPending);
                                pageNumPending = null;
                            }
                        });
                    });

                    document.getElementById('page_num').textContent = num;
                }

                function queueRenderPage(num) {
                    if (pageRendering) {
                        pageNumPending = num;
                    } else {
                        renderPage(num);
                    }
                }

                function onPrevPage() {
                    if (pageNum <= 1) {
                        return;
                    }
                    pageNum--;
                    queueRenderPage(pageNum);
                }

                function onNextPage() {
                    if (pageNum >= pdfDoc.numPages) {
                        return;
                    }
                    pageNum++;
                    queueRenderPage(pageNum);
                }

                pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
                    pdfDoc = pdfDoc_;
                    document.getElementById('page_count').textContent = pdfDoc.numPages;
                    renderPage(pageNum);
                });
            </script>";
        } else {
            // Si le mémoire n'existe pas, afficher un message d'erreur
            echo "Mémoire non trouvé.";
        }
    } else {
        // Si l'ID du mémoire n'est pas spécifié, afficher un message d'erreur
        echo "ID du mémoire non spécifié.";
    }
    ?>
    <div>
       <a href="acueil.php"> Retour</a>
        <button onclick="onPrevPage()">Page Précédente</button>
        <button onclick="onNextPage()">Page Suivante</button>
        <span>Page: <span id="page_num"></span> / <span id="page_count"></span></span>
    </div>
</body>
</html>
