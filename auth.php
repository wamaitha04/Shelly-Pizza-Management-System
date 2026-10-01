<?php
/**
 * includes/auth.php
 * -----------------------------------------------------------------
 * Central place for role names and permission checks, so every page
 * asks the same question the same way instead of repeating
 * `$_SESSION["role"] === "owner"` everywhere.
 *
 * Roles:
 *   owner    - full access to everything (only role that can manage
 *              other management-level accounts)
 *   manager  - can view staff performance and add/manage cashier,
 *              waiter & cook accounts, but cannot touch owner/manager
 *              accounts
 *   cashier  - front-of-house: can view the product list and
 *              register sales. No access to recipes, ingredients,
 *              or inventory.
 *   waiter   - same access as cashier
 *   cook     - back-of-house: can view products, manage recipes,
 *              ingredients, and inventory. No access to sales,
 *              sales history, or admin features.
 *
 * Include this file AFTER session_start(). It does not start a
 * session itself and does not touch the database.
 * -----------------------------------------------------------------
 */

// Roles allowed to view staff performance reports and manage user accounts.
const MANAGEMENT_ROLES = ['owner', 'manager'];

// Roles allowed to manage recipes, ingredients, and inventory
// ("back of house").
const KITCHEN_ROLES = ['owner', 'manager', 'cook'];

// Roles allowed to register sales and view sales history
// ("front of house").
const SALES_ROLES = ['owner', 'manager', 'cashier', 'waiter'];

// Roles that represent day-to-day operational "sub-user" staff —
// i.e. everyone a manager is allowed to create/edit/delete.
const SUBUSER_ROLES = ['cashier', 'waiter', 'cook'];

// Every role the system knows about.
const ALL_ROLES = ['owner', 'manager', 'cashier', 'waiter', 'cook'];

/**
 * The role of whoever is currently logged in, or "" if nobody is.
 */
function currentRole(): string
{
    return $_SESSION["role"] ?? "";
}

/**
 * True for Owner and Manager — the two roles that can see staff
 * performance and manage user accounts.
 */
function isManagement(): bool
{
    return in_array(currentRole(), MANAGEMENT_ROLES, true);
}

/**
 * True only for the Owner. Some actions (deleting a manager,
 * promoting someone to manager/owner) are reserved for the Owner
 * alone, even though managers can otherwise manage staff.
 */
function isOwner(): bool
{
    return currentRole() === "owner";
}

/**
 * True for anyone allowed to manage recipes, ingredients, and
 * inventory (Owner, Manager, Cook).
 */
function isKitchen(): bool
{
    return in_array(currentRole(), KITCHEN_ROLES, true);
}

/**
 * True for anyone allowed to register sales and view sales history
 * (Owner, Manager, Cashier, Waiter).
 */
function canSell(): bool
{
    return in_array(currentRole(), SALES_ROLES, true);
}

/**
 * Which roles the CURRENTLY LOGGED IN user is allowed to assign to
 * someone else. Owners can assign any role; managers can only create
 * or edit cashier/waiter/cook accounts (so a manager can never grant
 * themselves or anyone else owner/manager access).
 */
function assignableRoles(): array
{
    return isOwner() ? ALL_ROLES : SUBUSER_ROLES;
}

/**
 * Redirects away (defaults to the dashboard) if the logged-in user's
 * role isn't in $allowedRoles. Call this right after the "are you
 * logged in at all" check.
 */
function requireRole(array $allowedRoles, string $redirectTo = "../dashboard.php"): void
{
    if (!in_array(currentRole(), $allowedRoles, true)) {
        header("Location: " . $redirectTo);
        exit();
    }
}
