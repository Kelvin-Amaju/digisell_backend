backend/
├── api/
│   ├── products.php
│   ├── cart.php
│   ├── orders.php
│   ├── register.php
│   ├── verify-otp.php
│   ├── login.php
├── config/
│   ├── database.php
├── lib/
│   ├── mailer.php
├── vendor/ (Composer dependencies)
├── .htaccess
├── composer.json


### Suggested Name
**DigiSell**

**Reasoning**: The name "DigiSell" is concise, modern, and reflects the platform's focus on selling digital products (e.g., software, eBooks) with features like license key generation. It’s catchy, aligns with e-commerce, and is versatile for branding.

```markdown
# DigiSell

DigiSell is a simple e-commerce platform for selling digital products, built with **Next.js** (frontend) and **vanilla PHP** (backend). It features product listings, a shopping cart, checkout with payment integration (Stripe), user authentication with OTP verification, and a license key generator for digital purchases.

### Admin Section
- **URL**: `/admin`
- **Features**:
  - Form to upload new products (name, description, price, thumbnail image).
  - Restricted to users with `admin` role.
  - Responsive form with Tailwind CSS.
- **Backend API**:
  - `POST /api/admin/product_create.php`: Creates a new product with image upload.
  - Requires admin authentication via session.
- **Usage**:
  - Log in as an admin user (set `role = 'admin'` in the `users` table).
  - Navigate to `/admin` via the navigation bar.
  - Fill out the form and upload a thumbnail image to add a product.

## Troubleshooting

### Admin Page Access Denied
- Ensure the user has `role = 'admin'` in the `users` table:
  ```sql
  UPDATE users SET role = 'admin' WHERE email = 'your-admin-email@example.com';

## Features

- **Frontend** (Next.js with Tailwind CSS):
  - Responsive product listings with images, descriptions, and prices.
  - Shopping cart with add/remove functionality and total calculation.
  - Checkout form integrated with Stripe for secure payments.
  - User authentication (login/register) with OTP verification.
  - Order confirmation page displaying license keys.
- **Backend** (Vanilla PHP):
  - RESTful API for managing products, orders, and users.
  - User registration with email-based OTP verification using PHPMailer.
  - Secure password hashing (bcrypt) and session token generation.
  - Inventory management with MySQL database.
  - License key generation (UUID-based) for each purchase.
  - Payment processing with Stripe integration.
- **Security**:
  - Prepared statements to prevent SQL injection.
  - Password hashing and input sanitization.
  - CORS configuration for secure API access.
- **Extras**:
  - Tailwind CSS for modern, responsive styling.
  - OTP expiration (10 minutes) for secure verification.
  - Transactional database operations for order reliability.

## Tech Stack

- **Frontend**: Next.js (TypeScript), Tailwind CSS, Axios, Zustand, Stripe.js
- **Backend**: Vanilla PHP, MySQL, PHPMailer, Stripe PHP SDK, Ramsey UUID
- **Tools**: Composer (PHP dependencies), npm (Node.js dependencies)

## Prerequisites

- **Node.js** (v18 or higher)
- **PHP** (v8.1 or higher)
- **MySQL** (v8.0 or higher)
- **Composer** (for PHP dependencies)
- **SMTP Service** (e.g., Gmail, SendGrid) for sending OTP emails
- **Stripe Account** (for payment processing)

## Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/digisell.git
cd digisell
```

### 2. Backend Setup (Vanilla PHP)
1. **Navigate to the backend directory**:
   ```bash
   cd backend
   ```
2. **Install PHP dependencies**:
   ```bash
   composer install
   ```
3. **Configure the database**:
   - Create a MySQL database named `ecommerce`.
   - Import the database schema from `backend/database.sql`:
     ```sql
     CREATE DATABASE ecommerce;
     ```
     Then run the SQL commands provided in the previous implementation (see Database Schema section).
   - Update `backend/config/database.php` with your MySQL credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your-username');
     define('DB_PASS', 'your-password');
     define('DB_NAME', 'ecommerce');
     ```
4. **Configure PHPMailer**:
   - Update `backend/lib/mailer.php` with your SMTP credentials:
     ```php
     $mail->Username = 'your-email@gmail.com';
     $mail->Password = 'your-app-password';
     ```
   - Ensure you have an app-specific password if using Gmail or configure another SMTP service (e.g., SendGrid).
