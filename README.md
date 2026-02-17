# Ovesio - Content AI Module for PrestaShop 9.x

## Overview

The Ovesio - Content AI Module integrates PrestaShop with [Ovesio.com](https://ovesio.com/), enabling AI-powered translations and automatic content generation (descriptions and SEO meta tags) for your e-commerce store.

## Key Features

### Compatibility

- Fully compatible with PrestaShop 9.0+
- Built with modern PrestaShop architecture using Symfony controllers

### Translation Capabilities

- Translations are performed automatically in the background
- Once a translation is completed, it is updated on the website — translations are not instant
- Translations are available for:
  - Product names
  - Descriptions
  - Meta titles and keywords
  - Feature attributes
  - Feature values
  - Attribute groups
  - Product combinations/attributes
  - Category names and descriptions
- Translations are not repeated unless the original content has changed

### Description Generation

- Automatic AI-generated descriptions for both products and categories
- Custom logic for when to generate:
  - For products/categories with existing descriptions shorter than a defined character limit
  - For out-of-stock products
  - For disabled products and categories
- Two modes available:
  - One time only
  - On each resource update
- Descriptions are also performed in the background, not instantly

### SEO Meta Tag Generation

- Fully automated generation of SEO meta titles, meta descriptions, and meta keywords using Ovesio - Content AI
- Configurable from the AI SEO MetaTags Generator section
- Available for both products and categories
- SEO meta tag generation supports:
  - Out-of-stock products
  - Disabled products and categories
- Two generation modes supported:
  - One time only (meta tags are generated a single time)
  - On each product or category update (meta tags are regenerated every time a resource is edited)

### AI Description Generator Options

From the AI Description Generator section:

- Enable/disable product and category description generation
- Set thresholds:
  - Ignore product descriptions longer than X characters
  - Ignore category descriptions longer than X characters
- Set inclusion logic for out-of-stock and disabled products/categories
- Choose when new descriptions should be created:
  - One time only
  - On each update

### Cron Integration

- Automate processing with a cron job:
  ```bash
  */5 * * * * curl -k -L "https://yourdomain.com/ovesio/cron?hash=YOUR_HASH" > /dev/null 2>&1
  ```
- The cron job:
  - Runs every 5 minutes (recommended)
  - Processes queued entries
  - Triggers translations, description and meta tag generation
- The cron URL and hash are available in the module configuration

## Installation

### Step 1: Upload and Install the Module

1.  **Download** the latest zip archive called `ovesio.zip` from the [Releases](https://github.com/ovesio/content-ai-prestashop-9.x-module/releases) section.
2.  **Upload:**
    *   Log in to your PrestaShop Admin Panel (Back Office).
    *   Navigate to **Improve** > **Modules** > **Module Manager**.
    *   Click the **Upload a module** button.
    *   Select or drag & drop the downloaded zip archive.
    *   Click the **Install** button.
    *   After installation, click the **Configure** button.

### Step 2: Configure the Module

#### General Tab

- Enable the module
- Set the API URL (default: `https://api.ovesio.com/v1/`)
- Enter your API Token (obtain from [Ovesio.com](https://ovesio.com/))
- Select your catalog default language
- Set up the cron job for automatic processing using the provided URL and hash

#### AI Description Generator Tab

- Enable/disable product and category description generation
- Set character thresholds for descriptions
- Include/exclude out-of-stock or disabled entries
- Choose generation frequency:
  - One time only
  - On each update

#### AI SEO MetaTags Generator Tab

- Enable/disable SEO meta tag generation for products and categories
- Choose whether to include:
  - Out-of-stock products
  - Disabled products and categories
- Choose generation frequency:
  - One time only
  - On each update

#### Translate Settings Tab

- Enable/disable translation features
- Select languages and source/target mappings
- Choose translatable fields:
  - Name
  - Description
  - Tags
  - Meta Title
  - Meta Description
  - Meta Keywords
- Enable translation of additional product attributes, features, and combinations

## Technical Details

### Requirements

- PHP >= 7.2.5
- PrestaShop 9.0.0 or higher
- Composer (for development)
- Valid Ovesio API Token

### Module Structure

The module follows PrestaShop 9 best practices:

- **Controllers**: Symfony-based admin controllers in `src/Controller/Admin/`
- **Models**: Database operations in `model/`
- **SDK**: Ovesio API client in `lib/sdk/`
- **Hooks**: Automated triggers for product/category updates
- **Queue System**: Background processing for AI operations

### Registered Hooks

The module registers the following PrestaShop hooks:

- `actionObjectProductUpdateAfter` - Triggered after product update
- `actionObjectCategoryUpdateAfter` - Triggered after category update
- `actionObjectFeatureUpdateAfter` - Triggered after feature update
- `actionObjectFeatureValueAddAfter` - Triggered after feature value add
- `actionObjectFeatureValueUpdateAfter` - Triggered after feature value update
- `actionObjectAttributeGroupUpdateAfter` - Triggered after attribute group update
- `actionObjectProductAttributeAddAfter` - Triggered after combination add
- `actionObjectProductAttributeUpdateAfter` - Triggered after combination update
- `displayDashboardToolbarTopMenu` - Dashboard integration
- `actionAdminControllerSetMedia` - Admin assets loading

## Usage Summary

- Descriptions, meta tags, and translations are executed asynchronously in the background
- Operations can be triggered once or on every update, based on configuration
- Translations are processed once unless content is modified
- Use the activity list to monitor processing status and errors
- Ensure cron is set up for full automation

## Troubleshooting

### Module not appearing in Module Manager

- Ensure the folder is named exactly `ovesio` in the `modules/` directory
- Check file permissions (folders: 755, files: 644)
- Clear PrestaShop cache from **Advanced Parameters > Performance**

### Translations not processing

- Verify API token is correct
- Check that cron job is running properly
- Review the activity list for errors
- Ensure the callback URL is accessible

### API connection errors

- Verify the API URL is correct (default: `https://api.ovesio.com/v1/`)
- Check your API token is valid
- Ensure your server can make outbound HTTPS requests
- Check firewall settings

## Development

### Installing Dependencies

```bash
composer install
```

### File Structure

```
ovesio/
├── autoload.php              # Module autoloader
├── composer.json             # Composer configuration
├── config/                   # Module configuration
│   ├── routes.yml           # Custom routes
│   └── services.yml         # Service definitions
├── controllers/front/        # Frontend controllers
│   ├── callback.php         # API callback handler
│   └── cronjob.php          # Cron job handler
├── lib/sdk/                  # Ovesio SDK
├── model/                    # Database models
├── src/Controller/Admin/     # Admin controllers
├── translations/             # PrestaShop translations
├── vendor/                   # Composer dependencies
└── views/                    # Templates and assets
```

## Version

Current version: 1.2

## Support

This module is developed and maintained by Aweb Design SRL for Ovesio.com. For documentation and API references, visit [https://docs.ovesio.com](https://docs.ovesio.com).

For technical support or bug reports, please contact Ovesio support.

## Author

Aweb Design SRL

## License

MIT License. See LICENSE file for details.

## Disclaimer

This module is provided "AS IS", without warranty of any kind, express or implied.
The authors and contributors are not liable for any claim, damages, or other liability,
whether in an action of contract, tort, or otherwise, arising from, out of, or in connection
with the module or the use or other dealings in the module.

This is an open-source community module and is not an official module of PrestaShop

This module may rely on third-party services/APIs. Availability and output quality may vary.

Use this module at your own risk. Always test in a staging environment before deploying to production.
