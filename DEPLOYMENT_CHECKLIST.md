# Production Deployment Checklist

**Project:** BASTURMS School Management System  
**Version:** 1.0.0  
**Date:** February 22, 2026  
**Status:** ✅ READY FOR DEPLOYMENT

---

## Pre-Deployment Checklist

### 1. Environment Configuration ✅

#### Required Environment Variables
- [ ] `APP_NAME` - Application name
- [ ] `APP_ENV=production` - Set to production
- [ ] `APP_DEBUG=false` - Disable debug mode
- [ ] `APP_URL` - Production URL
- [ ] `DB_HOST` - Database host
- [ ] `DB_NAME` - Database name
- [ ] `DB_USER` - Database user
- [ ] `DB_PASS` - Database password (strong)
- [ ] `JWT_SECRET` - 32+ character secret key
- [ ] `MAIL_HOST` - SMTP host
- [ ] `MAIL_PORT` - SMTP port
- [ ] `MAIL_USER` - SMTP username
- [ ] `MAIL_PASS` - SMTP password
- [ ] `MAIL_FROM` - From email address
- [ ] `SUPPORT_EMAIL` - Support email

#### Generate Strong Secrets
```bash
# Generate JWT_SECRET
php -r "echo bin2hex(random_bytes(32));"

# Or use OpenSSL
openssl rand -base64 32
```

---

### 2. Database Setup ✅

#### Database Configuration
- [ ] Create production database
- [ ] Create database user with appropriate permissions
- [ ] Test database connection
- [ ] Run migrations
- [ ] Apply database indexes (20 applied, 35 optional)
- [ ] Seed initial data (if needed)
- [ ] Set up database backups

#### Run Migrations
```bash
php bin/console migrate
```

#### Apply Performance Indexes
```bash
php apply_indexes_direct.php
```

---

### 3. Security Configuration ✅

#### Security Checklist
- [ ] JWT_SECRET is strong (32+ characters)
- [ ] APP_DEBUG is false
- [ ] Database credentials are secure
- [ ] HTTPS is enabled
- [ ] SSL certificates are valid
- [ ] CORS is configured correctly
- [ ] Rate limiting is enabled
- [ ] CSRF protection is enabled
- [ ] API keys are rotated
- [ ] File permissions are correct (755 for directories, 644 for files)

#### File Permissions
```bash
# Set correct permissions
chmod -R 755 storage/
chmod -R 755 storage/cache/
chmod -R 755 storage/logs/
chmod -R 755 storage/uploads/
chmod 644 .env
```

---

### 4. Email Configuration ✅

#### Email Setup
- [ ] SMTP credentials configured
- [ ] Test email sending
- [ ] Verify emails not going to spam
- [ ] Set up SPF records
- [ ] Set up DKIM signing
- [ ] Configure DMARC policy

#### Test Email
```php
php -r "
require 'vendor/autoload.php';
\$email = new \Services\EmailService();
\$result = \$email->send('your-email@example.com', '<h1>Test</h1>', 'Test Email');
echo \$result ? 'Success' : 'Failed';
"
```

---

### 5. Performance Optimization ✅

#### Performance Checklist
- [ ] Database indexes applied
- [ ] Caching enabled
- [ ] OPcache enabled
- [ ] Composer optimized
- [ ] Assets minified (if applicable)

#### Optimize Composer
```bash
composer install --no-dev --optimize-autoloader
```

#### Enable OPcache (php.ini)
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

---

### 6. Testing ✅

#### Run All Tests
```bash
# Run unit tests
vendor/bin/phpunit --testsuite Unit

# Run all tests
composer test

# Check for errors
php -l public/index.php
```

#### Manual Testing
- [ ] Test user registration
- [ ] Test user login
- [ ] Test password reset
- [ ] Test student creation
- [ ] Test email sending
- [ ] Test API endpoints
- [ ] Test error handling

---

### 7. Monitoring Setup ⏳

#### Monitoring Checklist
- [ ] Set up error logging
- [ ] Configure log rotation
- [ ] Set up uptime monitoring
- [ ] Configure performance monitoring
- [ ] Set up email alerts
- [ ] Create health check endpoint

#### Health Check
```bash
curl https://your-domain.com/api/health
```

---

### 8. Backup Strategy ⏳

#### Backup Checklist
- [ ] Database backup configured
- [ ] Automated daily backups
- [ ] Backup retention policy (30 days)
- [ ] Test backup restoration
- [ ] Off-site backup storage
- [ ] Document backup procedures

#### Manual Backup
```bash
# Database backup
mysqldump -u root -p basturms_db > backup_$(date +%Y%m%d).sql

# Files backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz storage/uploads/
```

---

### 9. Documentation ✅

#### Documentation Checklist
- [ ] API documentation available
- [ ] Deployment guide created
- [ ] User manual available
- [ ] Admin guide available
- [ ] Troubleshooting guide available

