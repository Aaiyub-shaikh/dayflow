#!/usr/bin/env python3
"""
Quick test script to verify the server is working
"""

import sqlite3
import os
import sys

def test_database():
    """Test database creation and basic operations"""
    print("🧪 Testing database functionality...")
    
    # Import the server module to use its database initialization
    sys.path.insert(0, os.path.dirname(__file__))
    from server import EMSHandler
    
    # Create a dummy handler to access database methods
    class TestHandler(EMSHandler):
        def __init__(self):
            pass
    
    handler = TestHandler()
    
    try:
        # Initialize database
        handler.initialize_database()
        print("✅ Database initialized successfully")
        
        # Test database connection
        conn = handler.get_db_connection()
        
        # Test queries
        cursor = conn.execute("SELECT COUNT(*) as count FROM employees")
        employee_count = cursor.fetchone()['count']
        print(f"✅ Found {employee_count} employees in database")
        
        cursor = conn.execute("SELECT COUNT(*) as count FROM leave_types")
        leave_types_count = cursor.fetchone()['count']
        print(f"✅ Found {leave_types_count} leave types in database")
        
        cursor = conn.execute("SELECT COUNT(*) as count FROM leave_requests")
        requests_count = cursor.fetchone()['count']
        print(f"✅ Found {requests_count} leave requests in database")
        
        # Test leave request creation
        cursor = conn.execute("""
            INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        """, ('E001', 1, '2026-01-20', '2026-01-22', 3, 'Test leave request'))
        
        conn.commit()
        print("✅ Successfully created test leave request")
        
        # Clean up test data
        conn.execute("DELETE FROM leave_requests WHERE reason = 'Test leave request'")
        conn.commit()
        print("✅ Cleaned up test data")
        
        conn.close()
        print("✅ All database tests passed!")
        return True
        
    except Exception as e:
        print(f"❌ Database test failed: {e}")
        return False

def test_server_components():
    """Test server components without starting the server"""
    print("\n🧪 Testing server components...")
    
    try:
        # Test that we can import server modules
        import http.server
        import socketserver
        import json
        import sqlite3
        import urllib.parse
        import datetime
        print("✅ All required Python modules are available")
        
        # Test JSON serialization
        test_data = {
            'success': True,
            'message': 'Test message',
            'timestamp': datetime.datetime.now()
        }
        json_str = json.dumps(test_data, default=str)
        print("✅ JSON serialization working")
        
        # Test URL parsing
        parsed = urllib.parse.urlparse('/api/leave_requests?leave_types=1')
        query_params = urllib.parse.parse_qs(parsed.query)
        print("✅ URL parsing working")
        
        return True
        
    except Exception as e:
        print(f"❌ Server component test failed: {e}")
        return False

def main():
    """Run all tests"""
    print("🚀 Employee Management System - Server Test")
    print("=" * 50)
    
    # Test server components
    components_ok = test_server_components()
    
    # Test database
    database_ok = test_database()
    
    print("\n" + "=" * 50)
    if components_ok and database_ok:
        print("✅ All tests passed! The server is ready to run.")
        print("\nTo start the server:")
        print("• Double-click: start_python_server.bat")
        print("• Command line: python server.py")
        print("• Then visit: http://localhost:8000/index.html")
    else:
        print("❌ Some tests failed. Please check the error messages above.")
    
    print("=" * 50)

if __name__ == "__main__":
    main()