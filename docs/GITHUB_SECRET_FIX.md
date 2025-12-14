# Fixing GitHub Secret Detection Issue

## Problem

GitHub's push protection detected a Stripe test API secret key (`sk_test_...`) in commit history, blocking the push.

## Current Status

✅ **Current code is correct** - `SubscriptionController.php` now uses `config('services.stripe.secret')`  
✅ **Stripe config added** - `config/services.php` now includes Stripe configuration  
✅ **Environment variables** - Secret should be in `.env` file (which is in `.gitignore`)

## Solution Options

### Option 1: Use GitHub's Allow Feature (Recommended for Test Keys)

Since this is a **test key** (`sk_test_...`) and has already been replaced in the code:

1. Visit the GitHub URL provided in the error:
   ```
   https://github.com/sethi-stack/healthymartina-laravel-backend/security/secret-scanning/unblock-secret/36EIWCyTp0FnOPPxt0o12sDd7cL
   ```

2. Click "Allow secret" (since it's a test key that's already been replaced)

3. Push again:
   ```bash
   git push -u origin main
   ```

### Option 2: Remove Secret from Git History (For Production Keys)

If this were a production key, you should remove it from history using BFG Repo-Cleaner:

```bash
# Install BFG (if not installed)
brew install bfg  # macOS
# or download from: https://rtyley.github.io/bfg-repo-cleaner/

# Remove the secret
bfg --replace-text <(echo 'sk_test_51JuWzYSJq8dY5hOx7BhhInr2ToPHT9Xdjy1mEBQs75J8z8zGmRL1UBowZcz893cgoJvMtJqhTmfaNIX6OJmEPWVf00MTgUS8ut==>config("services.stripe.secret")')

# Clean up
git reflog expire --expire=now --all && git gc --prune=now --aggressive

# Force push (WARNING: This rewrites history)
git push --force origin main
```

**⚠️ Warning**: Force pushing rewrites history. Coordinate with your team first.

### Option 3: Rotate the Test Key (Safest)

1. Go to Stripe Dashboard → Developers → API keys
2. Revoke the old test key
3. Create a new test key
4. Update `.env` with the new key
5. Use Option 1 to allow the old key in GitHub (it's now invalid anyway)

## Verification

After fixing, verify the current code doesn't have hardcoded secrets:

```bash
# Check current code
grep -r "sk_test" app/ config/ --exclude-dir=vendor

# Should return nothing (or only comments/documentation)
```

## Prevention

To prevent this in the future:

1. ✅ Always use environment variables for secrets
2. ✅ Never commit `.env` files (already in `.gitignore`)
3. ✅ Use `config()` helper instead of hardcoding
4. ✅ Use pre-commit hooks to scan for secrets (optional)
5. ✅ Rotate keys if accidentally committed

## Current Configuration

The application now correctly uses:

- `config('services.stripe.secret')` → reads from `config/services.php`
- `config/services.php` → reads from `.env` file
- `.env` → contains `STRIPE_SECRET=sk_test_...` (not in git)

This is the correct Laravel pattern for handling secrets.


