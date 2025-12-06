#!/usr/bin/env python3
import re
import sys

file_path = "app/Http/Controllers/SubscriptionController.php"

try:
    with open(file_path, "r") as f:
        content = f.read()
    
    # Replace Stripe secret keys (handles multi-line)
    # Pattern: 'sk_test_...' (can span multiple lines)
    content = re.sub(
        r"'sk_test_[^']*'",
        "config('services.stripe.secret')",
        content,
        flags=re.DOTALL
    )
    
    # Also handle double quotes
    content = re.sub(
        r'"sk_test_[^"]*"',
        "config('services.stripe.secret')",
        content,
        flags=re.DOTALL
    )
    
    with open(file_path, "w") as f:
        f.write(content)
except FileNotFoundError:
    pass  # File doesn't exist in this commit
except Exception as e:
    sys.exit(1)


