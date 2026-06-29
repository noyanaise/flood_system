#!/bin/bash
set -e

# Clear out any overlapping Apache MPM modules that cause Railway to crash
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2dismod mpm_prefork 2>/dev/null || true

# Force enable ONLY prefork (required for native PHP execution)
a2enmod mpm_prefork

# Hand execution control back to the main Apache runtime engine
exec apache2-foreground
