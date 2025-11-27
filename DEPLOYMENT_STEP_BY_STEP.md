# 🚀 Step-by-Step Deployment Guide to Render.com (FREE TIER)

This guide will walk you through deploying your LGU Document Tracking System to Render.com step by step. Follow each step carefully!

---

## 📋 PRE-DEPLOYMENT CHECKLIST

Before we start, make sure you have:

- [ ] Your code is in a Git repository (GitHub, GitLab, or Bitbucket)
- [ ] You have a GitHub/GitLab/Bitbucket account
- [ ] You have access to your local Laravel project
- [ ] PHP and Composer installed locally (to generate APP_KEY)

---

## STEP 1: Prepare Your Local Application

### 1.1 Generate Application Key

**Run this locally in your project directory:**

```bash
php artisan key:generate --show
```

**📝 Copy the key that appears!** It looks like:
```
base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Save this somewhere safe** - you'll need it later!

### 1.2 Create Session Table Migration (if not exists)

Since we'll use database sessions on Render, we need to create the sessions table:

```bash
php artisan session:table
```

This creates a migration file. **Don't run the migration locally yet** - we'll do it on Render.

### 1.3 Check if your code is pushed to Git

Make sure all your code is committed and pushed to your repository:

```bash
git status
git add .
git commit -m "Prepare for Render deployment"
git push
```

---

## STEP 2: Create Render.com Account

### 2.1 Sign Up

1. Go to **https://render.com**
2. Click **"Get Started"** or **"Sign Up"**
3. Choose **"Sign up with GitHub"** (or GitLab/Bitbucket if you prefer)
4. Authorize Render to access your repository
5. **✅ No credit card required!**

### 2.2 Verify Account

Check your email if needed to verify your account.

---

## STEP 3: Create PostgreSQL Database

### 3.1 Create Database Service

1. In Render Dashboard, click **"New +"** button (top right)
2. Select **"PostgreSQL"**
3. Configure the database:
   - **Name**: `lgu-document-db`
   - **Database**: `lgu_document_tracking`
   - **User**: `lgu_admin`
   - **Region**: Choose closest to you (e.g., `Oregon (US West)`)
   - **PostgreSQL Version**: Latest (default)
   - **Plan**: **Select "Free"** ⭐
   - **Data Retention**: (Free tier has 90 days - this is fine for testing)
4. Click **"Create Database"**

### 3.2 Wait for Database to Start

- Wait 1-2 minutes for the database to be created
- Status should change to "Available"

### 3.3 Copy Database Connection Details

Once the database is ready:

1. Click on your database service name (`lgu-document-db`)
2. Go to the **"Info"** tab
3. **📝 Copy these details** (you'll need them in Step 5):
   - **Internal Database URL** (or individual fields)
   - **Host**
   - **Port** (usually `5432`)
   - **Database Name** (`lgu_document_tracking`)
   - **User** (`lgu_admin`)
   - **Password** (click "Show" to reveal)

**Save these details somewhere safe!**

---

## STEP 4: Create Web Service (Laravel App)

### 4.1 Create New Web Service

1. In Render Dashboard, click **"New +"** button
2. Select **"Web Service"**

### 4.2 Connect Your Repository

1. You'll see a list of your repositories
2. **Find and click** your `lgu-document-tracking` repository
3. If you don't see it, click **"Configure account"** to connect more repos

### 4.3 Configure Web Service

Fill in these settings:

#### Basic Settings:

- **Name**: `lgu-document-tracking` (or any name you like)
- **Environment**: Select **"PHP"**
- **Region**: Same region as your database (e.g., `Oregon (US West)`)
- **Branch**: `main` (or `master` - whatever your default branch is)
- **Root Directory**: (leave empty)
- **Runtime**: `PHP` (should auto-detect)
- **Build Command**: Copy and paste this:
  ```bash
  composer install --optimize-autoloader --no-dev && npm ci && npm run build && php artisan storage:link || true
  ```
- **Start Command**: Copy and paste this:
  ```bash
  php artisan serve --host 0.0.0.0 --port $PORT
  ```
- **Plan**: **Select "Free"** ⭐

#### Advanced Settings (click "Advanced"):

- **Health Check Path**: `/` (leave as default)
- **Auto-Deploy**: **Yes** (so it redeploys when you push to Git)

### 4.4 Click "Create Web Service"

Don't add environment variables yet - we'll do that next!

**⚠️ Wait for the first build to complete** (this might take 5-10 minutes). You'll see build logs. **It's OK if it fails on first build** - we need to add environment variables first!

---

## STEP 5: Configure Environment Variables

### 5.1 Add Environment Variables

1. In your web service page, go to **"Environment"** tab (left sidebar)
2. Click **"Add Environment Variable"** button

### 5.2 Add Each Variable One by One

Add these variables one at a time:

#### Required Variables:

**1. APP_NAME**
- **Key**: `APP_NAME`
- **Value**: `LGU Document Tracking System`
- Click **"Save Changes"**

**2. APP_ENV**
- **Key**: `APP_ENV`
- **Value**: `production`
- Click **"Save Changes"**

**3. APP_KEY** (IMPORTANT!)
- **Key**: `APP_KEY`
- **Value**: Paste the key you generated in Step 1.1
- It should look like: `base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
- Click **"Save Changes"**

