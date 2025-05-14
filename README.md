# Bricks Dynamic Template Control

A WordPress plugin that allows you to apply fallback Bricks templates to selected post types not built with Bricks. Includes admin settings for fine-grained control.

## Description

This plugin provides a user-friendly interface to manage fallback templates for posts and pages that haven't been built with Bricks. It allows you to:

- Set up multiple fallback templates with different conditions
- Control which template types are available (header, footer, content, etc.)
- Apply templates based on post types, user roles, and categories
- Set priority levels for template rules
- Manage all settings through a clean admin interface

## Version History

### 1.2
- Fixed notification system for all actions (save rules, reset rules, save settings)
- Improved error handling and user feedback
- Added proper JavaScript checks to prevent errors
- Enhanced settings form submission handling
- Added debug logging for better troubleshooting
- Improved code organization and reliability

### 1.1
- Added support for multiple template types
- Improved template selection interface
- Added template type filtering
- Enhanced error handling
- Added template conflict detection

### 1.0
- Initial release

## Installation

1. Upload the `bricks-dynamic-template-control` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Bricks > Bricks DTC to configure your template rules

## Requirements

- WordPress 5.0 or higher
- Bricks Builder theme
- PHP 7.4 or higher

## Features

- Multiple fallback template support
- Post type targeting
- User role-based templates
- Category/term-based templates
- Priority system for template rules
- Template type filtering
- Conflict detection
- Clean admin interface
- Real-time feedback and notifications

## Usage

1. Go to Bricks > Bricks DTC in your WordPress admin
2. Configure which template types you want to use in the Settings tab
3. Add template rules in the Template Rules tab
4. For each rule:
   - Select a template
   - Choose target post types
   - Set user role restrictions (optional)
   - Add category/term restrictions (optional)
   - Set priority level
5. Save your rules

## Support

For support, please visit [luisameglio.com](https://luisameglio.com) or open an issue on GitHub.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Luis Ameglio](https://luisameglio.com)

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
```

## Changelog

### 1.1
- Added support for multiple fallback templates
- Implemented template type selection
- Added conflict prevention
- Improved user interface
- Added priority system

### 1.0
- Initial release 
