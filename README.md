# 🛒 DailyBasket – Simple Grocery Ordering System

<p align="center">
  <img src="assets/logo.png" alt="DailyBasket Logo" width="250">
</p>

<h3 align="center">Daily Essentials, Delivered Fresh</h3>

<p align="center">
  A simple and user-friendly web-based grocery ordering platform that connects customers with a grocery shop.
</p>

---

## 📌 Project Description

**DailyBasket** is a web-based **Simple Grocery Ordering System** developed for **PS29 – BIZ HACK'26**.

The platform allows customers to browse grocery products, view prices and availability, add products to their cart, update quantities, review their selected items, and place grocery orders.

Shop administrators can manage grocery products, prices, stock availability, customer orders, and order status through an admin interface.

The system provides a complete digital workflow from **product browsing to order tracking**.

---

## 🎯 Problem Statement

### PS29 – Simple Grocery Ordering System

Build a grocery ordering platform where customers can browse grocery items, add products to a cart, and place orders.

The shop should maintain grocery products, prices, and availability. Customers should be able to review their selected items before confirming an order. The system should maintain order details and status after placement.

### Core Requirements

- Shop/admin users can add and manage grocery products.
- Customers can browse products and view prices and availability.
- Customers can add, remove, and update quantities in the cart.
- The cart calculates the total amount.
- Customers can place grocery orders.
- Administrators can view and update order status.
- Customers can view their order status.

---

## 💡 Proposed Solution

DailyBasket provides a simple digital grocery shopping experience.

The system connects customers and shop administrators through a single web platform.

### Customer Module

Customers can:

- Register and log in.
- Browse grocery products.
- Search for products.
- Filter products by category.
- View product images, prices, and availability.
- Add products to the cart.
- Increase or decrease product quantities.
- Remove products from the cart.
- View the total cart amount.
- Enter delivery details.
- Review their selected items.
- Place grocery orders.
- View previous orders.
- Track order status.

### Administrator Module

Administrators can:

- Log in to the admin dashboard.
- Add new grocery products.
- Edit product information.
- Delete products.
- Manage product categories.
- Update product prices.
- Manage stock availability.
- View customer orders.
- View order details.
- Update order status.

---

## ✨ Key Features

### 👤 Customer Features

- 🔐 Customer Registration & Login
- 🛍️ Grocery Product Browsing
- 🔎 Product Search
- 🗂️ Category Filtering
- 💰 Price Display
- 📦 Stock Availability
- 🛒 Shopping Cart
- ➕ Increase Quantity
- ➖ Decrease Quantity
- ❌ Remove Cart Items
- 🧮 Automatic Total Calculation
- 📍 Delivery Details
- ✅ Order Review
- 📋 Order Placement
- 📜 Order History
- 🚚 Order Status Tracking

### 👨‍💼 Admin Features

- 🔐 Admin Login
- 📊 Admin Dashboard
- ➕ Add Products
- ✏️ Edit Products
- 🗑️ Delete Products
- 🗂️ Manage Categories
- 💰 Manage Product Prices
- 📦 Manage Product Stock
- 📋 View Customer Orders
- 🔎 View Order Details
- 🔄 Update Order Status

---

# 🖥️ Screenshots

Screenshots of the developed DailyBasket pages are included below.
use this link  to see images of website
https://drive.google.com/file/d/1-qS1sOkF_fk1K8fDW7Ir7b55Lk6Km3N9/view?usp=drive_link

# 🔄 System Workflow

```text
Customer Login
      ↓
Browse Grocery Products
      ↓
Search / Filter Products
      ↓
View Product Details
      ↓
Add Products to Cart
      ↓
Update Cart Quantities
      ↓
Review Cart and Total
      ↓
Checkout
      ↓
Enter Delivery Details
      ↓
Confirm Order
      ↓
Place Order
      ↓
Order Confirmation
      ↓
Track Order Status
```

## Order Status Flow

```text
Pending
   ↓
Confirmed
   ↓
Preparing
   ↓
Ready
   ↓
Delivered
```

An order may also be marked as:

```text
Cancelled
```

---

# 🏗️ System Architecture

```text
                 ┌──────────────────────┐
                 │      CUSTOMER        │
                 │    WEB INTERFACE     │
                 └──────────┬───────────┘
                            │
                            ↓
                 ┌──────────────────────┐
                 │      FRONTEND        │
                 │   HTML / CSS / JS    │
                 │      Bootstrap       │
                 └──────────┬───────────┘
                            │
                            ↓
                 ┌──────────────────────┐
                 │       BACKEND        │
                 │        PHP 8+        │
                 │ Authentication       │
                 │ Cart & Orders        │
                 └──────────┬───────────┘
                            │
                            ↓
                 ┌──────────────────────┐
                 │       MySQL          │
                 │      DATABASE        │
                 └──────────────────────┘
                            ↑
                            │
                 ┌──────────┴───────────┐
                 │   ADMIN DASHBOARD    │
                 │ Products / Stock     │
                 │ Orders / Status      │
                 └──────────────────────┘
```

---

# 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5 |
| Styling | CSS3 |
| Client-side Logic | JavaScript |
| UI Framework | Bootstrap |
| Backend | PHP 8+ |
| Database | MySQL |
| Database Management | phpMyAdmin |
| Local Server | XAMPP |
| Code Editor | Visual Studio Code |
| Version Control | Git & GitHub |

---

# 🗄️ Database Structure

The application uses MySQL to store users, products, cart items, and orders.

## Users

Stores customer and administrator information.

```text
user_id
name
email
phone
password
role
address
city
pincode
created_at
```

## Categories

Stores grocery product categories.

```text
category_id
name
description
created_at
```

## Products

Stores grocery product information.

