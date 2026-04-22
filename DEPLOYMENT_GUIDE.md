# FixGrid PWA — Complete Deployment Guide
## Customer App + Engineer App → Google Play Store

---

## STEP 1 — Upload Files to cPanel

Upload everything from this package to your server (`public_html/`):

### Customer App (`public_html/customer-app/`)
| File | Action |
|------|--------|
| `manifest.json` | Upload NEW |
| `sw-customer.js` | Upload NEW |
| `icon-72.png` through `icon-512.png` | Upload NEW (10 icon files) |
| `icon-maskable-192.png`, `icon-maskable-512.png` | Upload NEW |

### Engineer App (`public_html/engineer-app/`)
| File | Action |
|------|--------|
| `manifest.json` | Upload NEW |
| `sw-engineer.js` | Upload NEW |
| `icon-72.png` through `icon-512.png` | Upload NEW (10 icon files) |
| `icon-maskable-192.png`, `icon-maskable-512.png` | Upload NEW |

---

## STEP 2 — Edit customer.php

### 2a. In the `<head>` section, add after `<meta name="theme-color">`:

```html
<!-- PWA Manifest -->
<link rel="manifest" href="/customer-app/manifest.json">

<!-- iOS PWA support -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="FixGrid">
<link rel="apple-touch-icon" sizes="152x152" href="/customer-app/icon-152.png">
<link rel="apple-touch-icon" sizes="192x192" href="/customer-app/icon-192.png">
<meta name="msapplication-TileImage" content="/customer-app/icon-144.png">
<meta name="msapplication-TileColor" content="#0B3C5D">
<meta name="format-detection" content="telephone=no">
```

### 2b. Replace the initPush() function body (~line 2256) with the contents of `customer-app/JS_PATCH.js`

(Keep the function declaration `async function initPush() {` and closing `}` — only replace the body)

---

## STEP 3 — Edit engineer.php

### 3a. In the `<head>` section, add after `<meta name="theme-color">`:

```html
<link rel="manifest" href="/engineer-app/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="FG Engineer">
<link rel="apple-touch-icon" sizes="192x192" href="/engineer-app/icon-192.png">
<meta name="format-detection" content="telephone=no">
```

### 3b. Replace the registerFcmToken() function body (~line 1564) with `engineer-app/JS_PATCH.js`

---

## STEP 4 — Add Digital Asset Links (Required for Play Store)

This tells Android that your domain owns these apps. Without this, the TWA
shows a browser bar and won't pass Play Store review.

Create file: `public_html/.well-known/assetlinks.json`

```json
[
  {
    "relation": ["delegate_permission/common.handle_all_urls"],
    "target": {
      "namespace": "android_app",
      "package_name": "in.fixgrid.customer",
      "sha256_cert_fingerprints": ["REPLACE_WITH_YOUR_KEYSTORE_SHA256"]
    }
  },
  {
    "relation": ["delegate_permission/common.handle_all_urls"],
    "target": {
      "namespace": "android_app",
      "package_name": "in.fixgrid.engineer",
      "sha256_cert_fingerprints": ["REPLACE_WITH_YOUR_KEYSTORE_SHA256"]
    }
  }
]
```

> ⚠️ Replace `REPLACE_WITH_YOUR_KEYSTORE_SHA256` with the actual SHA-256
> fingerprint from your keystore (you get this in Step 5).

Also make sure your cPanel `.htaccess` serves this with correct MIME type.
Add to `public_html/.htaccess`:
```apache
# Digital Asset Links
<Files "assetlinks.json">
  Header set Content-Type "application/json"
  Header set Access-Control-Allow-Origin "*"
</Files>
```

---

## STEP 5 — Build the TWA APKs (on your local PC)

You need: **Node.js 18+**, **Java JDK 17**, **Android SDK**

