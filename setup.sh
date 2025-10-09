#!/bin/bash

echo "🚀 Setting up MediAI React Admin Panel..."
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    echo "   Download from: https://nodejs.org/"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

echo "✅ Node.js version: $(node --version)"
echo "✅ npm version: $(npm --version)"
echo ""

# Install dependencies
echo "📦 Installing React dependencies..."
npm install

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully!"
else
    echo "❌ Failed to install dependencies"
    exit 1
fi

echo ""
echo "🎉 Setup complete! Next steps:"
echo ""
echo "1. Start XAMPP (Apache + MySQL)"
echo "2. Run: npm run dev"
echo "3. Open: http://localhost:3000"
echo "4. Login as admin (role_id = 4)"
echo ""
echo "📖 See REACT_SETUP.md for detailed instructions"
