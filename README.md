# Bricks Dynamic Template Control

Contributors: luisameglio
Tags: bricks, template, dynamic, builder
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that allows you to apply fallback Bricks templates to selected post types that are not built with Bricks Builder.

## Description

Bricks Dynamic Template Control (BDTC) is a powerful plugin that enhances your Bricks Builder workflow by allowing you to set up multiple fallback templates for different content types. This is particularly useful when you have content that wasn't built with Bricks but you want to maintain a consistent design across your site.

### Key Features

- **Multiple Fallback Templates**: Create multiple rules with different conditions
- **Template Type Selection**: Choose which types of Bricks templates to use (Header, Footer, Content, etc.)
- **Conditional Rules**: Apply templates based on:
  - Post Types
  - User Roles
  - Categories/Terms
- **Priority System**: Set the order in which rules are applied
- **Conflict Prevention**: Automatic validation to prevent template and post type conflicts
- **Modern Interface**: Clean, user-friendly admin interface with AJAX-powered updates

## Requirements

- WordPress 5.0 or higher
- Bricks Builder theme (either as active theme or parent theme)
- PHP 7.4 or higher

## Installation

1. Download the plugin files
2. Upload the plugin folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Bricks > Bricks DTC to configure your fallback templates

## Usage

1. **Configure Template Types**
   - Go to the Settings tab
   - Select which template types you want to use in your rules
   - Save your settings

2. **Create Fallback Rules**
   - Go to the Template Rules tab
   - Click "Add New Rule" to create a new fallback template rule
   - Configure the rule settings:
     - Select a template
     - Choose post types
     - Set user role restrictions (optional)
     - Add category/term conditions (optional)
     - Set priority
   - Save your rules

3. **Manage Rules**
   - Add multiple rules with different conditions
   - Set priorities to control the order of application
   - Delete rules you no longer need
   - Reset all rules if needed

## How It Works

The plugin hooks into Bricks Builder's template system and applies your configured fallback templates when:
- The current post is not built with Bricks
- The post type matches your rule conditions
- Any additional conditions (user role, categories) are met

## Support

If you find this plugin helpful, consider [buying me a coffee](https://buymeacoffee.com/luisameglio) to support its development.

## License

This plugin is licensed under the GPL v2 or later.

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
