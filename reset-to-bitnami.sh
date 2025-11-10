#!/bin/bash
git fetch origin
git reset --hard origin/main
npm install
echo "✅ Synced with Bitnami"
