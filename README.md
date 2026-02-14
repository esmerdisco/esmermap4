# EsmerMap (Android)

This repo contains a simple Android wrapper app (Kotlin) that:

- Shows an **animated splash screen** using your two provided images
- Opens your local web app via **WebView** from `app/src/main/assets/web/location/index.html`

## Where the splash animation is
- `app/src/main/java/com/esmermap/app/SplashActivity.kt`
- `app/src/main/res/layout/activity_splash.xml`

## Build locally (Android Studio)
Open the project in Android Studio and run **app**.

## Build on GitHub Actions
Push to GitHub — the workflow will build a Debug APK and upload it as an artifact.
Workflow: `.github/workflows/android-build.yml`