**4. APP_DEBUG**
- **Key**: `APP_DEBUG`
- **Value**: `false`
- Click **"Save Changes"**

**5. APP_URL**
- **Key**: `APP_URL`
- **Value**: `https://lgu-document-tracking.onrender.com` (use YOUR service URL)
- To find your URL: Go to your service → "Info" tab → copy the URL
- Click **"Save Changes"**

**6. DB_CONNECTION**
- **Key**: `DB_CONNECTION`
- **Value**: `pgsql` (PostgreSQL, not MySQL!)
- Click **"Save Changes"**

**7. DB_HOST**
- **Key**: `DB_HOST`
- **Value**: Copy from your database "Info" tab (e.g., `dpg-xxxxx-a.oregon-postgres.render.com`)
- Click **"Save Changes"**

**8. DB_PORT**
- **Key**: `DB_PORT`
- **Value**: `5432` (default PostgreSQL port)
- Click **"Save Changes"**

**9. DB_DATABASE**
- **Key**: `DB_DATABASE`
- **Value**: `lgu_document_tracking`
- Click **"Save Changes"**

**10. DB_USERNAME**
- **Key**: `DB_USERNAME`
- **Value**: `lgu_admin`
- Click **"Save Changes"**

**11. DB_PASSWORD**
- **Key**: `DB_PASSWORD`
- **Value**: Copy the password from your database "Info" tab
- Click **"Show"** next to password in database info to reveal it
- Click **"Save Changes"**

**12. SESSION_DRIVER** (IMPORTANT!)
- **Key**: `SESSION_DRIVER`
- **Value**: `database` (must be database, not file!)
- Click **"Save Changes"**

**13. SESSION_LIFETIME**
- **Key**: `SESSION_LIFETIME`
- **Value**: `120`
- Click **"Save Changes"**

**14. CACHE_DRIVER**
- **Key**: `CACHE_DRIVER`
- **Value**: `file`
- Click **"Save Changes"**

**15. LOG_LEVEL**
- **Key**: `LOG_LEVEL`
- **Value**: `error`
- Click **"Save Changes"**

**16. QUEUE_CONNECTION**
- **Key**: `QUEUE_CONNECTION`
- **Value**: `sync`
- Click **"Save Changes"**

### 5.3 Verify All Variables

Scroll through your environment variables list and make sure all 16 variables are there and correct!

---

## STEP 6: Trigger a New Deployment

After adding all environment variables:

1. Go to **"Manual Deploy"** tab (left sidebar)
2. Click **"Clear build cache & deploy"**
3. Wait for the deployment to complete (5-10 minutes)

**Watch the build logs** to see progress. It should:
- ✅ Install Composer dependencies
- ✅ Install NPM packages
- ✅ Build frontend assets
- ✅ Create storage link
- ✅ Start the server

---

## STEP 7: Run Database Migrations

Once deployment is successful:

### 7.1 Open Shell

1. In your web service, go to **"Shell"** tab (left sidebar)
2. Click **"Connect"** button
3. A terminal window will open

### 7.2 Run Migrations

Type these commands one by one:

**1. Create sessions table:**
```bash
php artisan session:table
```

**2. Run all migrations:**
```bash
php artisan migrate --force
```

**3. Seed the database (optional - adds sample data):**
```bash
php artisan db:seed --force
```

**4. Create storage link:**
```bash
php artisan storage:link
```

