<?php
/**
 * includes/validation.php
 * -----------------------------------------------------------------
 * Shared validation helpers used by registration and user management
 * (auth/register.php, users/add.php, users/edit.php).
 * Keeping these in one place means the rules for "what counts as a
 * valid email/phone/strong password" are defined once, not copy-
 * pasted (and slowly drifting out of sync) across three forms.
 * -----------------------------------------------------------------
 */

/**
 * True if $email is a syntactically valid email address.
 * This checks FORMAT only — it does not check whether the address
 * actually exists or belongs to the person, and it does not check
 * whether it's already registered (that's a separate uniqueness
 * check against the database).
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Strips spaces and dashes so "0712 345 678" and "0712-345-678" and
 * "0712345678" are all treated as the same number. Store and compare
 * phone numbers using this normalized form so the same person can't
 * accidentally register twice with visually-different versions of
 * the same number.
 */
function normalizePhone(string $phone): string
{
    return preg_replace('/[\s\-]/', '', trim($phone));
}

/**
 * True if $phone (after normalizing) is a plausible phone number:
 * an optional leading "+", then 7-15 digits. This intentionally
 * doesn't try to validate against real-world numbering plans —
 * just catches obviously-wrong input (letters, way too short/long).
 */
function isValidPhone(string $phone): bool
{
    $cleaned = normalizePhone($phone);
    return (bool) preg_match('/^\+?[0-9]{7,15}$/', $cleaned);
}

/**
 * Checks password strength and returns a specific, human-readable
 * error message describing the FIRST rule that failed — or null if
 * the password satisfies every rule (i.e. it's strong enough).
 *
 * Returning the specific failure (rather than just true/false) is
 * what lets the registration/edit forms tell someone exactly what
 * to fix ("add a number") instead of a vague "weak password".
 */
function passwordStrengthError(string $password): ?string
{
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must include at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must include at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must include at least one number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Password must include at least one special character (e.g. ! @ # $ %).";
    }
    return null;
}