```bash
# Install Bubblewrap CLI
npm install -g @bubblewrap/cli

# ── Customer App ──────────────────────────
mkdir fixgrid-customer && cd fixgrid-customer
bubblewrap init --manifest=https://www.fixgrid.in/customer-app/manifest.json

# Bubblewrap will ask you questions — use these answers:
#   Package ID:      in.fixgrid.customer
#   App name:        FixGrid — Book a Service
#   Launcher name:   FixGrid
#   Theme color:     #0B3C5D
#   Background:      #0B3C5D
#   Start URL:       /customer-app/customer.php
#   Keystore path:   ./android.keystore
#   Key alias:       fixgrid-customer
#   Key password:    (choose a strong password)

# Build the APK
bubblewrap build

# This creates: app-release-signed.apk  ← upload this to Play Store

# Get the SHA-256 fingerprint for assetlinks.json
keytool -list -v -keystore ./android.keystore -alias fixgrid-customer | grep SHA256
# Copy this value into assetlinks.json ↑

cd ..

# ── Engineer App ──────────────────────────
mkdir fixgrid-engineer && cd fixgrid-engineer
bubblewrap init --manifest=https://www.fixgrid.in/engineer-app/manifest.json

# Use these answers:
#   Package ID:      in.fixgrid.engineer
#   App name:        FixGrid Engineer
#   Launcher name:   FG Engineer
#   Theme color:     #0B3C5D
#   Background:      #0F1117
#   Start URL:       /engineer-app/engineer.php
#   Keystore path:   ./android.keystore
#   Key alias:       fixgrid-engineer

bubblewrap build
keytool -list -v -keystore ./android.keystore -alias fixgrid-engineer | grep SHA256
```

> 💾 **IMPORTANT:** Save your `.keystore` file and password safely.
> If you lose the keystore, you cannot update the app on Play Store — ever.

---

## STEP 6 — Upload to Google Play Console

1. Go to https://play.google.com/console
2. Create two new apps: "FixGrid" and "FixGrid Engineer"
3. For each app:
   - **App type:** App
   - **Category:** Tools or Productivity
   - Upload `app-release-signed.apk`
   - Fill in store listing (description, screenshots, icon)
   - Set **Content Rating** (complete the questionnaire)
   - Set countries: India (+ others if needed)
   - Submit for review (typically 1–7 days)

---

## STEP 7 — Test PWA Before Play Store

Before submitting, test it works as a PWA:

1. Open Chrome on Android
2. Go to `https://www.fixgrid.in/customer-app/customer.php`
3. Tap ⋮ menu → "Add to Home screen"
4. Launch from home screen → should look native (no browser bar)
5. Check offline: turn off WiFi → app should still load

**Verify with Lighthouse:**
- Open Chrome DevTools → Lighthouse → Mobile
- Run audit → should score ✅ PWA: Installable

---

## STEP 8 — Verify assetlinks.json

After uploading, confirm it's accessible:
```
https://www.fixgrid.in/.well-known/assetlinks.json
```

Use Google's tool to verify: https://digitalassetlinks.googleapis.com/v1/statements:list?source.web.site=https://www.fixgrid.in&relation=delegate_permission/common.handle_all_urls

---

## Summary Checklist

- [ ] Icons uploaded to `customer-app/` and `engineer-app/`
- [ ] `manifest.json` uploaded to both folders
- [ ] `sw-customer.js` uploaded to `customer-app/`
- [ ] `sw-engineer.js` uploaded to `engineer-app/`
- [ ] `<head>` patches added to `customer.php` and `engineer.php`
- [ ] JS patches applied to `initPush()` and `registerFcmToken()`
- [ ] `.well-known/assetlinks.json` created with correct SHA256
- [ ] TWA APKs built with Bubblewrap
- [ ] Both apps submitted to Play Console
- [ ] Keystore file backed up safely

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Browser bar shows in TWA | `assetlinks.json` wrong or not deployed |
| Install prompt not showing | Must be HTTPS + manifest + SW registered |
| Push notifications not working | Check Firebase config in Admin → Settings |
| SW not registering | Check browser console for SW errors |
| iOS install prompt | iOS uses "Add to Home Screen" in Safari share menu — no auto-prompt |
