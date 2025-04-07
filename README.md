# Pin

Pin is a minimalist, interactive chat platform that allows users to create and share messages in a unique spatial environment. The platform features a clean, modern interface with a focus on user experience and simplicity.

## Features

- **Spatial Chat System**: Users can place and interact with messages in a 2D space
- **Real-time Updates**: Live position and context updates for all users
- **Direct Messaging**: Private communication between users
- **User Management**: 
  - User authentication
  - Admin dashboard for user management
  - Ban/unban functionality
- **Responsive Design**: Works seamlessly across desktop and mobile devices
- **Modern UI**: Clean interface with intuitive navigation

## Technical Stack

- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP
- Data Storage: JSON files
- Icons and Assets: Custom favicon set and touch icons

## Project Structure

```
pin/
├── index.html          # Main landing page
├── chat.html          # Main chat interface
├── room.html          # Room-specific view
├── about.html         # Project information
├── admin/            # Admin-related files
│   ├── admin_dashboard.php
│   ├── admin_login.php
│   └── admin_delete_user.php
├── *.php             # Backend API endpoints
├── *.json            # Data storage files
└── dms/              # Direct message storage
```

## Setup

1. Ensure you have a PHP-enabled web server
2. Clone the repository to your web server's directory
3. Ensure proper write permissions for JSON files
4. Access the application through your web browser

## Security Features

- User authentication system
- Admin access control
- Ban system for user moderation
- Secure direct messaging

## Contributing

Feel free to submit issues and enhancement requests.

## License

This project is proprietary and confidential.

## Contact

For any questions or support, please refer to the about page within the application.
