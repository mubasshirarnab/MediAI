# Node.js Installation Guide for React Admin Panel

## 🚨 Required: Node.js Installation

Node.js এবং npm system এ install নেই। আপনাকে আগে Node.js install করতে হবে।

## 📥 Method 1: Direct Download (Recommended)

### macOS:
1. Visit: https://nodejs.org/
2. Download "LTS" version (recommended)
3. Run the `.pkg` installer
4. Restart Terminal
5. Verify: `node --version` এবং `npm --version`

## 📥 Method 2: Using Homebrew

```bash
# Install Homebrew first (if not installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install Node.js
brew install node
```

## 📥 Method 3: Using Node Version Manager (nvm)

```bash
# Install nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Restart terminal or run: source ~/.bashrc
# Install latest LTS Node.js
nvm install --lts
nvm use --lts
```

## ✅ After Installation:

### 1. Verify Installation:
```bash
node --version    # Should show v18.x.x or higher
npm --version     # Should show 9.x.x or higher
```

### 2. Install React Dependencies:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/MediAI-main
npm install
```

### 3. Start Development Server:
```bash
npm run dev
```

### 4. Access Admin Panel:
- URL: http://localhost:3000
- Login as admin (role_id = 4)

## 🔧 Troubleshooting:

### Port Issues:
```bash
npm run dev -- --port 3001  # Use different port
```

### Permission Issues (macOS):
```bash
sudo chown -R $(whoami) ~/.npm
```

### Clear Cache:
```bash
npm cache clean --force
```

## 📱 Alternative Approach:

যদি Node.js install করতে না পারেন, তাহলে আপনি:
1. PHP files directly browser এ access করতে পারেন
2. get_admin_data.php directly call করে data দেখতে পারেন
3. পরে যখন Node.js install করবেন তখন React admin panel ব্যবহার করবেন

## 📖 Next Steps After Node.js Installation:

1. ✅ Install Node.js
2. ✅ Run `npm install`
3. ✅ Run `npm run dev` 
4. ✅ Open http://localhost:3000
5. ✅ Login as admin and enjoy! 🎉

**Node.js install করার পর আমাকে জানান, আমি admin panel চালু করে দেব!** 🚀
