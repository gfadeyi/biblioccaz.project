<?php
require_once 'config.php';
include 'header.php';
?>
<head>
        
<h1>Les petits prix</h1>;
</head>

    <header class="py-3 shadow-sm">
        <div class="container-fluid px-5">
            <div class="row align-items-center">
                <div class="col-md-3">
                            <h3 class="fw-bold text-bibli-green mb-0"><i class="bi bi-book-half"></i>BIBLIOccaz</h3>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control search-bar" placeholder="Rechercher par auteur, titre, ISBN...">
                        <button class="btn btn-search px-4" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <a href="#" class="text-dark me-3 text-decoration-none"><i class="bi bi-person fs-4"></i></a>
                    <a href="#" class="text-dark text-decoration-none"><i class="bi bi-cart3 fs-4"></i></a>
                </div>
            </div>
        </div>
    </header>
</body>
</html>
<?php
include'footer.php';
