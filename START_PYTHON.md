# Python Server Quick Start Guide

## 🚀 How to Run the Employee Management System

Since PHP is not available on your system, I've created a **Python-based server** that provides the same functionality.

### Option 1: Double-click to Start
1. Double-click `start_python_server.bat`
2. Press any key when prompted
3. The server will start automatically

### Option 2: Command Line
1. Open Command Prompt (cmd)
2. Navigate to the project folder:
   ```
   cd C:\dayflow
   ```
3. Run the Python server:
   ```
   python server.py
   ```

### 📍 Access Points
Once the server starts, open these URLs in your browser:

- **Main Application**: http://localhost:8000/index.html
- **Server Dashboard**: http://localhost:8000/
- **API Health Check**: http://localhost:8000/api/health

### ✅ Features Working
- ✅ Leave request submission (all 7 leave types)
- ✅ Admin approval/rejection system
- ✅ Real-time dashboard updates
- ✅ SQLite database integration
- ✅ Employee and admin interfaces
- ✅ Date validation and calculations
- ✅ Statistics and reporting

### 🔧 Troubleshooting
If you see "API connection issue":
1. Make sure the Python server is running
2. Check that port 8000 is not used by another program
3. Try refreshing the page after starting the server

### 📊 Sample Data
The system comes with:
- 7 employees across 5 departments
- 7 leave types (Annual, Sick, Personal, etc.)
- 3 sample leave requests for testing

### 🛑 To Stop
Press `Ctrl+C` in the command window to stop the server.

---
**Note**: This Python server provides identical functionality to the original PHP version but works with your Python 3.12.2 installation.