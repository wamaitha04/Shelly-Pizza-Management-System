<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/auth.php';
requireRole(SALES_ROLES);

include '../config/db_connect.php';
$activePage = "new_sale";

$success = "";
$error = "";

// Pull product_id in too — the dropdown now submits the id, not the
// name, so stock and recipe checks can be done reliably even if two
// products happen to share a name.
$products = $conn->query("SELECT product_id, product_name, price FROM products ORDER BY product_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rawItems = $_POST["items"] ?? [];

    // Combine rows for the same product (e.g. someone added the same
    // pizza in two separate rows) into a single requested quantity,
    // so stock is checked against the true total being ordered.
    $cart = []; // product_id => ["qty" => int, "price" => float]
    foreach ($rawItems as $item) {
        $productId = (int) ($item["product_id"] ?? 0);
        $qty       = (int) ($item["quantity"] ?? 0);
        $price     = (float) ($item["unit_price"] ?? 0);

        if ($productId > 0 && $qty > 0 && $price >= 0) {
            if (!isset($cart[$productId])) {
                $cart[$productId] = ["qty" => 0, "price" => $price];
            }
            $cart[$productId]["qty"] += $qty;
            $cart[$productId]["price"] = $price; // last submitted price wins
        }
    }

    if (empty($cart)) {
        $error = "Add at least one item.";
    } else {
        // Everything below runs inside one transaction: either the
        // whole sale is valid and gets recorded (with stock deducted),
        // or nothing is written at all.
        $conn->begin_transaction();

        $stockErrors     = [];
        $lineItems       = [];   // product_id => [name, qty, price]
        $ingredientNeeds = [];   // ingredient_id => total quantity required

        foreach ($cart as $productId => $entry) {
            $qty = $entry["qty"];

            // There's no separate "finished stock" number to check —
            // how many of this pizza are available is entirely a
            // function of its recipe and the ingredients on hand,
            // checked below. Just confirm the product still exists.
            $stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ? FOR UPDATE");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product) {
                $stockErrors[] = "One of the selected products no longer exists.";
                continue;
            }

            $lineItems[$productId] = [$product["product_name"], $qty, $entry["price"]];

            // A pizza with no recipe defined can't be sold — we have
            // no way to confirm the kitchen can actually make it.
            $stmt = $conn->prepare(
                "SELECT ingredient_id, quantity_required FROM recipe_items WHERE product_id = ?"
            );
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $recipe = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($recipe)) {
                $stockErrors[] = "\"{$product['product_name']}\" has no recipe set yet, so it can't be sold.";
                continue;
            }

            foreach ($recipe as $r) {
                $needed = $r["quantity_required"] * $qty;
                $ingredientNeeds[$r["ingredient_id"]] = ($ingredientNeeds[$r["ingredient_id"]] ?? 0) + $needed;
            }
        }

        // Check every ingredient this order would consume against
        // what's actually in stock right now.
        foreach ($ingredientNeeds as $ingredientId => $needed) {
            $stmt = $conn->prepare(
                "SELECT ingredient_name, unit, quantity_in_stock
                 FROM ingredients WHERE ingredient_id = ? FOR UPDATE"
            );
            $stmt->bind_param("i", $ingredientId);
            $stmt->execute();
            $ing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($ing && $needed > $ing["quantity_in_stock"]) {
                $neededDisplay    = rtrim(rtrim(number_format($needed, 3), '0'), '.');
                $available        = rtrim(rtrim(number_format($ing["quantity_in_stock"], 3), '0'), '.');
                $stockErrors[] = "Not enough \"{$ing['ingredient_name']}\": this order needs {$neededDisplay} {$ing['unit']}, only {$available} {$ing['unit']} in stock.";
            }
        }

        if (!empty($stockErrors)) {
            // Reject the whole sale — nothing gets recorded and no
            // stock is touched.
            $conn->rollback();
            $error = implode(" ", $stockErrors);
        } else {
            $total = 0;
            foreach ($lineItems as [$name, $qty, $price]) {
                $total += $qty * $price;
            }

            $stmt = $conn->prepare(
                "INSERT INTO sales (user_id, sale_date, total_amount) VALUES (?, NOW(), ?)"
            );
            $stmt->bind_param("id", $_SESSION["user_id"], $total);
            $stmt->execute();
            $sale_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO sale_items (sale_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?)"
            );
            foreach ($lineItems as [$name, $qty, $price]) {
                $stmt->bind_param("isid", $sale_id, $name, $qty, $price);
                $stmt->execute();
            }
            $stmt->close();

            // Deduct the raw ingredients this sale consumed, per each
            // product's recipe. There's no separate finished-product
            // stock to deduct — reducing ingredients here is exactly
            // what brings each pizza's computed "makeable" quantity
            // down, everywhere it's displayed.
            if (!empty($ingredientNeeds)) {
                $stmt = $conn->prepare(
                    "UPDATE ingredients SET quantity_in_stock = quantity_in_stock - ? WHERE ingredient_id = ?"
                );
                foreach ($ingredientNeeds as $ingredientId => $needed) {
                    $stmt->bind_param("di", $needed, $ingredientId);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $conn->commit();

            header("Location: receipt.php?id=" . $sale_id);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Sale - Shelly's Pizza</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="card" style="max-width:600px; margin:0 auto;">
            <h2>Record New Sale</h2>

            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="new_sale.php">
                <div id="items-container">
                    <div class="sale-item-row" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:.5rem; margin-bottom:.5rem;">
                        <select name="items[0][product_id]" onchange="fillPrice(this, 0)" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['product_id']; ?>"
                                        data-price="<?php echo $p['price']; ?>">
                                    <?php echo htmlspecialchars($p['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="items[0][quantity]" value="1" min="1" placeholder="Qty" required>
                        <input type="number" name="items[0][unit_price]" id="price_0" step="0.01" placeholder="Price" required>
                        <span></span>
                    </div>
                </div>

                <button type="button" onclick="addRow()" class="btn-secondary" style="margin-bottom:1rem;">+ Add Item</button>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" style="border:none; cursor:pointer;">Save Sale</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Product options HTML built once by PHP so addRow() can reuse it safely
const productOptionsHTML = `<?php
    foreach ($products as $p) {
        echo '<option value="' . $p['product_id'] . '" data-price="' . $p['price'] . '">'
           . htmlspecialchars($p['product_name'], ENT_QUOTES) . '</option>';
    }
?>`;

// Keyed by product_id now, since names alone aren't a safe/unique key.
const products = <?php echo json_encode(array_column($products, 'price', 'product_id')); ?>;
let rowCount = 1;

function fillPrice(select, index) {
    const price = products[select.value] ?? '';
    document.getElementById('price_' + index).value = price;
}

function addRow() {
    const i = rowCount++;
    const container = document.getElementById('items-container');
    container.insertAdjacentHTML('beforeend', `
        <div class="sale-item-row" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:.5rem; margin-bottom:.5rem;">
            <select name="items[${i}][product_id]" onchange="fillPrice(this, ${i})" required>
                <option value="">-- Select Product --</option>
                ${productOptionsHTML}
            </select>
            <input type="number" name="items[${i}][quantity]" value="1" min="1" placeholder="Qty" required>
            <input type="number" name="items[${i}][unit_price]" id="price_${i}" step="0.01" placeholder="Price" required>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:red;cursor:pointer;font-size:1.2rem;">✕</button>
        </div>
    `);
}
</script>
</body>
</html>
