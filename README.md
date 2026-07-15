# Employee Management System - Leave & Time-Off Management

A comprehensive web-based Employee Management System with advanced Leave & Time-Off Management functionality.

## 🌟 Features

### Leave & Time-Off Management

#### Apply for Leave (Employee)
- **Multiple Leave Types**: Annual, Sick, Personal, Maternity, Emergency, Study, and Bereavement leave
- **Date Range Selection**: Intuitive date picker with validation
- **Leave Type Information**: Display paid/unpaid status and descriptions
- **Reason Field**: Mandatory reason/remarks for leave requests
- **Overlap Detection**: Prevents overlapping leave requests
- **Real-time Validation**: Client and server-side validation

#### Leave Approval (Admin/HR)
- **Comprehensive Dashboard**: View all pending, approved, and rejected requests
- **Detailed Request Information**: Employee details, leave type, dates, and reasons
- **Approval Workflow**: One-click approve/reject with optional comments
- **Filtering & Search**: Filter by status, department, date range
- **Pagination**: Handle large numbers of requests efficiently
- **Real-time Updates**: Immediate reflection of status changes

#### Status Management
- **Pending**: Initial status when request is submitted
- **Approved**: Admin approved the request
- **Rejected**: Admin rejected the request with optional comments

## 🏗️ Architecture

### Frontend
- **HTML5**: Semantic markup with accessibility features (ARIA labels, roles)
- **CSS3**: Modern styling with CSS custom properties, responsive design
- **JavaScript (ES6+)**: Modular architecture with API integration
- **Progressive Enhancement**: Works without JavaScript for basic functionality

### Backend
- **PHP 7.4+**: REST API with proper error handling
- **SQLite Database**: Lightweight, file-based database
- **PDO**: Secure database interactions with prepared statements
- **RESTful API**: Standard HTTP methods and status codes

### Database Schema
- **employees**: Employee information and roles
- **departments**: Organizational structure
- **leave_types**: Configurable leave types with rules
- **leave_requests**: Leave request data with audit trail
- **attendance**: Employee attendance records (future expansion)

## 📁 Project Structure

```
dayflow/
├── index.html              # Main application interface
├── styles.css             # Comprehensive styling
├── script.js              # Frontend JavaScript with API integration
├── setup.php              # Database initialization script
├── README.md              # This documentation
├── api/                   # Backend API
│   ├── index.php          # API router and health check
│   ├── leave_requests.php # Leave management endpoints
│   └── config/
│       └── Database.php   # Database connection and utilities
└── database/
    ├── init.sql          # Database schema and sample data
    └── ems.db            # SQLite database file (created automatically)
```

## 🚀 Installation & Setup

### Prerequisites
- Web server (Apache, Nginx, or PHP built-in server)
- PHP 7.4 or higher
- SQLite3 extension enabled
- PDO and PDO_SQLite extensions

### Quick Setup

1. **Clone or download** the project files to your web server directory

2. **Run the setup script** in your web browser:
   ```
   http://localhost/dayflow/setup.php
   ```

3. **Access the application**:
   ```
   http://localhost/dayflow/index.html
   ```

### Manual Setup

If you prefer manual setup:

```bash
# Navigate to project directory
cd dayflow

# Create database directory
mkdir -p database

# Set permissions (Linux/Mac)
chmod 755 database
chmod 644 database/init.sql

# Initialize database (via PHP CLI)
php -r "
require 'api/config/Database.php';
\$db = new Database();
\$db->initialize();
echo 'Database initialized successfully!';
"
```

## 🔧 API Endpoints

### Health Check
```
GET /api/health
```
Returns API and database health status.

### Leave Management

#### Get Leave Requests
```
GET /api/leave_requests
GET /api/leave_requests?employee_id=E001
GET /api/leave_requests?status=pending
GET /api/leave_requests?page=1&limit=20
```

#### Submit Leave Request
```
POST /api/leave_requests
Content-Type: application/json

{
  "leave_type_id": 1,
  "start_date": "2026-01-15",
  "end_date": "2026-01-17",
  "reason": "Family vacation"
}
```

#### Approve/Reject Leave Request
```
PUT /api/leave_requests?id=1
Content-Type: application/json

{
  "status": "approved",
  "admin_comments": "Approved for vacation time"
}
```

#### Cancel Leave Request
```
DELETE /api/leave_requests?id=1
```

#### Get Leave Types
```
GET /api/leave_requests?leave_types=1
```

#### Get Statistics
```
GET /api/leave_requests?stats=1
```

## 👤 User Roles & Permissions

### Employee Role
- Submit leave requests
- View own leave history
- Cancel pending requests
- View leave statistics
- Access attendance records

