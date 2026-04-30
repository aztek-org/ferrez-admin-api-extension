# Ferrez Admin API

Secure admin API for OpenCart 4 with HMAC authentication, scoped read/write access, and admin dashboard for monitoring API activity.

## Features

- **HMAC Authentication**: Secure API requests with HMAC-SHA256 signatures
- **Scoped Access**: Fine-grained permissions for API users (read/write per resource)
- **Admin Dashboard**: Monitor API users, endpoints, scopes, and activity
- **Multiple Endpoints**: Comprehensive coverage of admin resources:
  - Categories
  - Coupons
  - Customer groups
  - Customers
  - Manufacturers
  - Orders
  - Products
  - Returns
  - Vouchers
  - And metadata endpoints
- **CORS Support**: Configure allowed origins and IP whitelisting
- **Language Support**: Full English and Spanish UI

## Installation

1. Extract the OCMOD zip file or copy the extension folder to your OpenCart `extension/` directory
2. Navigate to **Extensions** -> **Extensions** -> **Modules** in your OpenCart admin
3. Find **Ferrez Admin API** and click **Install**
4. Access the dashboard via **Modules** -> **Ferrez Admin API** or through the main sidebar

## Quick Start

### 1. Create API User

1. Go to **System** -> **Users** -> **API**
2. Click **Add New**
3. Enter username and password
4. Configure token generation method

### 2. Assign Scopes

After creating an API user:

1. In the **Ferrez Admin API** dashboard, view **Configured Scopes**
2. Assign read/write permissions for specific resources
3. Save permissions JSON configuration

### 3. Make API Requests

Use your API credentials to sign requests with HMAC-SHA256:

```bash
curl -X GET "http://localhost/api/admin/v1/products" \
  -H "Authorization: HMAC-SHA256 username=myuser signature=..." \
  -H "Content-Type: application/json"
```

## API Endpoints

### Product Endpoints

- `GET /api/admin/v1/products` - List products
- `GET /api/admin/v1/products/{product_id}` - Get product details
- `POST /api/admin/v1/products` - Create product
- `PUT /api/admin/v1/products/{product_id}` - Update product
- `DELETE /api/admin/v1/products/{product_id}` - Delete product

### Category Endpoints

- `GET /api/admin/v1/categories` - List categories
- `GET /api/admin/v1/categories/{category_id}` - Get category
- `POST /api/admin/v1/categories` - Create category
- `PUT /api/admin/v1/categories/{category_id}` - Update category

### Order Endpoints

- `GET /api/admin/v1/orders` - List orders
- `GET /api/admin/v1/orders/{order_id}` - Get order details
- `PUT /api/admin/v1/orders/{order_id}` - Update order status/history

### Customer Endpoints

- `GET /api/admin/v1/customers` - List customers
- `GET /api/admin/v1/customers/{customer_id}` - Get customer
- `POST /api/admin/v1/customers` - Create customer
- `PUT /api/admin/v1/customers/{customer_id}` - Update customer

### Additional Endpoints

- **Coupons**: `GET|POST|PUT /api/admin/v1/coupons`
- **Vouchers**: `GET|POST|PUT /api/admin/v1/vouchers`
- **Manufacturers**: `GET|POST|PUT /api/admin/v1/manufacturers`
- **Customer Groups**: `GET /api/admin/v1/customer_groups`
- **Returns**: `GET|PUT /api/admin/v1/returns`
- **Metadata**: `GET /api/admin/v1/metadata`

## HMAC Authentication

### Request Signing

1. Create a canonical request string:
   ```
   METHOD\nPATH\nQUERY_STRING\nBODY_HASH\nTIMESTAMP
   ```

2. Sign with HMAC-SHA256 using API user's secret key

3. Add `Authorization` header:
   ```
   Authorization: HMAC-SHA256 username=myuser timestamp=1234567890 signature=...
   ```

### Request Timestamp Validation

- Requests must include a `timestamp` parameter
- Server validates timestamp is within acceptable window (default: +/- 5 minutes)
- Prevents replay attacks

## Scopes Configuration

Scopes define read/write permissions per resource. Configure via the admin dashboard:

### Scope Format

```json
{
  "products": {
    "read": true,
    "write": false
  },
  "orders": {
    "read": true,
    "write": true
  },
  "customers": {
    "read": true,
    "write": false
  }
}
```

### Scope Levels

- **Global scopes**: Apply to all API users with matching scopes
- **User-specific scopes**: Override global scopes for individual users
- **Resource-level**: Control read/write at granular level

## CORS & Security

### Configure Allowed Origins

1. Go to **Ferrez Admin API** settings
2. Set **Allowed Origins** (comma-separated)
3. Optionally configure **IP Whitelist**

### Security Best Practices