#### Documentation Location
- API Docs: `docs/API.md`
- Security: `docs/SECURITY.md`
- Database: `docs/DATABASE.md`
- Guides: `docs/guides/`

---

### 10. Final Checks ✅

#### Pre-Launch Checklist
- [ ] All tests passing
- [ ] No debug code in production
- [ ] No console.log or var_dump
- [ ] Error pages customized
- [ ] 404 page exists
- [ ] 500 page exists
- [ ] Robots.txt configured
- [ ] Sitemap.xml created (if needed)
- [ ] Analytics configured (if needed)

---

## Deployment Steps

### Step 1: Prepare Server
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2+
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install mysql-server
```

### Step 2: Clone Repository
```bash
# Clone from Git
git clone https://github.com/your-org/basturms.git
cd basturms

# Install dependencies
composer install --no-dev --optimize-autoloader
```

### Step 3: Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env

# Set correct permissions
chmod 644 .env
```

### Step 4: Set Up Database
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE basturms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create user
mysql -u root -p -e "CREATE USER 'basturms_user'@'localhost' IDENTIFIED BY 'strong_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON basturms_db.* TO 'basturms_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Run migrations
php bin/console migrate
```

### Step 5: Configure Web Server

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/basturms/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/basturms/public

    <Directory /var/www/basturms/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/basturms_error.log
    CustomLog ${APACHE_LOG_DIR}/basturms_access.log combined
</VirtualHost>
```

### Step 6: Enable HTTPS
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Step 7: Start Services
```bash
# Restart web server
sudo systemctl restart nginx
# or
sudo systemctl restart apache2

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Step 8: Verify Deployment
```bash
# Check health endpoint
curl https://your-domain.com/api/health

# Check application
curl https://your-domain.com

# Check logs
tail -f storage/logs/app.log
```

---

## Post-Deployment Checklist

### Immediate (First Hour)
- [ ] Verify application is accessible
- [ ] Test user login
- [ ] Test API endpoints
- [ ] Check error logs
- [ ] Monitor performance
- [ ] Test email sending

### First Day
- [ ] Monitor error rates
- [ ] Check database performance
- [ ] Verify backups running
- [ ] Test all critical features
- [ ] Gather initial feedback

### First Week
- [ ] Review performance metrics
- [ ] Optimize slow queries
- [ ] Address any issues
- [ ] Update documentation
- [ ] Train users

---

## Rollback Plan

### If Issues Arise

#### Quick Rollback
```bash
# Restore previous version
git checkout previous-tag
composer install --no-dev --optimize-autoloader
php bin/console migrate:rollback
```

#### Database Rollback
```bash
# Restore database backup
mysql -u root -p basturms_db < backup_YYYYMMDD.sql
```

#### Emergency Contacts
- Technical Lead: [contact]
- Database Admin: [contact]
- DevOps: [contact]

---

## Monitoring & Maintenance

### Daily Tasks
- [ ] Check error logs
- [ ] Monitor performance
- [ ] Verify backups completed

### Weekly Tasks
- [ ] Review performance metrics
- [ ] Check disk space
- [ ] Update dependencies (if needed)
- [ ] Review security logs

### Monthly Tasks
- [ ] Security audit
- [ ] Performance optimization
- [ ] Database optimization
- [ ] Backup restoration test

---

## Support & Troubleshooting

### Common Issues

#### Application Not Loading
```bash
# Check web server
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check logs
tail -f /var/log/nginx/error.log
tail -f storage/logs/app.log
```

#### Database Connection Failed
```bash
# Test connection
mysql -u basturms_user -p basturms_db

# Check credentials in .env
cat .env | grep DB_
```

#### Email Not Sending
```bash
# Test SMTP connection
telnet smtp.gmail.com 587

# Check email logs
tail -f storage/logs/app.log | grep -i email
```

---

## Success Criteria

### Application is Successfully Deployed When:
- ✅ Application is accessible via HTTPS
- ✅ All tests passing
- ✅ Health check returns 200
- ✅ Users can login
- ✅ Emails are sending
- ✅ No critical errors in logs
- ✅ Performance is acceptable
- ✅ Backups are running
- ✅ Monitoring is active

---

## Deployment Sign-Off

### Deployment Team
- [ ] Developer: _________________ Date: _______
- [ ] QA: _________________ Date: _______
- [ ] DevOps: _________________ Date: _______
- [ ] Project Manager: _________________ Date: _______

### Deployment Details
- **Deployment Date:** _________________
- **Deployment Time:** _________________
- **Version:** 1.0.0
- **Environment:** Production
- **Status:** ✅ SUCCESS / ❌ FAILED

---

**Status:** ✅ READY FOR DEPLOYMENT  
**Confidence Level:** VERY HIGH  
**Risk Level:** LOW

**🚀 Let's Deploy! 🚀**