### Admin/HR Role
- All employee permissions
- View all leave requests
- Approve/reject requests
- Add comments to requests
- View system-wide statistics
- Manage employee records

### Demo Users
The system includes sample users for testing:

- **John Doe (E001)**: Employee - Engineering
- **Jane Smith (E002)**: Admin - Marketing  
- **Sarah Williams (E004)**: HR Manager
- **Admin User (A001)**: System Administrator

## 🎨 UI/UX Features

### Responsive Design
- Mobile-first approach
- Tablet and desktop optimized
- Touch-friendly interface
- Accessible navigation

### Accessibility
- ARIA labels and roles
- Keyboard navigation
- Screen reader compatible
- High contrast support
- Focus management

### User Experience
- Intuitive dashboard design
- Real-time form validation
- Loading states and feedback
- Error handling and recovery
- Contextual help and guidance

## 🔍 Development Features

### Code Quality
- **Modular JavaScript**: Organized into logical modules
- **Error Handling**: Comprehensive error catching and user feedback
- **Validation**: Both client-side and server-side validation
- **Security**: SQL injection prevention, XSS protection
- **Performance**: Optimized queries and caching strategies

### API Design
- **RESTful**: Standard HTTP methods and status codes
- **JSON**: Consistent data format
- **Validation**: Input sanitization and validation
- **Error Responses**: Descriptive error messages
- **Pagination**: Efficient data handling

### Database Design
- **Normalized Schema**: Proper relationships and constraints
- **Indexes**: Performance optimization
- **Triggers**: Automatic timestamp updates
- **Views**: Simplified complex queries
- **Sample Data**: Ready-to-use test data

## 🧪 Testing

### Manual Testing Checklist

#### Employee Features
- [ ] Submit leave request with all leave types
- [ ] Validate date range selection
- [ ] Test overlapping request detection
- [ ] Cancel pending requests
- [ ] View leave history and statistics

#### Admin Features
- [ ] View all pending requests
- [ ] Approve requests with comments
- [ ] Reject requests with reasons
- [ ] Filter and search requests
- [ ] View statistics dashboard

#### System Testing
- [ ] API health check
- [ ] Database connectivity
- [ ] Error handling
- [ ] Mobile responsiveness
- [ ] Accessibility features

## 🔧 Configuration

### Leave Types
Customize leave types in the database:
```sql
INSERT INTO leave_types (name, description, is_paid, max_days_per_year, color) 
VALUES ('Custom Leave', 'Description here', true, 10, '#ff6b6b');
```

### User Roles
Update user roles in the employees table:
```sql
UPDATE employees SET role = 'admin' WHERE id = 'E001';
```

## 🚨 Troubleshooting

### Common Issues

#### Database Connection Failed
- Check PHP SQLite extensions are installed
- Verify file permissions on database directory
- Ensure web server can write to database folder

#### API Not Working
- Verify PHP version (7.4+ required)
- Check web server PHP configuration
- Review error logs for specific issues

#### Frontend Not Loading Data
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Test API health endpoint directly

### Debug Mode
Enable detailed error reporting by adding to `api/config/Database.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 🔐 Security Considerations

### Implemented Security
- SQL injection prevention with prepared statements
- XSS protection with HTML escaping
- Input validation and sanitization
- CSRF protection considerations
- Secure session handling

### Production Deployment
For production use, consider:
- HTTPS encryption
- Authentication system (JWT tokens)
- Rate limiting
- Database encryption
- Access logging
- Regular security updates

## 📈 Future Enhancements

### Planned Features
- **Email Notifications**: Automated status change notifications
- **Calendar Integration**: Integration with popular calendar systems
- **Mobile App**: Native mobile application
- **Reporting System**: Advanced analytics and reporting
- **Workflow Automation**: Custom approval workflows
- **Integration APIs**: HR system integrations

### Technical Improvements
- **Real-time Updates**: WebSocket implementation
- **Caching Layer**: Redis/Memcached integration
- **Unit Testing**: Automated test suite
- **CI/CD Pipeline**: Automated deployment
- **Docker Support**: Containerized deployment
- **API Documentation**: OpenAPI/Swagger docs

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create a feature branch
3. Implement changes with tests
4. Submit pull request with description

### Code Standards
- Follow PSR-4 for PHP
- Use ES6+ JavaScript standards
- Maintain consistent indentation
- Add comments for complex logic
- Update documentation for new features

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Create an issue in the repository
- Review the troubleshooting section
- Check the API documentation
- Test with the provided sample data

---

**Employee Management System v1.0** - Built with ❤️ for modern workforce management.
