# MediAI React Admin Panel Setup Guide

## 🚀 Quick Setup Instructions

### 1. Install Dependencies
```bash
npm install
```

### 2. Start Development Server
```bash
npm run dev
```

### 3. Build for Production
```bash
npm run build
```

### 4. Preview Production Build
```bash
npm run serve
```

## 📁 Project Structure

```
MediAI-main/
├── src/
│   ├── components/
│   │   └── AdminPage.jsx      # Main React component
│   ├── styles/
│   │   └── admin.css          # Admin panel styles
│   └── main.jsx               # React entry point
├── index.html                 # HTML template
├── package.json               # Dependencies
├── vite.config.js            # Vite configuration
└── php-files/                # PHP backend files
```

## 🔧 Configuration

### Development Environment
- **React**: Version 18.2.0
- **Vite**: Version 4.4.0 (Build tool)
- **Development Server**: http://localhost:3000

### Production Setup
- Build files go to `dist/` folder
- PHP backend integration with Vite proxy

## 🌐 Access Admin Panel

1. **Start XAMPP** (Apache + MySQL)
2. **Run React Dev Server**: `npm run dev`
3. **Login as Admin** (role_id = 4)
4. **Access**: http://localhost:3000

## 🔐 Authentication Flow

1. Admin logs in via `login.php`
2. Redirected to `index.html` (React app)
3. React app checks authentication via `check_admin_auth.php`
4. Database access through `get_admin_data.php`

## 📱 Features

- ✅ Complete database overview
- ✅ Real-time search across tables
- ✅ Responsive design
- ✅ Secure admin authentication
- ✅ Modern React UI/UX

## 🛠️ Troubleshooting

### Port Conflicts
If port 3000 is busy:
```bash
npm run dev -- --port 3001
```

### PHP Integration Issues
Ensure XAMPP Apache is running on port 80.

### Build Issues
Clear node_modules and reinstall:
```bash
rm -rf node_modules package-lock.json
npm install
```

## 📝 Notes

- Admin page requires role_id = 4
- PHP sessions must be enabled
- Cross-origin requests handled by Vite proxy
- CSS imported from React components

## 🎯 Development Workflow

1. **Make changes** to `src/components/AdminPage.jsx`
2. **Hot reload** automatically updates the page
3. **Test authentication** with admin account
4. **Build for production** when ready
5. **Deploy** `dist/` folder to web server

Happy coding! 🚀
