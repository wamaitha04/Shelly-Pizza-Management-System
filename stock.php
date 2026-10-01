<?php
/**
 * includes/stock.php
 * -----------------------------------------------------------------
 * "How many of this pizza can we make right now?" is not a number
 * anyone types in — it's calculated from the recipe (recipe_items)
 * against what's actually sitting in the ingredients store. That
 * way the two can never drift out of sync: if the kitchen doesn't
 * physically have enough ingredients, the system won't claim the
 * pizza is in stock.
 *
 * A product with NO recipe defined yet is treated as 0 in stock,
 * on purpose — we don't guess how many we could make of something
 * whose ingredient cost hasn't been entered.
 * -----------------------------------------------------------------
 */

/**
 * Makeable quantity for a single product.
 */
function computeMakeableQuantity(mysqli $conn, int $productId): int
{
    $stmt = $conn->prepare(
        "SELECT ing.quantity_in_stock, ri.quantity_required
         FROM recipe_items ri
         JOIN ingredients ing ON ri.ingredient_id = ing.ingredient_id
         WHERE ri.product_id = ?"
    );
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
        return 0;
    }

    $makeable = null;
    foreach ($rows as $r) {
        if ((float) $r["quantity_required"] <= 0) {
            continue; // avoid divide-by-zero on a malformed recipe row
        }
        $possible = (int) floor($r["quantity_in_stock"] / $r["quantity_required"]);
        $makeable = ($makeable === null) ? $possible : min($makeable, $possible);
    }

    return max($makeable ?? 0, 0);
}

/**
 * Makeable quantity for EVERY product that has a recipe, in one
 * query, keyed by product_id. Products with no recipe simply won't
 * have a key here — treat a missing key as 0. Used on list pages so
 * we don't run one query per row.
 */
function computeMakeableQuantities(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT ri.product_id,
                MIN(FLOOR(ing.quantity_in_stock / ri.quantity_required)) AS makeable
         FROM recipe_items ri
         JOIN ingredients ing ON ri.ingredient_id = ing.ingredient_id
         WHERE ri.quantity_required > 0
         GROUP BY ri.product_id"
    );

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[(int) $row["product_id"]] = max((int) $row["makeable"], 0);
    }
    return $map;
}
