# Deploying LGU Document Tracking System to Render.com

## ✅ Is Render.com Suitable for Your Laravel Application?

**YES**, Render.com is an excellent choice for deploying your Laravel application! Here's why:

### ✅ Advantages
- ✅ Native Laravel support with automatic PHP detection
- ✅ MySQL database hosting available
- ✅ Free tier available for testing
- ✅ Easy SSL certificates (automatic HTTPS)
- ✅ Simple environment variable management
- ✅ Git-based continuous deployment
- ✅ Automatic builds from your repository
- ✅ Persistent disk storage available (for QR codes and file uploads)

### ⚠️ Important Considerations

1. **File Storage**: QR codes are stored in `public/qrcodes/`
   - **Solution**: Use Render's persistent disk storage or configure S3
   - **Recommendation**: Use Render's persistent disk (included in paid plans) or migrate to S3 for better scalability

2. **Sessions**: Currently using file-based sessions
   - **Solution**: Switch to database sessions for multi-instance deployments
   - **Configuration**: Change `SESSION_DRIVER=database` in production

3. **Ephemeral Storage**: Render's free tier has ephemeral file system
   - **Issue**: Files can be lost on restart
   - **Solution**: Use persistent disk or external storage (S3)

4. **Database**: MySQL is available on Render
   - **Free tier**: 90 days retention limit
   - **Paid tiers**: Full persistence

5. **Storage Link**: Needs to be created after deployment
   - **Solution**: Add to build script

---

## 🚀 Deployment Steps

