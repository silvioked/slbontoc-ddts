# Free Tier Testing Guide for Render.com

## ✅ YES - Render.com is FREE for Testing!

Render.com offers a **free tier** that's perfect for testing your LGU Document Tracking System. Here's everything you need to know:

---

## 🆓 What's FREE on Render

### ✅ Web Service (Laravel App)
- **Cost**: $0/month
- ✅ Deploy your Laravel application
- ✅ Automatic HTTPS/SSL certificates
- ✅ Git-based continuous deployment
- ✅ Automatic builds

### ✅ PostgreSQL Database
- **Cost**: $0/month
- ✅ Up to 1 GB storage
- ✅ Full database functionality
- ✅ Standard PostgreSQL features

### ✅ No Credit Card Required
- You can sign up and deploy without a credit card!

---

## ⚠️ Free Tier Limitations (Important for Testing)

### 1. **Service Sleeps After Inactivity** ⏱️
- **What happens**: Your app spins down after 15 minutes of no traffic
- **Impact**: First request after sleep takes ~30-60 seconds to wake up
- **Good for**: Testing, demos, low-traffic applications
- **Not ideal for**: Production with constant users

**Solution for testing**: Just refresh the page - it will wake up automatically!

### 2. **Ephemeral Storage** 💾
- **What happens**: Files stored on disk are lost when service restarts
- **Impact**: QR codes in `public/qrcodes/` may disappear on restart
- **Good for**: Testing functionality (not data persistence)

**Workarounds for testing**:
- ✅ QR codes will work during your testing session
- ✅ They regenerate when you create new documents
- ✅ For long-term storage, consider:
  - Using database storage (store QR code data in DB)
  - Using free cloud storage (Google Drive API, Dropbox API)
  - AWS S3 free tier (5GB free for 12 months)

### 3. **Database Retention** 🗄️
- **What happens**: Free databases are deleted after 90 days of inactivity
- **Impact**: If you don't use it for 90 days, data is lost
- **Good for**: Short-term testing (you have 90 days!)

**Solution**: Just access your app at least once every 90 days to keep it active.

### 4. **Shared Resources** ⚡
- **What happens**: Your app shares CPU/RAM with other free tier users
- **Impact**: Slightly slower performance during peak times
- **Good for**: Testing with 1-5 users simultaneously

---

## 🚀 Quick Start: Deploy FREE Tier

### Step 1: Sign Up
1. Go to https://render.com
2. Sign up with GitHub/GitLab/Bitbucket (free)
3. **No credit card needed!**

### Step 2: Connect Your Repository
1. Click "New" → "Web Service"
2. Connect your Git repository
3. Select your branch (usually `main` or `master`)

### Step 3: Configure Settings

**Basic Settings:**
- **Name**: `lgu-document-tracking` (or any name)
- **Environment**: `PHP`
- **Region**: Choose closest to you (e.g., `Oregon`)
- **Branch**: `main` (or your default branch)
- **Root Directory**: (leave empty)
- **Plan**: **`Free`** ⭐

**Build Command:**
```bash
composer install --optimize-autoloader --no-dev && npm ci && npm run build && php artisan storage:link || true
```

**Start Command:**
```bash
php artisan serve --host 0.0.0.0 --port $PORT
```

### Step 4: Create Free Database

1. Click "New" → "PostgreSQL"
2. Configure:
   - **Name**: `lgu-document-db`
   - **Database**: `lgu_document_tracking`
   - **User**: `lgu_admin`
   - **Region**: Same as your web service
   - **Plan**: **`Free`** ⭐
3. Copy the connection details

### Step 5: Add Environment Variables

In your web service settings, add these environment variables:

```env
APP_NAME="LGU Document Tracking System"
APP_ENV=production
APP_KEY=<generate-this-locally>
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

# Database (from PostgreSQL service)
DB_CONNECTION=pgsql
DB_HOST=<internal-database-host>
DB_PORT=5432
DB_DATABASE=lgu_document_tracking
DB_USERNAME=lgu_admin
DB_PASSWORD=<from-database-service>

# Sessions (IMPORTANT!)
SESSION_DRIVER=database

# Logging
LOG_LEVEL=error
```

**To generate APP_KEY:**
```bash
# On your local machine
php artisan key:generate --show
# Copy the key and paste in Render environment variables
```

### Step 6: Deploy!

1. Click "Create Web Service"
2. Wait for first deployment (5-10 minutes)
3. Once deployed, go to "Shell" tab
4. Run migrations:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   php artisan session:table
   php artisan migrate --force
   ```

### Step 7: Test Your App

1. Visit your app URL: `https://your-app-name.onrender.com`
2. Wait for wake-up (if sleeping): ~30-60 seconds on first load
3. Login with default credentials:
   - Email: `admin@lgu.gov`
   - Password: `password`

---

## 💡 Free Tier Testing Tips

### ✅ What Works Great on Free Tier:
- ✅ Testing authentication and login
- ✅ Creating and viewing documents
- ✅ Testing user roles and permissions
- ✅ QR code generation (during active session)
- ✅ Document tracking and status updates
- ✅ Notifications system
- ✅ All core functionality

### ⚠️ Things to Watch Out For:
- ⚠️ QR codes may disappear after restart (regenerate when needed)
- ⚠️ First load after sleep takes 30-60 seconds
- ⚠️ Don't rely on file storage for important data

### 💾 Handling QR Codes on Free Tier

**Option 1: Accept Ephemeral Storage** (Simplest for testing)
- QR codes work during your testing session
- They regenerate when creating documents
- Good enough for functionality testing

**Option 2: Store QR Data in Database** (Better persistence)
- Store QR code image as base64 in database
- Or store just the document number and regenerate on-the-fly
- More complex but data persists

**Option 3: Use Free Cloud Storage** (Best for testing)
- Google Drive API (15GB free)
- Dropbox API (2GB free)
- AWS S3 (5GB free for 12 months)

---

## 🔄 Switching from Free to Paid (When Ready)

When you're ready for production:

1. **Web Service**: Upgrade to Starter ($7/month)
   - Always-on (no sleeping)
   - Persistent disk included
   - Better performance

2. **Database**: Upgrade to Starter ($7/month)
   - No 90-day deletion
   - Regular backups
   - Better performance

**Total**: ~$14/month for reliable production

---

## 📊 Free Tier vs Paid Comparison

| Feature | Free Tier | Starter Plan ($7/mo) |
|---------|-----------|----------------------|
| **Cost** | $0 | $7/month |
| **Always On** | ❌ (sleeps after 15 min) | ✅ |
| **Cold Start** | 30-60 sec | Instant |
| **File Storage** | Ephemeral | Persistent disk |
| **Database Retention** | 90 days | Permanent |
| **Performance** | Shared resources | Dedicated |
| **Good For** | Testing, demos | Production |

---

## ✅ Summary: Is Free Tier Good for Testing?

**YES! Absolutely!** 

✅ Perfect for:
- Learning how Render works
- Testing your Laravel app functionality
- Demonstrating to stakeholders
- Development and staging
- Low-budget testing

✅ Limitations are manageable:
- Sleep delay is fine for testing (just refresh!)
- File storage works during active sessions
- 90-day database retention is plenty for testing

**Bottom Line**: Start with the free tier. You can always upgrade later when you need production features!

---

## 🚀 Next Steps

1. ✅ Sign up for Render.com (free, no credit card)
2. ✅ Deploy using the `render.yaml` file (configured for free tier)
3. ✅ Test your application thoroughly
4. ✅ Upgrade when ready for production

**Good luck with your testing!** 🎉

