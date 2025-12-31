# Facility Management System - Fresh Installation Guide

## Overview

This is a fresh installation of the Facility Management System with a clean database containing no dummy data. The system has been configured for a new customer deployment.

## Installation Status

✅ **Installation Complete**

- Docker container: `fm_app_legal_os`
- Status: Running
- Port: 80 (HTTP)
- Database: SQLite (clean, no demo data)
- Application automatically redirects from root to login page

## System Access

### Application URL
- **Local Access**: http://localhost
- **Network Access**: http://[your-server-ip]

### Default Login Credentials

```
Username: admin
Password: admin123
```

⚠️ **IMPORTANT SECURITY NOTICE**:
Please change the admin password immediately after your first login!

## Database Status

The database has been initialized with the following structure:

| Table | Records |
|-------|---------|
| Facilities | 0 |
| Users | 1 (admin only) |
| GBU Assessments | 0 |
| Providers | 0 |
| Maintenance Logs | 0 |
| Daily Tasks | 0 |
| Documents | 0 |

**Note**: Only essential legal reference data (5 laws, 3 obligations) has been pre-loaded to support compliance features. All operational data tables are empty and ready for customer use.

## Docker Management

### View Application Status
```bash
docker compose ps
```

### View Application Logs
```bash
docker compose logs -f
```

### Stop the Application
```bash
docker compose down
```

### Start the Application
```bash
docker compose up -d
```

### Restart the Application
```bash
docker compose restart
```

### Complete Reset (⚠️ Destroys all data)
```bash
docker compose down -v
docker compose up -d
```

## Data Persistence

The following data is persisted in Docker volumes:

- **Database**: Stored in volume `rynix-fm_fm_data`
- **Uploads**: Stored in volume `rynix-fm_fm_uploads`

These volumes will persist even if you stop or recreate the container (unless you use `docker compose down -v`).

## Backup Recommendations

### Backup Database
```bash
docker compose exec app cp /var/www/html/data/database.sqlite /var/www/html/data/backup_$(date +%Y%m%d).sqlite
docker compose cp app:/var/www/html/data/backup_$(date +%Y%m%d).sqlite ./
```

### Backup Uploads
```bash
docker compose exec app tar -czf /tmp/uploads_backup_$(date +%Y%m%d).tar.gz /var/www/html/uploads
docker compose cp app:/tmp/uploads_backup_$(date +%Y%m%d).tar.gz ./
```

## System Requirements

- Docker Engine 20.10+
- Docker Compose V2
- Minimum 512MB RAM
- Minimum 1GB disk space

## Application Features

This installation includes the following modules:

1. **Facility Management** - Manage buildings, floors, and rooms
2. **Maintenance Management** - Track maintenance tasks and schedules
3. **Provider Management** - Manage service providers and contractors
4. **Key Management** - Track keys, locks, and access control
5. **Document Management** - Store and organize facility documents
6. **Energy Management** - Monitor energy consumption (meters and readings)
7. **Legal Compliance** - Track legal requirements and obligations
8. **GBU (Risk Assessment)** - Manage workplace risk assessments
9. **Training Management** - Track employee training requirements
10. **Calendar & Events** - Schedule and track facility events
11. **Task Management** - Daily task tracking and assignment

## Port Configuration

The application is currently configured to run on port 80. To change the port:

1. Edit `docker-compose.yml`
2. Change the port mapping from `"80:80"` to `"[your-port]:80"`
3. Restart the container: `docker compose up -d`

## Troubleshooting

### Application Not Accessible
```bash
# Check if container is running
docker compose ps

# Check logs for errors
docker compose logs --tail=100

# Restart the application
docker compose restart
```

### Database Issues
```bash
# Check database exists
docker compose exec app ls -lh /var/www/html/data/

# Verify database integrity
docker compose exec app php -r "
\$pdo = new PDO('sqlite:/var/www/html/data/database.sqlite');
echo 'Database connection successful\n';
"
```

### Permission Issues
```bash
# Fix permissions inside container
docker compose exec app chown -R www-data:www-data /var/www/html/data
docker compose exec app chown -R www-data:www-data /var/www/html/uploads
```

## Next Steps

1. **Login**: Access the application at http://localhost
2. **Change Password**: Go to User Management and change the admin password
3. **Add Facilities**: Start by adding your first facility
4. **Configure Users**: Create user accounts for your team
5. **Import Documents**: Upload relevant compliance documents
6. **Setup Providers**: Add your service providers and contractors

## Support

For technical support or questions about this installation, please contact your system administrator.

---

**Installation Date**: 2025-12-31
**Version**: Fresh Install (Clean Database)
**Container Name**: fm_app_legal_os
