# 🏥 MediAI - AI-Powered Healthcare Management System

<div align="center">

![MediAI Logo](https://img.shields.io/badge/MediAI-Healthcare%20AI-blue?style=for-the-badge&logo=health)
![Version](https://img.shields.io/badge/version-2.0.0-green?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)
![Python](https://img.shields.io/badge/Python-3.8+-3776AB?style=for-the-badge&logo=python)

**An intelligent healthcare platform that combines AI-powered disease detection, risk prediction, and comprehensive hospital management**

[🚀 Features](#-features) • [⚙️ Installation](#️-installation) • [📖 Documentation](#-documentation) • [🔧 Configuration](#-configuration) • [🛡️ Security](#️-security)

</div>

---

## 📋 Table of Contents

1. [🌟 About](#-about)
2. [🚀 Features](#-features)
3. [🏗️ Architecture](#️-architecture)
4. [⚙️ Installation](#️-installation)
5. [🔧 Configuration](#-configuration)
6. [📖 API Documentation](#-api-documentation)
7. [🛡️ Security](#️-security)
8. [🚀 Deployment](#-deployment)
9. [🤝 Contributing](#-contributing)
10. [📄 License](#-license)

---

## 🌟 About

MediAI is a comprehensive healthcare management system that leverages artificial intelligence to provide intelligent medical services. The platform integrates multiple AI models for disease detection and risk prediction with a full-featured hospital management system, creating a seamless experience for patients, doctors, and healthcare administrators.

### 🎯 Mission

To democratize healthcare by making AI-powered medical diagnostics and hospital management accessible, efficient, and secure for healthcare providers worldwide.

---

## 🚀 Features

### 🤖 AI-Powered Medical Services

| Feature | Description | Technology |
|---------|-------------|------------|
| **Disease Detection** | Image-based detection for breast cancer and brain tumors | TensorFlow/Keras CNN Models |
| **Risk Prediction** | Cardiovascular disease risk assessment using Framingham model | Scikit-learn ML Pipeline |
| **AI Chat Assistant** | Intelligent medical consultation and symptom analysis | Natural Language Processing |
| **Report Analysis** | Automated analysis of medical reports and lab results | OCR + NLP |

### 🏥 Hospital Management

#### 👥 User Management
- **Multi-role System**: Patients, Doctors, Hospital Staff, Administrators
- **Secure Authentication**: Session-based authentication with timeout management
- **Profile Management**: Comprehensive profiles with medical history and credentials

#### 📅 Appointment System
- **Smart Scheduling**: Available time slots management
- **Video Consultations**: Integrated telemedicine capabilities
- **Appointment Tracking**: Real-time status updates and notifications
- **Multi-hospital Support**: Doctors can serve multiple hospitals

#### 💰 Billing & Payments
- **Comprehensive Billing**: Services, medicines, tests, room charges
- **Insurance Integration**: Support for insurance claims
- **Discount Management**: Flexible discount and tax calculation
- **Payment Tracking**: Complete payment history and balance management

#### 🏨 Cabin Management
- **Room Types**: General, Deluxe, ICU cabins
- **Real-time Availability**: Live booking status
- **Booking Management**: Check-in/check-out tracking
- **Pricing System**: Dynamic pricing based on cabin type

#### 💊 Pharmacy & Inventory
- **Medicine Management**: Complete pharmacy inventory
- **Prescription System**: Digital prescription management
- **Stock Tracking**: Real-time inventory monitoring
- **Supplier Management**: Vendor and supply chain tracking

### 🌐 Community & Support

#### 👥 Health Communities
- **Support Groups**: Disease-specific communities (Diabetes, Mental Health, etc.)
- **Discussion Forums**: Peer support and experience sharing
- **Expert Moderation**: Healthcare professional moderation
- **Privacy Controls**: Anonymous participation options

#### 💬 Communication
- **Real-time Chat**: Secure messaging between patients and doctors
- **Group Discussions**: Community-based health discussions
- **File Sharing**: Secure medical document sharing
- **Notification System**: Email and in-app notifications

### 🔔 Smart Reminders
- **Medication Reminders**: Automated medication adherence alerts
- **Appointment Reminders**: Smart appointment notifications
- **Health Tracking**: Personalized health milestone reminders
- **Email Integration**: Automated email notifications

---

## 🏗️ Architecture

### 📊 System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   AI Services   │
│                 │    │                 │    │                 │
│ • PHP Pages     │◄──►│ • PHP Core      │◄──►│ • Python Flask  │
│ • HTML/CSS/JS   │    │ • MySQL DB      │    │ • TensorFlow    │
│ • Bootstrap     │    │ • Session Mgmt  │    │ • Scikit-learn  │
│ • React Components│   │ • Encryption    │    │ • NLP Models    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 🗄️ Database Schema

The system uses a comprehensive MySQL database with 25+ tables including:

- **User Management**: `users`, `patients`, `doctors`, `admins`, `roles`
- **Medical Data**: `appointments`, `medical_records`, `prescriptions`
- **AI Features**: `ai_conversations`, `chatbot_queries`, `disease_detections`
- **Hospital Operations**: `cabins`, `bills`, `inventory`, `community`
- **Security**: `encrypted_data`, `session_logs`

### 🔐 Security Architecture

- **AES-256-CBC Encryption**: For sensitive medical data at rest
- **Session Management**: Secure session handling with automatic timeout
- **Role-Based Access Control**: Granular permissions by user role
- **Input Validation**: Comprehensive sanitization and validation
- **SQL Injection Protection**: Prepared statements and parameterized queries

---

## ⚙️ Installation

### 📋 Prerequisites

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Python**: Version 3.8+ (for AI services)
- **Composer**: Latest version
- **Node.js**: Version 16+ (for frontend build tools)

### 🚀 Quick Start

#### 1. Clone the Repository
```bash
git clone https://github.com/mubasshirarnab/MediAI.git
cd MediAI
```

#### 2. Install PHP Dependencies
```bash
composer install
```

#### 3. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE mediai;

# Import database schema
mysql -u root -p mediai < mediai.sql
```

#### 4. Configure Database Connection
Edit `dbConnect.php`:
```php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "mediai";
```

#### 5. Setup AI Services
```bash
# Setup Breast Cancer Detection
cd "Breast Cancer"
pip install -r requirements.txt
python app.py

# Setup Risk Prediction Model
cd "risk_predict_model"
pip install -r requirements.txt
python app.py
```

#### 6. Configure Encryption
```bash
# Run encryption setup
php setup_encryption.php
```

#### 7. Set File Permissions
```bash
chmod 755 -R .
chmod 777 images/ postImages/ groupsImages/
```

#### 8. Configure Web Server

**Apache Virtual Host Example:**
```apache
<VirtualHost *:80>
    ServerName mediai.local
    DocumentRoot /path/to/MediAI
    DirectoryIndex index.php
    
    <Directory /path/to/MediAI>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 🔧 Configuration

### 📁 Configuration Files

| File | Purpose |
|------|---------|
| `dbConnect.php` | Database connection settings |
| `email_config.php` | Email SMTP configuration |
| `encryption_config.php` | Encryption key management |
| `session_manager.php` | Session security settings |
| `composer.json` | PHP dependencies |

### 🌐 Environment Variables

Create `.env` file in root directory:
```env
# Database
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_NAME=mediai

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password

# AI Services
AI_BREAST_CANCER_URL=http://localhost:5000
AI_RISK_PREDICTION_URL=http://localhost:5001

# Security
ENCRYPTION_KEY=your_generated_key
SESSION_TIMEOUT=1800
```

### 📧 Email Configuration

Configure `email_config.php` for notifications:
```php
<?php
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your_email@gmail.com',
    'password' => 'your_app_password',
    'from_email' => 'noreply@mediai.com',
    'from_name' => 'MediAI Healthcare'
];
?>
```

---

## 📖 API Documentation

### 🔗 AI Service Endpoints

#### Breast Cancer Detection
```http
POST /Breast Cancer/detect-breast
Content-Type: application/json

{
    "image_path": "/path/to/mammogram.jpg"
}
```

#### Tumor Detection
```http
POST /Breast Cancer/detect-tumor
Content-Type: application/json

{
    "image_path": "/path/to/brain_scan.jpg"
}
```

#### Risk Prediction
```http
POST /risk_predict_model/predict
Content-Type: application/json

{
    "age": 45,
    "sex": "male",
    "current_smoker": "no",
    "cigs_per_day": 0,
    "bp_medications": "yes",
    "prevalent_stroke": "no",
    "prevalent_hyp": "yes",
    "diabetes": "no",
    "cholesterol": 220,
    "systolic_bp": 140,
    "heart_rate": 75,
    "glucose": 95
}
```

### 🏥 Hospital Management APIs

#### Appointment Booking
```http
POST /book_appointment.php
Content-Type: application/x-www-form-urlencoded

doctor_id=15&patient_name=John%20Doe&email=john@email.com&phone=1234567890&timeslot=2024-01-15%2010:00:00&notes=Regular%20checkup
```

#### Disease Detection Upload
```http
POST /detect_disease.php
Content-Type: multipart/form-data

image=@scan.jpg&type=breast_cancer
```

#### AI Chat Interface
```http
POST /ai.php
Content-Type: application/x-www-form-urlencoded

action=send_message&message=I%20have%20chest%20pain&conversation_id=123
```

---

## 🛡️ Security

### 🔐 Data Protection

- **Encryption**: AES-256-CBC encryption for sensitive medical data
- **Secure Sessions**: Automatic session timeout and regeneration
- **Input Validation**: Comprehensive sanitization of all user inputs
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Protection**: Output encoding and Content Security Policy

### 🚦 Access Control

- **Role-Based Authentication**: Different access levels for patients, doctors, staff
- **Session Management**: Secure session handling with timeout protection
- **Password Security**: Hashed passwords using secure algorithms
- **API Rate Limiting**: Protection against brute force attacks

### 📋 Security Checklist

- [ ] Change default database credentials
- [ ] Configure proper file permissions
- [ ] Set up SSL/TLS certificates
- [ ] Configure firewall rules
- [ ] Enable database query logging
- [ ] Regular security updates
- [ ] Backup encryption keys securely
- [ ] Monitor access logs

### 🔑 Encryption Setup

The system includes comprehensive encryption for medical data:

```bash
# Setup encryption (one-time)
php setup_encryption.php

# Test encryption
php test_encryption_examples.php
```

---

## 🚀 Deployment

### 🐳 Docker Deployment

Create `docker-compose.yml`:
```yaml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
    depends_on:
      - db
      - ai-services
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: mediai
    volumes:
      - db_data:/var/lib/mysql
  
  ai-services:
    build: ./ai-services
    ports:
      - "5000:5000"
      - "5001:5001"

volumes:
  db_data:
```

### ☁️ Cloud Deployment

#### AWS EC2 Setup
```bash
# Launch EC2 instance with Ubuntu 20.04
# Install LAMP stack
sudo apt update
sudo apt install apache2 mysql-server php8.0 libapache2-mod-php8.0

# Clone and setup
git clone https://github.com/mubasshirarnab/MediAI.git
sudo cp -r MediAI/* /var/www/html/
```

#### Heroku Deployment
```bash
# Install Heroku CLI
heroku create your-app-name
heroku buildpacks:add heroku/php
heroku buildpacks:add heroku/python
git push heroku main
```

### 📊 Performance Optimization

- **Database Indexing**: Optimized queries with proper indexes
- **Caching**: Redis for session and data caching
- **CDN Integration**: Static assets delivery via CDN
- **Load Balancing**: Multiple server support
- **Image Optimization**: Compressed medical images

---

## 🤝 Contributing

We welcome contributions to improve MediAI! Please follow these guidelines:

### 📝 Development Guidelines

1. **Code Style**: Follow PSR-12 for PHP, PEP 8 for Python
2. **Testing**: Write unit tests for new features
3. **Documentation**: Update documentation for API changes
4. **Security**: Follow security best practices
5. **Branching**: Use feature branches for development

### 🔄 Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### 🐛 Bug Reporting

Use the GitHub Issues page to report bugs. Please include:
- Detailed description of the issue
- Steps to reproduce
- Expected vs actual behavior
- Environment details

---

## 📄 License

This project is licensed under the **Proprietary License** - see the [LICENSE](LICENSE) file for details.

### 📜 License Summary

- ✅ **Personal Use**: Free for personal and educational purposes
- ✅ **Open Source Contribution**: Community contributions welcome
- ❌ **Commercial Use**: Requires commercial license
- ❌ **Redistribution**: Cannot redistribute without permission

---

## 🙏 Acknowledgments

- **Healthcare Professionals**: For medical expertise and validation
- **Open Source Community**: For the amazing tools and libraries
- **Medical Institutions**: For providing datasets and insights
- **Contributors**: Everyone who helped make this project possible

---

## 📞 Support & Contact

### 🆘 Getting Help

- **Documentation**: Check this README and inline code comments
- **Issues**: Report bugs via GitHub Issues
- **Discussions**: Join our GitHub Discussions
- **Email**: support@mediai.com

### 🌐 Connect With Us

- **Website**: [https://mediai.example.com](https://mediai.example.com)
- **Twitter**: [@MediAI_Health](https://twitter.com/MediAI_Health)
- **LinkedIn**: [MediAI Healthcare](https://linkedin.com/company/mediai-healthcare)

---

<div align="center">

**🏥 Made with ❤️ for Healthcare Professionals Worldwide**

[⬆️ Back to Top](#-mediai---ai-powered-healthcare-management-system)

</div>