5. **Set up Stripe**:
   - Add your Stripe secret key in `backend/api/orders.php`:
     ```php
     \Stripe\Stripe::setApiKey('your-stripe-secret-key');
     ```
6. **Run the backend**:
   - Use a PHP-compatible server (e.g., Apache, Nginx) or PHP's built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Ensure `.htaccess` is enabled for clean URLs (included in `backend/.htaccess`).

### 3. Frontend Setup (Next.js)
1. **Navigate to the frontend directory**:
   ```bash
   cd frontend
   ```
2. **Install Node.js dependencies**:
   ```bash
   npm install
   ```
3. **Configure environment variables**:
   - Create a `.env.local` file in `frontend`:
     ```env
     NEXT_PUBLIC_STRIPE_KEY=your-stripe-public-key
     ```
   - Obtain the Stripe public key from your Stripe dashboard.
4. **Run the frontend**:
   ```bash
   npm run dev
   ```
   - The app will be available at `http://localhost:3000`.

### 4. Database Schema
The MySQL database includes the following tables:
- `products`: Stores product details (id, name, description, price, thumbnail).
- `users`: Stores user details (id, name, email, password, otp_code, otp_expires).
- `orders`: Stores order details (id, user_id, total, status).
- `order_items`: Stores order items (id, order_id, product_id, quantity, price).
- `license_keys`: Stores license keys (id, order_id, key).

Run the SQL commands from the implementation to create these tables.

### 5. Usage
1. **Register**:
   - Visit `/register`, enter name, email, and password.
   - Receive an OTP via email, then verify it at `/verify-otp`.
2. **Login**:
   - Visit `/login` to authenticate with email and password.
3. **Browse Products**:
   - View products on the homepage (`/`).
   - Add products to the cart.
4. **Checkout**:
   - Go to `/cart`, then proceed to `/checkout`.
   - Complete payment via Stripe.
5. **View Order**:
   - After payment, view order details and license key at `/order/[orderId]`.

## API Endpoints
- `GET /api/products.php`: List all products.
- `POST /api/register.php`: Register a user and send OTP.
- `POST /api/verify-otp.php`: Verify OTP for registration.
- `POST /api/login.php`: Authenticate a user.
- `POST /api/orders.php`: Create an order and process payment.
- `GET /api/orders.php?id={id}`: Retrieve order details and license key.

## Deployment
- **Backend**:
  - Deploy to a PHP-compatible server (e.g., DigitalOcean, AWS).
  - Configure MySQL, SMTP, and Stripe keys.
  - Enable `.htaccess` for URL rewriting.
- **Frontend**:
  - Deploy to Vercel:
    ```bash
    vercel
    ```
  - Add `NEXT_PUBLIC_STRIPE_KEY` in Vercel’s environment variables.
- **CORS**:
  - Update `backend/api/*.php` to allow the frontend domain:
    ```php
    header('Access-Control-Allow-Origin: http://frontend-domain.com');
    ```

## Security Notes
- Use HTTPS in production.
- Secure SMTP credentials and Stripe keys (store in environment variables).
- Implement CSRF tokens for POST requests in production.
- Rate-limit OTP requests to prevent abuse.

## Future Improvements
- Add cart persistence across sessions.
- Implement a user dashboard for order history.
- Add product search and filtering.
- Write unit tests (PHPUnit for backend, Jest for frontend).
- Integrate additional payment gateways (e.g., PayPal).

## Contributing
Contributions are welcome! Please open an issue or submit a pull request on GitHub.

## License
This project is licensed under the MIT License.

## Contact
For questions or support, contact [your-email@example.com].
```

### Notes
- **README Structure**: The README is concise yet detailed, covering setup, usage, and deployment. It assumes the project is hosted on GitHub and includes placeholders (e.g., `your-username`, `your-email`) that you can replace.
- **DigiSell**: The name is flexible; alternatives could be **"KeyCart"** (emphasizing license keys) or **"Sellify"** (simple and catchy). Let me know if you prefer a different name!
- **Assumptions**: The README assumes the frontend and backend are in separate directories (`frontend` and `backend`) within the repository. Adjust paths if your structure differs.
- **Database SQL**: The README references the SQL schema from the previous implementation. You can create a `database.sql` file in `backend/` with the provided SQL commands for convenience.

If you need adjustments to the README (e.g., specific repository details, additional sections) or want to explore other name ideas, let me know!