```text
product_id
category_id
name
description
price
stock
unit
image
status
created_at
updated_at
```

## Cart

Stores products currently selected by customers.

```text
cart_id
user_id
product_id
quantity
created_at
updated_at
```

## Orders

Stores customer order information.

```text
order_id
user_id
total_amount
delivery_charge
status
delivery_name
delivery_phone
delivery_address
delivery_city
delivery_pincode
created_at
updated_at
```

## Order Items

Stores individual products included in an order.

```text
order_item_id
order_id
product_id
product_name
quantity
price
subtotal
```

---

# 📁 Project Structure

```text
DailyBasket/
│
├── assets/
│   ├── logo.png
│   ├── images/
│   └── icons/
│
├── screenshots/
│   ├── homepage.png
│   ├── login.png
│   ├── register.png
│   ├── products.png
│   ├── product-details.png
│   ├── cart.png
│   ├── checkout.png
│   ├── order-confirmation.png
│   ├── my-orders.png
│   ├── order-tracking.png
│   ├── admin-dashboard.png
│   ├── admin-products.png
│   └── admin-orders.png
│
├── css/
│   ├── style.css
│   └── responsive.css
│
├── js/
│   ├── script.js
│   └── cart.js
│
├── admin/
│   ├── dashboard.php
│   ├── products.php
│   ├── categories.php
│   ├── orders.php
│   └── users.php
│
├── customer/
│   ├── profile.php
│   ├── orders.php
│   └── order-details.php
│
├── includes/
│   ├── db.php
│   ├── header.php
│   └── footer.php
│
├── database/
│   └── dailybasket.sql
│
├── index.php
├── login.php
├── register.php
├── products.php
├── product-details.php
├── cart.php
├── checkout.php
├── order-success.php
│
└── README.md
```

> The folder structure should be updated if the actual project structure is different.

---

# 🚀 Setup and Run Instructions

## Prerequisites

Install the following:

- XAMPP
- PHP 8 or above
- MySQL
- phpMyAdmin
- Web Browser
- Visual Studio Code

---

## Step 1 – Clone the Repository

```bash
git clone https://github.com/YOUR-USERNAME/DailyBasket.git
```

Replace `YOUR-USERNAME` with the actual GitHub username.

---

## Step 2 – Move the Project to XAMPP

Copy the project folder into:

```text
C:\xampp\htdocs\
```

Example:

```text
C:\xampp\htdocs\DailyBasket
```

---

## Step 3 – Start XAMPP

Open the XAMPP Control Panel and start:

```text
Apache
MySQL
```

---

## Step 4 – Create the Database

Open:

```text
http://localhost/phpmyadmin
```

Create a new database:

```text
dailybasket
```

---

## Step 5 – Import the Database

Import the SQL file:

```text
database/dailybasket.sql
```

into the `dailybasket` database.

---

## Step 6 – Configure Database Connection

Update the database connection file according to your local setup.

Example:

```php
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "dailybasket";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

?>
```

---

## Step 7 – Run the Website

Open your browser and visit:

```text
http://localhost/DailyBasket/
```

---

# 🔐 Security

The application should follow basic security practices including:

- Password hashing
- Session-based authentication
- Role-based access control
- Admin route protection
- Input validation
- Prepared SQL statements
- Server-side validation
- Product stock validation
- Secure order processing

Passwords should never be stored as plain text.

---

# 📱 Responsive Design

DailyBasket is designed to provide a responsive experience across:

- 💻 Desktop
- 💻 Laptop
- 📱 Mobile
- 📲 Tablet

The interface follows a clean and modern grocery-focused design.

---

# 🎨 Brand Identity

## Brand Name

**DailyBasket**

## Tagline

**Daily Essentials, Delivered Fresh**

## Brand Style

The website uses a fresh grocery-inspired visual identity with:

- Green
- Dark Green
- Lime Green
- White
- Light backgrounds

The design represents freshness, convenience, and everyday grocery shopping.

---

# 🔮 Future Enhancements

Future versions of DailyBasket can include:

- 💳 Online Payment Integration
- 🎟️ Coupons and Discount Codes
- ❤️ Wishlist
- ⭐ Product Reviews and Ratings
- 📱 WhatsApp Order Notifications
- 📧 Email Notifications
- 🕐 Delivery Time Selection
- 📊 Advanced Sales Analytics
- 🤖 Smart Product Recommendations
- 📍 Delivery Tracking
- 🏪 Multiple Store Support
- 🌐 Multi-language Support

---

# 🎯 Expected Outcome

DailyBasket provides a complete digital grocery ordering workflow:

```text
Product Discovery
       ↓
Shopping Cart
       ↓
Order Review
       ↓
Checkout
       ↓
Order Placement
       ↓
Admin Processing
       ↓
Order Status
       ↓
Customer Tracking
```

The system provides customers with a convenient way to browse and order groceries while giving administrators a centralized platform to manage products, stock, prices, and customer orders.

---

# 👥 Team

## Team Name

**Challengers**

## Team ID

**FSSD-033**

## Team Members

| # | Name | Role |
|---|---|---|
| 1 | Elakkiyan Velusamy | Team Leader |
| 2 | Nadhin S | Team Member |
| 3 | Guhan S | Team Member |

---

# 🏆 Hackathon Details

**Event:** BIZ HACK'26

**Problem Statement:** PS29 – Simple Grocery Ordering System

**Project Name:** DailyBasket

**Tagline:** Daily Essentials, Delivered Fresh

---

# 📄 License

This project was developed as a hackathon project for educational and demonstration purposes.

---

<p align="center">

## 🛒 DailyBasket

### Daily Essentials, Delivered Fresh

**Made with ❤️ by Team Challengers**

</p>