**5. Create QR codes directory:**
```bash
mkdir -p public/qrcodes
```

**6. Set permissions:**
```bash
chmod -R 775 storage bootstrap/cache public/qrcodes
```

**7. Clear and cache config:**
```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7.3 Verify

Type this to check if migrations ran:
```bash
php artisan migrate:status
```

You should see all migrations listed as "Ran".

---

## STEP 8: Test Your Application

### 8.1 Visit Your App

1. Go to your web service **"Info"** tab
2. Click on the URL (e.g., `https://lgu-document-tracking.onrender.com`)

**⚠️ First Load**: If the app was sleeping, it might take 30-60 seconds to wake up. Just wait and refresh if needed!

### 8.2 Login

Use these default credentials:
- **Email**: `admin@lgu.gov`
- **Password**: `password`

### 8.3 Test Features

Test these features to make sure everything works:
- ✅ Login/Logout
- ✅ View dashboard
- ✅ Create a document
- ✅ Generate QR code
- ✅ View documents list
- ✅ Update document status

---

## 🎉 CONGRATULATIONS!

Your Laravel app is now deployed to Render.com on the FREE tier!

---

## 🔧 TROUBLESHOOTING

### Issue: "500 Internal Server Error"

**Solution:**
1. Go to **"Logs"** tab in your web service
2. Look for error messages
3. Common issues:
   - Missing APP_KEY → Add it in Environment variables
   - Database connection failed → Check DB credentials
   - Missing migrations → Run migrations in Shell

### Issue: "Database Connection Failed"

**Solution:**
1. Check database service is running (Status should be "Available")
2. Verify all DB environment variables are correct
3. Make sure you're using `pgsql` not `mysql` for DB_CONNECTION

### Issue: "App Takes Long Time to Load"

**Solution:**
- This is normal on free tier! The app sleeps after 15 minutes of inactivity
- First load after sleep takes 30-60 seconds
- Subsequent loads are faster (until it sleeps again)

### Issue: "QR Codes Not Working"

**Solution:**
1. Check if `public/qrcodes` directory exists:
   ```bash
   ls -la public/qrcodes
   ```
2. If missing, create it:
   ```bash
   mkdir -p public/qrcodes
   chmod -R 775 public/qrcodes
   ```
3. Note: On free tier, QR codes may disappear after restart (ephemeral storage)

### Issue: "Sessions Not Working"

**Solution:**
1. Make sure `SESSION_DRIVER=database` in environment variables
2. Run migrations again:
   ```bash
   php artisan session:table
   php artisan migrate --force
   ```

### Issue: "Build Fails"

**Solution:**
1. Check build logs for specific error
2. Common issues:
   - Wrong PHP version → Render auto-detects, but check logs
   - NPM build fails → Check package.json
   - Composer fails → Check composer.json
3. Try clearing build cache and redeploying

---

## 📝 POST-DEPLOYMENT CHECKLIST

- [ ] All environment variables are set
- [ ] Database migrations ran successfully
- [ ] Can access the app URL
- [ ] Can login with default credentials
- [ ] Can create a document
- [ ] QR code generation works
- [ ] Dashboard loads correctly

---

## 🔄 MAKING CHANGES

### To update your app:

1. Make changes locally
2. Commit and push to Git:
   ```bash
   git add .
   git commit -m "Your change description"
   git push
   ```
3. Render will automatically redeploy (if auto-deploy is enabled)
4. Or manually trigger deployment in Render dashboard

### To update environment variables:

1. Go to your web service → "Environment" tab
2. Edit the variable
3. Click "Save Changes"
4. Trigger a new deployment

---

## 📞 NEED MORE HELP?

If you get stuck:

1. **Check Render Logs**: Web Service → "Logs" tab
2. **Check Build Logs**: Web Service → "Events" tab → Click on latest build
3. **Check Database**: Database Service → "Info" tab → Check connection
4. **Render Docs**: https://render.com/docs

---

## ✅ SUCCESS!

Your LGU Document Tracking System is now live on Render.com! 🎉

**Your App URL**: `https://your-app-name.onrender.com`

Remember:
- ⏱️ Free tier sleeps after 15 min (just refresh to wake it up)
- 💾 Files are ephemeral (QR codes may disappear on restart)
- 🗄️ Database lasts 90 days on free tier

**Enjoy testing your deployed application!** 🚀