### Prerequisites
1. Render.com account (sign up at https://render.com)
2. Git repository (GitHub, GitLab, or Bitbucket)
3. Your Laravel application pushed to the repository

---

## 📋 Step 1: Prepare Your Application

### A. Create `render.yaml` Configuration File

Create a file named `render.yaml` in your project root:

```yaml
services:
  # Laravel Web Service
  - type: web
    name: lgu-document-tracking
    env: php
    plan: starter # or free
    buildCommand: |
      composer install --optimize-autoloader --no-dev
      npm ci
      npm run build
      php artisan storage:link || true
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
    startCommand: php artisan serve --host 0.0.0.0 --port $PORT
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: LOG_LEVEL
        value: error
      - key: SESSION_DRIVER
        value: database # Important for multi-instance!
      - key: CACHE_DRIVER
        value: file
    disk:
      name: storage-disk
      mountPath: /opt/render/project/src/storage
      sizeGB: 10

  # MySQL Database
  - type: pgsql # Render uses PostgreSQL by default
    name: lgu-document-db
    plan: starter # or free
    databaseName: lgu_document_tracking
    user: lgu_admin
```

**Note**: Render offers PostgreSQL by default. If you need MySQL specifically, you can:
- Use PostgreSQL (recommended - just update your database config)
- Or use an external MySQL service (like PlanetScale, AWS RDS, etc.)

### B. Alternative: Use PostgreSQL (Recommended)

If you're open to using PostgreSQL (highly recommended for Render), update your database configuration:

1. Update `composer.json` to include PostgreSQL driver (already included in Laravel)
2. Update `.env` for production:

```env
DB_CONNECTION=pgsql
DB_HOST=<render-postgres-host>
DB_PORT=5432
DB_DATABASE=<database-name>
DB_USERNAME=<username>
DB_PASSWORD=<password>
```

### C. Database Migrations Script

Create a file `render-migrate.sh`:

```bash
#!/bin/bash
php artisan migrate --force
php artisan db:seed --force
```

---

## 📋 Step 2: Configure Environment Variables

In Render Dashboard, add these environment variables:

### Required Environment Variables:

```env
APP_NAME="LGU Document Tracking System"
APP_ENV=production
APP_KEY=<generate-using-php-artisan-key:generate>
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=<from-render-postgres-service>
DB_PORT=5432
DB_DATABASE=<database-name>
DB_USERNAME=<username>
DB_PASSWORD=<password>

# Sessions (IMPORTANT for Render)
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_DRIVER=file

# Mail (if you have email notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### How to Generate APP_KEY:

1. Locally, run: `php artisan key:generate --show`
2. Copy the key and paste it in Render's environment variables

---

## 📋 Step 3: Database Session Migration

Since you're switching to database sessions, create a migration:

```bash
php artisan session:table
php artisan migrate
```

This creates a `sessions` table in your database.

---

## 📋 Step 4: Deploy on Render

### Option A: Using Render Dashboard

1. **Go to Render Dashboard** → New → Web Service
2. **Connect your Git repository**
3. **Configure the service:**
   - **Name**: `lgu-document-tracking`
   - **Environment**: `PHP`
   - **Root Directory**: (leave empty if root is your app root)
   - **Build Command**: 
     ```bash
     composer install --optimize-autoloader --no-dev && npm ci && npm run build && php artisan storage:link || true && php artisan config:cache && php artisan route:cache && php artisan view:cache
     ```
   - **Start Command**: 
     ```bash
     php artisan serve --host 0.0.0.0 --port $PORT
     ```
   - **Plan**: Choose Free (for testing) or Starter/Standard (for production)

4. **Add Environment Variables** (from Step 2)

5. **Create PostgreSQL Database:**
   - Dashboard → New → PostgreSQL
   - Name it: `lgu-document-db`
   - Note the connection details

6. **Run Migrations:**
   - In your web service, go to "Shell" tab
   - Run: `php artisan migrate --force`
   - Run: `php artisan db:seed --force` (optional)

7. **Set up Persistent Disk** (for QR codes):
   - In web service settings → Advanced
   - Add persistent disk:
     - **Name**: `storage-disk`
     - **Mount Path**: `/opt/render/project/src/storage`
     - **Size**: 10 GB (or as needed)

### Option B: Using `render.yaml` (Infrastructure as Code)

1. Push `render.yaml` to your repository
2. In Render Dashboard → New → Blueprint
3. Connect your repository
4. Render will automatically detect and deploy your services

---

## 🔧 Step 5: Post-Deployment Configuration

### A. Create Storage Link

After first deployment, run in Render Shell:

```bash
php artisan storage:link
```

### B. Create Sessions Table (if using database sessions)

```bash
php artisan migrate
```

### C. Set File Permissions

Render handles this automatically, but verify:

```bash
chmod -R 775 storage bootstrap/cache
```

### D. Create QR Codes Directory

```bash
mkdir -p public/qrcodes
chmod -R 775 public/qrcodes
```

---

## 🔐 Step 6: Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Secure database credentials
- [ ] HTTPS enabled (automatic on Render)
- [ ] Session driver set to `database`
- [ ] Change default admin password
- [ ] Enable `.env` file protection (never commit to Git)

---

## 📁 File Storage Solutions

### Option 1: Persistent Disk (Render Paid Plans)

✅ Best for: Small to medium deployments
✅ Pros: Simple, integrated
❌ Cons: Only on paid plans, not scalable across regions

### Option 2: AWS S3 (Recommended for Production)

✅ Best for: Production, scalable deployments
✅ Pros: Scalable, reliable, CDN-ready
❌ Cons: Requires AWS account, additional setup

**To use S3:**

1. Install AWS SDK (already available in Laravel):
   ```bash
   composer require league/flysystem-aws-s3-v3 "^3.0"
   ```

2. Update `.env`:
   ```env
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your-key
   AWS_SECRET_ACCESS_KEY=your-secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your-bucket-name
   AWS_USE_PATH_STYLE_ENDPOINT=false
   ```

3. Update QR code storage to use S3 in your code

### Option 3: Database Storage (for small files only)

Store QR codes as base64 in database - not recommended for your use case.

---

## 🐛 Troubleshooting

### Issue: QR Codes Not Persisting

**Solution**: Use persistent disk or S3 storage

### Issue: Sessions Not Working Across Requests

**Solution**: 
1. Set `SESSION_DRIVER=database`
2. Run `php artisan session:table` and migrate

### Issue: Database Connection Failed

**Solution**: 
1. Check database credentials in environment variables
2. Ensure database service is running
3. Check firewall rules (Render handles this automatically)

### Issue: Build Fails

**Solution**: 
1. Check build logs in Render dashboard
2. Ensure `composer.json` and `package.json` are correct
3. Check PHP version (ensure 8.1+)

### Issue: 500 Error on First Load

**Solution**:
1. Check application logs in Render dashboard
2. Run `php artisan config:clear` in shell
3. Verify all environment variables are set

---

## 💰 Render Pricing Considerations

### Free Tier Limitations:
- ⏱️ Services spin down after 15 minutes of inactivity
- 💾 Ephemeral storage (files lost on restart)
- 🗄️ Database: 90-day retention
- ⚡ Shared resources

### Starter Plan ($7/month per service):
- ✅ Always-on service
- ✅ Persistent disk included
- ✅ Better performance
- ✅ 90-day database retention

### Standard Plan ($25/month per service):
- ✅ Best for production
- ✅ Better CPU/RAM
- ✅ Full database persistence
- ✅ Better for handling traffic

---

## ✅ Recommended Production Setup

For a production LGU system, I recommend:

1. **Web Service**: Standard Plan ($25/month)
   - Persistent disk for QR codes
   - Better performance for multiple users

2. **Database**: Starter Plan ($7/month) or Standard ($20/month)
   - PostgreSQL (better for Render)
   - Regular backups

3. **Storage**: 
   - Option A: Use Render's persistent disk
   - Option B: Use AWS S3 (more scalable, ~$1-5/month)

**Total Cost**: ~$32-50/month for production deployment

---

## 🚀 Quick Start Commands

### Deploy Checklist:

```bash
# 1. Update .env.example with production defaults
# 2. Commit and push to Git
git add .
git commit -m "Configure for Render deployment"
git push

# 3. In Render Dashboard:
#    - Create PostgreSQL database
#    - Create Web Service
#    - Add environment variables
#    - Deploy!

# 4. After deployment, in Render Shell:
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📚 Additional Resources

- [Render PHP Documentation](https://render.com/docs/deploy-php)
- [Render Laravel Guide](https://render.com/docs/deploy-laravel)
- [Laravel Deployment Guide](https://laravel.com/docs/10.x/deployment)

---

## ✅ Final Answer

**YES, Render.com is absolutely fine for deploying your LGU Document Tracking System!**

Just remember to:
1. ✅ Configure database sessions instead of file sessions
2. ✅ Set up persistent disk or S3 for QR code storage
3. ✅ Use PostgreSQL (or external MySQL) for the database
4. ✅ Set all environment variables correctly
5. ✅ Use at least Starter plan for production (free tier spins down)

Good luck with your deployment! 🚀