- Use HTTPS in production
- Rotate API credentials regularly
- Limit API user permissions to necessary resources
- Monitor API activity in the dashboard
- Use IP whitelisting for sensitive environments
- Enable request signing validation

## Admin Dashboard

The extension provides a comprehensive dashboard with:

- **Extension Status**: Enable/disable the API
- **API Users**: View configured users and their scopes
- **Configured Scopes**: Edit and manage permissions
- **Endpoints**: Browse available endpoints
- **Route Reference**: View API routes and methods
- **Last Updated**: Track configuration changes

### Dashboard Navigation

1. **System** -> **Modules** -> **Ferrez Admin API**

### Extension Page Notes

- Route Reference shows extension route and pretty route base URLs.
- The dashboard now includes a quick reminder for product image upload.
- For product create/update, send images with `multipart/form-data` and file field `image_file`.

## Product Images

Recommended upload format for product images is **multipart/form-data** using the file field `image_file`.

- Endpoint: `POST /api/admin/v1/product` (create)
- Endpoint: `PUT /api/admin/v1/product/{product_id}` (update)
- File field: `image_file`
- Allowed extensions: `jpg`, `jpeg`, `png`, `gif`, `webp`
- Default max size: 5 MB (configurable via `module_ferrez_admin_rest_api_max_upload_size`)

Example:

```bash
curl -X POST "http://localhost:8080/api/admin/v1/product?route=extension/ferrez_admin_rest_api/api/admin&username=...&store_id=0&language=es-es&currency=MXN&time=...&signature=..." \
  -F "name=Demo Product" \
  -F "model=DEMO-001" \
  -F "price=149.90" \
  -F "quantity=5" \
  -F "image_file=@C:/tmp/product.jpg"
```

## Troubleshooting

### "Unauthorized" Response

1. Check HMAC signature calculation
2. Verify timestamp is within acceptable range
3. Confirm API user has required scopes
4. Check if user is enabled

### "Forbidden" Response

1. Verify API user has read/write permission for resource
2. Check scope configuration in dashboard
3. Ensure request includes valid authentication

### Scope Not Applied

1. Verify permissions JSON is valid JSON
2. Check that scope is correctly configured in dashboard
3. Restart OpenCart or clear cache

### CORS Issues

1. Add origin to **Allowed Origins** list
2. Verify the exact origin URL (including protocol)
3. Check IP whitelisting if configured

## Response Format

All responses follow JSON:API standard structure:

### Success Response

```json
{
  "data": {
    "id": 1,
    "type": "product",
    "attributes": {
      "name": "Product Name",
      "price": 99.99,
      "status": 1
    }
  }
}
```

### Error Response

```json
{
  "errors": [
    {
      "status": 400,
      "title": "Bad Request",
      "detail": "Invalid parameter: category_id"
    }
  ]
}
```

## Developer Notes

### File Structure

```
ferrez_admin_rest_api/
|- install.json                  # Metadata
|- admin/
|  |- controller/module/ferrez_admin_rest_api.php    # Admin module handler
|  |- view/template/module/ferrez_admin_rest_api.twig # Admin UI
|  `- language/
|     |- en-gb/module/ferrez_admin_rest_api.php
|     `- es-es/module/ferrez_admin_rest_api.php
`- catalog/
   |- controller/
   |  |- api/admin.php                # Main API controller
   |  |- api/base.php                 # Base API class
   |  `- startup/admin_api.php        # Route registration
   |- model/api/
   |  |- category.php
   |  |- coupon.php
   |  |- customer_group.php
   |  |- customer.php
   |  |- manufacturer.php
   |  |- metadata.php
   |  |- order.php
   |  |- product.php
   |  |- returns.php
   |  `- voucher.php
   `- language/en-gb/api/admin.php
```

### API Request Lifecycle

1. Request arrives at `/api/admin/v1/{resource}`
2. HMAC signature validation
3. Authentication/Authorization check
4. Scope permission verification
5. Resource handler execution
6. Response serialization (JSON:API format)

## Support & Documentation

- **GitHub**: [aztek-org/ferrez-admin-api-extension](https://github.com/aztek-org/ferrez-admin-api-extension)
- **Issues**: Report bugs or feature requests on GitHub Issues

## Version History

### 1.2.2
- Added product image upload reminder on the extension admin page
- Updated repository documentation for extension page and multipart upload guidance

### 1.2.1
- Fixed README character encoding issues
- Added multipart image upload capability for product create/update API

### 1.1.0
- Renamed to Ferrez Admin API (simplified branding)
- HMAC-SHA256 authentication
- Scoped read/write access control
- Admin dashboard with monitoring
- Multi-language support (EN/ES)
- Comprehensive endpoint coverage

### 1.0.0
- Initial release as Ferrez Admin API

## License

Proprietary - Ferrez.mx

## Author

Ferrez.mx Development Team

