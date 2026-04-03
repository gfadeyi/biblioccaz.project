<?php
require_once 'config.php';
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
include 'header.php';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM livre WHERE id_livre = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: admin.php");
    exit();
}

if (isset($_POST['ajouter_livre'])) {
    try {
        $pdo->beginTransaction();
        $nomImage = "default.png"; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $nomImage = time() . "_" . bin2hex(random_bytes(4)) . "." . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['image']['tmp_name'], "img/" . $nomImage);
        }
        $stmt = $pdo->prepare("INSERT INTO livre (titre, auteur, id_cat, couverture, description) VALUES (?, ?, 1, ?, ?)");
        $stmt->execute([$_POST['titre'], $_POST['auteur'], $nomImage, $_POST['description']]);
        
        $lastId = $pdo->lastInsertId();
        $stmtEx = $pdo->prepare("INSERT INTO exemplaire (id_livre, id_user, prix, etat) VALUES (?, ?, ?, ?)");
        $stmtEx->execute([$lastId, $_SESSION['id_user'] ?? 1, $_POST['prix'], $_POST['etat']]);
        $pdo->commit();
    } catch (Exception $e) { $pdo->rollBack(); }
}

$livres = $pdo->query("SELECT * FROM livre ORDER BY id_livre DESC")->fetchAll();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0">
                <h5 style="color: #274e13;">Ajouter un livre</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="titre" class="form-control mb-2" placeholder="Titre" required>
                    <input type="text" name="auteur" class="form-control mb-2" placeholder="Auteur" required>
                    <input type="number" step="0.01" name="prix" class="form-control mb-2" placeholder="Prix (€)" required>
                    <select name="etat" class="form-select mb-2">
                        <option>Neuf</option><option>Bon état</option><option>Usagé</option>
                    </select>
                    <textarea name="description" class="form-control mb-2" rows="4" placeholder="Résumé du livre..."></textarea>
                    <input type="file" name="image" class="form-control mb-3" accept="image/*">
                    <button type="submit" name="ajouter_livre" class="btn text-white w-100" style="background-color: #274e13;">Ajouter</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3 shadow-sm border-0">
                <h5 style="color: #274e13;">Catalogue</h5>
                <table class="table table-hover mt-3">
                    <thead><tr><th>Image</th><th>Titre</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($livres as $l): ?>
                        <tr>
                            <td><img src="img/<?= $l['couverture'] ?>" style="width: 40px; height: 50px; object-fit: cover;"></td>
                            <td><?= htmlspecialchars($l['titre']) ?></td>
                            <td>
                                <a href="modifier_livre.php?id=<?= $l['id_livre'] ?>" class="btn btn-sm btn-outline-primary">Modif</a>
                                <a href="admin.php?delete=<?= $l['id_livre'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')">X</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>