# Deployment Instructions (Docker)

This guide explains how to deploy the Legal Operating System to a VPS using Docker. The setup ensures that all legal data (database and documents) is preserved and transferred correctly.

## Prerequisites
- A VPS (Virtual Private Server) with Docker and Docker Compose installed.
- Access to copy files to the VPS (e.g., via SCP, SFTP, or Git).

## Steps

### 1. Transfer Files
Copy the entire `FM-app` folder to your VPS.
Example using SCP:
```bash
scp -r path/to/FM-app user@your-vps-ip:/opt/fm-app
```

### 2. Build and Run
SSH into your VPS and navigate to the folder:
```bash
cd /opt/fm-app
docker-compose up -d --build
```

### 3. Verification
- Open your browser and visit `http://your-vps-ip`.
- The application should be running with all legal data intact.

## How it Works
- **Database:** The current `database.sqlite` is used as a "seed". On the first run, it is copied to a persistent volume (`fm_data`).
- **Uploads:** The contents of the `uploads/` folder are also used as a seed and copied to a persistent volume (`fm_uploads`).
- **Persistence:** Any new tasks, facility updates, or document uploads made on the VPS will be saved in the Docker volumes and will survive container restarts.

## Troubleshooting
- If you need to reset the database to the original state:
  ```bash
  docker-compose down -v
  docker-compose up -d
  ```
  *(Warning: This deletes all new data created on the VPS!)*
