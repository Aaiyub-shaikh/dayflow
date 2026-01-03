# 🚀 Quick Start Guide - Employee Management System

## Fastest Way to Run the Project

### Option 1: Double-click to Run (Recommended)
1. **Double-click** `start_server.bat` in File Explorer
2. Wait for "Server will be available at: http://localhost:8000"
3. **Open your browser** and go to: `http://localhost:8000`

### Option 2: PowerShell
1. **Right-click** in the `dayflow` folder and select "Open PowerShell window here"
2. **Run**: `.\start_server.ps1`
3. **Open your browser** and go to: `http://localhost:8000`

### Option 3: Command Line
1. **Open Command Prompt** in the `dayflow` folder
2. **Run**: `start_server.bat`
3. **Open your browser** and go to: `http://localhost:8000`

## What You'll See

### 1. Server Dashboard (`http://localhost:8000`)
- System status overview
- Quick links to all features
- Health monitoring

### 2. Employee Management App (`http://localhost:8000/index.html`)
- **Employee View**: Submit leave requests, view status
- **Admin View**: Approve/reject requests, view all employees
- **Role Switcher**: Switch between Employee and Admin modes

### 3. API Documentation (`http://localhost:8000/api.php`)
- Available endpoints
- API health status
- JSON responses

## Troubleshooting

### ❌ "PHP is not installed"
**Download PHP from**: https://windows.php.net/download/
- Extract to `C:\php`
- Add `C:\php` to your system PATH
- Restart Command Prompt

### ❌ "API connection issue"
1. **Make sure the server is running** (see console output)
2. **Check the URL**: Should be `http://localhost:8000`
3. **Try refreshing** the page
4. **Check browser console** for errors (F12)

### ❌ "Database error"
1. **Run**: `http://localhost:8000/debug_db.php`
2. **Follow the fix instructions** shown on that page

## Testing the Features

### As Employee:
1. Click **"👤 Employee"** role button
2. Click **"🏖️ Leave Requests"** card
3. Fill out the leave request form
4. Submit and see it in "Recent Activity"

### As Admin:
1. Click **"👨‍💼 Admin"** role button  
2. See **pending requests** in the table
3. Click **"Approve"** or **"Reject"** buttons
4. Add optional comments

## File Structure
```
dayflow/
├── start_server.bat        ← Double-click to run!
├── index.html             ← Main application
├── api.php               ← API endpoints
├── debug_db.php          ← Database troubleshooting
├── server_index.html     ← Server dashboard
└── database/
    └── ems.db            ← SQLite database
```

## Success! 🎉
If you see the Employee Management System interface with working leave requests, everything is running correctly!

**Need Help?** Check the full `README.md` for detailed documentation.