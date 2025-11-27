# ✅ Render Deployment Checklist

Print this checklist and check off each item as you complete it!

---

## 📋 BEFORE YOU START

- [ ] I have a Git repository (GitHub/GitLab/Bitbucket)
- [ ] My code is committed and pushed to Git
- [ ] I have PHP and Composer installed locally
- [ ] I'm ready to spend 30-60 minutes for deployment

---

## 🏠 LOCAL PREPARATION

- [ ] Generated APP_KEY: `php artisan key:generate --show`
- [ ] ✅ **Copied APP_KEY and saved it somewhere safe**
- [ ] Created session table migration: `php artisan session:table`
- [ ] Committed all changes: `git add . && git commit -m "Prepare for Render" && git push`

---

## 🔐 CREATE RENDER ACCOUNT

- [ ] Signed up at https://render.com
- [ ] Connected GitHub/GitLab/Bitbucket account
- [ ] Account is verified

---

## 🗄️ CREATE DATABASE (STEP 3)

- [ ] Clicked "New +" → "PostgreSQL"
- [ ] Set Name: `lgu-document-db`
- [ ] Set Database: `lgu_document_tracking`
- [ ] Set User: `lgu_admin`
- [ ] Selected "Free" plan
- [ ] Clicked "Create Database"
- [ ] ✅ **Copied database connection details:**
  - [ ] Host: `_________________________`
  - [ ] Port: `5432`
  - [ ] Database: `lgu_document_tracking`
  - [ ] Username: `lgu_admin`
  - [ ] Password: `_________________________`

---

## 🌐 CREATE WEB SERVICE (STEP 4)

- [ ] Clicked "New +" → "Web Service"
- [ ] Connected repository: `lgu-document-tracking`
- [ ] Set Name: `lgu-document-tracking`
- [ ] Set Environment: `PHP`
- [ ] Set Region: (same as database)
- [ ] Set Branch: `main` (or `master`)
- [ ] Set Build Command: (copied from guide)
- [ ] Set Start Command: (copied from guide)
- [ ] Selected "Free" plan
- [ ] Clicked "Create Web Service"
- [ ] ✅ **Copied my app URL: `https://_________________.onrender.com`**

---

## 🔑 ADD ENVIRONMENT VARIABLES (STEP 5)

Add each variable and check it off:

### Basic App Variables
- [ ] `APP_NAME` = `LGU Document Tracking System`
- [ ] `APP_ENV` = `production`
- [ ] `APP_KEY` = `base64:...` (from Step 1)
- [ ] `APP_DEBUG` = `false`
- [ ] `APP_URL` = `https://your-app.onrender.com` (your URL)

### Database Variables
- [ ] `DB_CONNECTION` = `pgsql`
- [ ] `DB_HOST` = (from database Info tab)
- [ ] `DB_PORT` = `5432`
- [ ] `DB_DATABASE` = `lgu_document_tracking`
- [ ] `DB_USERNAME` = `lgu_admin`
- [ ] `DB_PASSWORD` = (from database Info tab)

### Session & Cache Variables
- [ ] `SESSION_DRIVER` = `database` ⭐ **IMPORTANT!**
- [ ] `SESSION_LIFETIME` = `120`
- [ ] `CACHE_DRIVER` = `file`
- [ ] `LOG_LEVEL` = `error`
- [ ] `QUEUE_CONNECTION` = `sync`

---

## 🚀 DEPLOY (STEP 6)

- [ ] Went to "Manual Deploy" tab
- [ ] Clicked "Clear build cache & deploy"
- [ ] Waited for build to complete (5-10 minutes)
- [ ] ✅ Build was successful!

---

## 📊 RUN MIGRATIONS (STEP 7)

Open Shell and run these commands:

- [ ] `php artisan session:table`
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --force` (optional)
- [ ] `php artisan storage:link`
- [ ] `mkdir -p public/qrcodes`
- [ ] `chmod -R 775 storage bootstrap/cache public/qrcodes`
- [ ] `php artisan config:clear`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan migrate:status` (to verify)

---

## ✅ TEST YOUR APP (STEP 8)

- [ ] Visited app URL
- [ ] Waited for app to wake up (30-60 seconds if sleeping)
- [ ] ✅ App loaded successfully!
- [ ] Tested login:
  - Email: `admin@lgu.gov`
  - Password: `password`
- [ ] ✅ Login successful!
- [ ] Tested creating a document
- [ ] Tested QR code generation
- [ ] ✅ Everything works!

---

## 🎉 DEPLOYMENT COMPLETE!

- [ ] My app is live at: `https://_________________.onrender.com`
- [ ] I can login and use all features
- [ ] I understand free tier limitations (sleep, ephemeral storage)
- [ ] I know how to update the app (git push)

---

## 🐛 IF SOMETHING WENT WRONG

Check these common issues:

- [ ] Checked "Logs" tab for errors
- [ ] Verified all environment variables are correct
- [ ] Checked database is running (Status = "Available")
- [ ] Verified migrations ran successfully
- [ ] Tried clearing cache: `php artisan config:clear`

---

## 📚 NEED HELP?

- [ ] Read full guide: `DEPLOYMENT_STEP_BY_STEP.md`
- [ ] Check Render logs
- [ ] Review Render documentation: https://render.com/docs

---

**🎊 CONGRATULATIONS! Your app is deployed! 🎊**

