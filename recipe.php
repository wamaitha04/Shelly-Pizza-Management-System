<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(KITCHEN_ROLES, "index.php");

include '../config/db_connect.php';

$product_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
if ($product_id === 0) {
    header("Location: index.php");
    exit();
}

// Confirm the product actually exists, and grab its name for the heading.
$stmt = $conn->prepare("SELECT product_name, size FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: index.php");
    exit();
}

$success = "";

// ---------------------------------------------------------
// Save the recipe
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ingredient_ids = $_POST["ingredient_id"] ?? [];
    $quantities     = $_POST["quantity_required"] ?? [];

    // Simplest reliable approach: wipe this product's existing recipe,
    // then re-insert only the ingredients the user actually gave a
    // quantity greater than 0 for. This avoids needing to figure out
    // which rows are "new", "changed", or "removed" individually.
    $stmt = $conn->prepare("DELETE FROM recipe_items WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    for ($i = 0; $i < count($ingredient_ids); $i++) {
        $ingId = (int) $ingredient_ids[$i];
        $qty = (float) $quantities[$i];

        if ($ingId > 0 && $qty > 0) {
            $stmt = $conn->prepare(
                "INSERT INTO recipe_items (product_id, ingredient_id, quantity_required)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param("iid", $product_id, $ingId, $qty);
            $stmt->execute();
            $stmt->close();
        }
    }

    $success = "Recipe saved.";
}

// Load every ingredient, LEFT JOINed to this product's existing recipe
// so we can pre-fill quantities that were already set, and leave
// others blank/zero.
$stmt = $conn->prepare(
    "SELECT i.ingredient_id, i.ingredient_name, i.unit,
            IFNULL(ri.quantity_required, 0) AS quantity_required
     FROM ingredients i
     LEFT JOIN recipe_items ri ON ri.ingredient_id = i.ingredient_id AND ri.product_id = ?
     ORDER BY i.ingredient_name"
);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$ingredients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recipe - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="app-header">
        <div class="app-header-brand">
            <img src="../assets/img/logo.jpg" alt="Shelly's Pizza logo">
            Shelly's Pizza — Recipe
        </div>
        <div>
            <a href="../dashboard.php">Dashboard</a>
            &nbsp;|&nbsp;
            <a href="../auth/logout.php">Log Out</a>
        </div>
    </header>

    <main class="app-main">
        <div class="card">
            <h2>Recipe for <?php echo htmlspecialchars($product["product_name"] . " (" . $product["size"] . ")"); ?></h2>
            <p style="color: var(--color-caramel-dark); margin-bottom: 1.25rem; font-size: 0.9rem;">
                Enter how much of each ingredient is used to make <strong>one</strong> of this pizza.
                Leave a quantity at 0 for ingredients this pizza doesn't use.
            </p>

            <?php if ($success): ?>
                <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <form method="POST" action="recipe.php?id=<?php echo $product_id; ?>">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Unit</th>
                            <th>Quantity Used per Pizza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ing["ingredient_name"]); ?></td>
                                <td><?php echo htmlspecialchars($ing["unit"]); ?></td>
                                <td>
                                    <input type="hidden" name="ingredient_id[]" value="<?php echo $ing['ingredient_id']; ?>">
                                    <input type="number" step="0.001" min="0" name="quantity_required[]"
                                           value="<?php echo $ing['quantity_required'] > 0 ? $ing['quantity_required'] : ''; ?>"
                                           placeholder="0"
                                           style="width: 100px; padding: 0.4rem; border: 2px solid var(--color-border); border-radius: 6px;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" style="border:none; cursor:pointer;">Save Recipe</button>
                    <a href="index.php" class="btn-secondary">Back to Products</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
