# USTHB Scolarité System

Academic management system for USTHB students, teachers, and administrators.

## Installation & Setup

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx with mod_rewrite

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repo-url>
   cd my_backend
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=usthb_scolarite
   DB_USER=your_db_user
   DB_PASS=your_db_password
   APP_URL=http://your-domain.com/my_backend
   ```

3. **Create database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

4. **Set file permissions**
   ```bash
   chmod 755 . -R
   ```

5. **Access the application**
   - Admin: `http://localhost/my_backend/admin/admin.php`
   - Teacher: `http://localhost/my_backend/teacher/teacher.php`
   - Student: `http://localhost/my_backend/student/student.php`

Default credentials (from schema):
- Email: `admin@usthb.dz` / Password: `password123`

## Database

- Database name: `usthb_scolarite`
- Charset: UTF-8MB4
- Schema: [database/schema.sql](database/schema.sql)

## Features

- **Students**: View grades, classes, assignments, attendance
- **Teachers**: Manage grades, record absences, email students
- **Admins**: Manage users, modules, enrollments, grade review

## Project Structure

```
├── admin/              # Admin dashboard & management
├── teacher/            # Teacher interface
├── student/            # Student portal
├── public/             # Authentication (login, register, logout)
├── includes/           # Shared functions & config
├── assets/             # CSS styles
└── database/           # Schema SQL
```

## Notes

- **One teacher per module**: Each teacher is assigned exactly one module per academic year
- **Auto-enrollment**: New students are auto-enrolled in all modules matching their level
- **Environment variables**: All credentials use `.env` for portability

## License

Internal use only.
