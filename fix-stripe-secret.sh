#!/bin/bash
# Script to replace Stripe secret key in SubscriptionController.php

FILE="app/Http/Controllers/SubscriptionController.php"

if [ -f "$FILE" ]; then
    # Replace the hardcoded Stripe secret with config call
    # Handle both single-line and multi-line cases
    perl -i -pe 'BEGIN{undef $/;} s/'\''sk_test_[^'\'']*'\''/config("services.stripe.secret")/gs' "$FILE"
    perl -i -pe 'BEGIN{undef $/;} s/"sk_test_[^"]*"/config("services.stripe.secret")/gs' "$FILE"
fi



