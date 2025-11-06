# MKPay API Demo Requests

This directory contains HTTP request files for testing and demonstrating the MKPay API endpoints using the [REST Client](https://marketplace.visualstudio.com/items?itemName=humao.rest-client) VSCode extension.

## 📁 Available Demo Files

### Authentication & Admin Management
- **`admin-auth-demo.http`** - Admin authentication and user management
  - Login/Logout
  - User CRUD operations
  - Role assignment

### Account Management
- **`admin-accounts-demo.http`** - Account management (Admin API)
  - Create PF (Individual) and PJ (Business) accounts
  - Account status management (suspend, activate, block, verify)
  - Document validation workflow
  - Account activity logs

### Applications & Tokens
- **`admin-apps-demo.http`** - App and API token management (Admin API)
  - Create and manage integration apps
  - Generate API tokens with granular permissions
  - Token lifecycle (create, update, revoke)
  - App statistics and monitoring

### Wallets & Transactions
- **`admin-wallets-demo.http`** - Wallet and transaction management (Admin API)
  - Wallet balance management
  - Manual balance adjustments
  - Transaction approval/rejection
  - Currency exchange rates
  - Financial reports
  - Withdrawal management

### Integration API
- **`integration-api-demo.http`** - Public Integration API (for client applications)
  - Payment creation (PIX, Credit Card)
  - Transaction management
  - Customer management
  - Wallet operations
  - Currency exchange
  - Webhook configuration
  - Reports and analytics

### Tenancy
- **`tenants-demo.http`** - Multi-tenancy operations
  - Tenant creation and management

## 🚀 Getting Started

### Prerequisites
1. Install [VSCode](https://code.visualstudio.com/)
2. Install [REST Client extension](https://marketplace.visualstudio.com/items?itemName=humao.rest-client)
3. Make sure your Laravel backend is running

### Configuration

Each `.http` file has environment variables at the top. Update these before running requests:

```http
####### ENVIRONMENTS #########
@APP_URL=http://localhost:8000
@ADMIN_EMAIL=admin@mail.com
@ADMIN_PASSWORD=power@123
#######
```

For Integration API, you'll need:
```http
@API_URL=http://localhost:8000/api/v1
@APP_ID=your-app-id-here
@APP_SECRET_TOKEN=your-secret-token-here
```

### How to Use

1. **Open any `.http` file** in VSCode
2. **Click "Send Request"** above any HTTP request
3. **View the response** in the right panel
4. **Chain requests** using the `@name` and variable extraction features

Example of request chaining:
```http
### Create Account
# @name createAccount
POST {{APP_URL}}/admin/accounts
...

### Extract Account UUID
@accountUuid = {{createAccount.response.body.data.uuid}}

### Get Account Details
GET {{APP_URL}}/admin/accounts/{{accountUuid}}
```

## 📝 Request Organization

### Separating Requests
Use `###` to separate multiple requests:
```http
### First Request
GET {{APP_URL}}/api/endpoint1

### Second Request
POST {{APP_URL}}/api/endpoint2
```

### Named Requests
Use `# @name requestName` to reference responses:
```http
# @name login
POST {{APP_URL}}/login
```

### Variables
**File-level variables:**
```http
@token = my-token-value
```

**Extract from response:**
```http
@token = {{login.response.body.token}}
@userId = {{createUser.response.body.data.id}}
```

**System variables:**
- `{{$guid}}` - Generate UUID
- `{{$timestamp}}` - Current timestamp
- `{{$datetime iso8601}}` - Current date in ISO 8601 format
- `{{$randomInt min max}}` - Random integer

## 🔐 Authentication

### Admin API
Uses Bearer token authentication:
```http
Authorization: Bearer {{token}}
```

Get token from login:
```http
# @name login
POST {{APP_URL}}/admin/login
Content-Type: application/json

{
    "email": "admin@mail.com",
    "password": "power@123"
}

### Extract token
@token = {{login.response.body.token}}
```

### Integration API
Uses custom headers:
```http
X-App-Id: {{APP_ID}}
X-App-Secret: {{APP_SECRET_TOKEN}}
```

## 🎯 Common Workflows

### 1. Create and Verify an Account
```
1. admin-auth-demo.http → Login
2. admin-accounts-demo.http → Create PF Account
3. admin-accounts-demo.http → Submit Document
4. admin-accounts-demo.http → Approve Document
5. admin-accounts-demo.http → Mark as Verified
```

### 2. Setup Integration App
```
1. admin-auth-demo.http → Login
2. admin-accounts-demo.http → Create Account
3. admin-apps-demo.http → Create App
4. admin-apps-demo.http → Create API Token
5. integration-api-demo.http → Test Payment (use generated token)
```

### 3. Process a Payment
```
1. integration-api-demo.http → Create PIX Payment
2. integration-api-demo.http → Get Payment Status
3. integration-api-demo.http → Get Transaction Details
```

### 4. Financial Operations
```
1. admin-auth-demo.http → Login
2. admin-wallets-demo.http → List Wallets
3. admin-wallets-demo.http → Get Wallet Balances
4. admin-wallets-demo.http → Transaction Summary Report
```

## 📚 API Documentation

For detailed API documentation, access:
- **Scramble Docs**: `http://localhost:8000/docs/api`
- **Implementation Spec**: See `@implementar.md` in project root

## 🛠️ Tips & Tricks

### 1. Quick Testing
Use VSCode command palette (`Ctrl+Shift+P` / `Cmd+Shift+P`):
- `Rest Client: Send Request`
- `Rest Client: Cancel Request`
- `Rest Client: Rerun Last Request`

### 2. Saving Responses
Click "Save Response" to save the response body to a file for later analysis.

### 3. Environment Switching
Create multiple environment variables in VSCode settings:
```json
{
    "rest-client.environmentVariables": {
        "local": {
            "APP_URL": "http://localhost:8000"
        },
        "staging": {
            "APP_URL": "https://staging.mkpayments.com"
        },
        "production": {
            "APP_URL": "https://api.mkpayments.com"
        }
    }
}
```

Then use:
```http
@APP_URL = {{$dotenv APP_URL}}
```

### 4. Request Comments
Add notes to requests:
```http
# @note This permanently deletes the user
# @no-redirect
DELETE {{APP_URL}}/users/{{userId}}
```

## 🔗 Related Resources

- [REST Client Documentation](https://marketplace.visualstudio.com/items?itemName=humao.rest-client)
- [REST Client GitHub](https://github.com/Huachao/vscode-restclient)
- [HTTP Specification](https://developer.mozilla.org/en-US/docs/Web/HTTP)

## 🤝 Contributing

When adding new endpoints:
1. Create a new `.http` file or add to existing one
2. Follow the naming convention: `{area}-demo.http`
3. Include environment variables at the top
4. Add descriptive comments
5. Use request chaining where appropriate
6. Update this README with the new file description
