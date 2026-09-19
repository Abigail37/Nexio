# Sales Management System — Master Project Prompt & Specification

## 1. PROJECT OVERVIEW

Build a complete Sales Management System with two connected sides:

1. A public-facing ecommerce storefront where customers can browse products, add products to a cart, place orders, and view their orders.
2. A protected management/admin system where the Superuser, CEO, Manager, and other staff roles can perform business operations according to their permissions.

The system is being developed as a portfolio/SIWES project.

The application must be built gradually and carefully. Do not attempt to build the entire system at once.

--------------------------------------------------
TECH STACK
--------------------------------------------------

Frontend:
- HTML
- CSS
- JavaScript

Backend:
- PHP

Database:
- MySQL / MariaDB

Local development:
- XAMPP

Dependencies:
- Composer
- PHPMailer

Authentication:
- PHP sessions
- Password hashing with password_hash()
- Password verification with password_verify()
- Email OTP verification
- Secure password setup links for staff accounts

--------------------------------------------------
CORE DEVELOPMENT RULE
--------------------------------------------------

THE PROJECT MUST BE BUILT STRICTLY PHASE BY PHASE.

Do not move to the next phase until the current phase has been:
- implemented
- tested
- debugged
- verified
- and explicitly marked COMPLETE

If a previous project specification or uploaded MD file is unavailable, do NOT guess what it contained.

Tell the user that the specification is unavailable and request the current MD/project prompt.

Never silently invent or replace project requirements.

--------------------------------------------------
CURRENT PROJECT STATUS
--------------------------------------------------

PHASE 1 — FOUNDATION
STATUS: COMPLETE

The following have already been implemented and tested:

- Database
- Database connection
- Project folder structure
- Composer
- PHPMailer
- SMTP email
- Customer registration
- Email OTP verification
- OTP expiration
- OTP attempt limits
- Password hashing
- Login
- Logout
- PHP sessions
- Role-based authentication
- PHP permission system
- Superuser account
- CEO account creation
- Manager account creation
- Staff account creation
- Staff password setup through email
- Staff editing
- Staff activation/deactivation
- Protected Superuser account
- Role authorization testing
- Session/security testing
- Authentication failure-case testing

DO NOT rebuild these features unless a future phase specifically requires modifying them.

The next phase is:

PHASE 2 — STOREFRONT CORE

--------------------------------------------------
2. USER ROLES
--------------------------------------------------

The system has the following roles:

1. Superuser
2. CEO
3. Manager
4. Sales Representative
5. Cashier
6. Supplier
7. Delivery Personnel
8. Accountant
9. Customer

Each staff account has exactly one role.

--------------------------------------------------
3. STAFF HIERARCHY
--------------------------------------------------

The general organizational hierarchy is:

SUPERUSER
    ↓
CEO
    ↓
MANAGER
    ↓
---------------------------------
|        |       |      |       |
Sales   Cashier Supplier Delivery Accountant
Rep
---------------------------------

The Superuser is the highest-level system account.

The Superuser account:
- belongs to the system owner
- is protected
- cannot be deleted
- cannot be deactivated
- cannot be modified through normal staff-management actions
- can create CEO accounts

The CEO:
- can manage staff according to the configured PHP permissions
- can create Manager accounts
- can edit/deactivate accounts they are authorized to manage

Managers:
- can create Sales Representatives
- can create Cashiers
- can create Suppliers
- can create Delivery Personnel
- can create Accountants
- can edit/deactivate staff they are authorized to manage

Do not assume that the original MD's simpler hierarchy is still authoritative. This expanded hierarchy is the current project requirement.

--------------------------------------------------
4. PERMISSION SYSTEM
--------------------------------------------------

Permissions are stored in PHP.

DO NOT create a database permissions system unless explicitly requested later.

Permissions are defined centrally in: includes/permissions.php

Authorization must be enforced server-side.

Never rely only on hiding buttons or menu items.

Example: hasPermission('edit_products')

and: requirePermission('edit_products')

should be used where appropriate.

If a user manually enters a protected URL, the backend must still deny access.

--------------------------------------------------
5. CURRENT STAFF PERMISSIONS
--------------------------------------------------

Superuser:
- Full system access
- Manage staff
- Manage products
- Manage inventory
- Manage categories
- Manage suppliers
- Manage orders
- Manage payments
- View sales
- View reports
- Manage settings
- Create CEO accounts

CEO:
- Full business-management access according to the configured PHP permissions
- Manage staff
- Create Managers
- Manage products
- Manage inventory
- Manage categories
- Manage suppliers
- Manage orders
- Manage payments
- View sales
- View reports/settings where permitted

Manager:
- Dashboard
- Products
- Add products
- Edit products
- Delete products
- Inventory
- Orders
- Sales
- Payments where permitted
- Suppliers
- Categories
- Staff accounts under their authority

Sales Representative:
- Dashboard
- View products
- Add products
- Edit products
- Delete products
- View orders
- Manage orders
- View sales

Cashier:
- Dashboard
- View sales
- View payments
- Manage payments

Supplier:
- Dashboard
- View products
- Supplier-related management

Delivery:
- Dashboard
- View orders
- Manage assigned/order delivery status

Accountant:
- Dashboard
- View sales
- View payments
- Financial-related access as implemented

Customer:
- Browse products
- View product details
- Cart
- Checkout
- Place orders
- View own orders
- View/manage own profile

These permissions are implemented in PHP and should remain centralized.

--------------------------------------------------
6. AUTHENTICATION REQUIREMENTS
--------------------------------------------------

Customer registration is public.

Staff registration is NOT public.

Customers register through the public registration page.

Staff accounts are created by authorized staff.

Customer registration:
- Name
- Email
- Password
- Confirmation/password validation
- Email verification
- OTP sent through email

OTP:
- Six-digit OTP
- Sent through PHPMailer
- Expires after 5 minutes
- Limited verification attempts
- Incorrect OTP must be rejected
- Expired OTP must be rejected

Passwords:
- Always use password_hash()
- Always use password_verify()
- Never store plain-text passwords

Staff onboarding:

Authorized staff creates an account.

The system:
1. creates the account
2. generates a secure password setup token
3. stores only the token hash
4. emails the setup link
5. allows the staff member to create their own password
6. invalidates the setup token after use
7. expires the setup link after its configured lifetime

Staff should NOT receive plain-text passwords through email.

Login:
- Validate email
- Validate password
- Check account exists
- Check account is active
- Check email verification where required
- Verify password
- Create secure session
- Regenerate session ID after successful login
- Redirect according to role

Logout:
- Destroy session
- Remove session data
- Return user to login

--------------------------------------------------
7. SECURITY REQUIREMENTS
--------------------------------------------------

All database queries must use prepared statements.

Never concatenate user input directly into SQL queries.

All protected pages must enforce authentication server-side.

All role/permission checks must happen server-side.

Validate all user input.

Escape output with htmlspecialchars() where appropriate.

Use password_hash() and password_verify().

Use secure random tokens for:
- OTPs
- Password setup links
- Other authentication tokens

Password setup tokens must be stored hashed.

Expired/used tokens must not work.

Protected Superuser accounts must not be deletable or deactivatable.

Users must not be able to modify accounts outside their authority simply by changing an ID in the URL.

Eventually, state-changing operations should use POST requests and CSRF protection.

Do not expose SMTP credentials in publicly accessible files or repositories.

--------------------------------------------------
8. PROJECT FOLDER STRUCTURE
--------------------------------------------------

Use the existing project folder structure.

Do not randomly restructure the project without discussing it first.

Important folders include:

/public
/admin
/includes
/sql
/vendor

Existing authentication/support files include concepts such as:

/includes/db.php
/includes/auth.php
/includes/permissions.php
/includes/password-setup-mailer.php

The exact existing filenames should be respected when continuing development.

--------------------------------------------------
9. PUBLIC STOREFRONT
--------------------------------------------------

The storefront should contain:

1. Home
2. Products/Menu
3. Product Detail
4. Cart
5. Checkout
6. Contact
7. Login
8. Register
9. Customer Account
10. Customer Order History

--------------------------------------------------
10. HOME PAGE
--------------------------------------------------

The homepage should include:

- Hero/banner
- Brand introduction
- Featured products
- Categories
- Promotions
- Clear navigation
- Call-to-action buttons

The design should be clean, modern, responsive, and suitable for an ecommerce business.

--------------------------------------------------
11. PRODUCTS PAGE
--------------------------------------------------

Products page should support:

- Product listing
- Product image
- Product name
- Description
- Price
- Stock status
- Category
- Search
- Category filtering
- Sorting where appropriate
- Add to cart

Product cards should have clear calls to action.

--------------------------------------------------
12. PRODUCT DETAIL
--------------------------------------------------

Each product detail page should show:

- Product image
- Product name
- Description
- Price
- Category
- Supplier information where appropriate
- Stock availability
- Quantity selection
- Add to Cart

The page must prevent customers from ordering more than available stock.

--------------------------------------------------
13. CART
--------------------------------------------------

Cart should support:

- Add product
- Remove product
- Increase quantity
- Decrease quantity
- Quantity validation
- Subtotal
- Total
- Continue shopping
- Proceed to checkout

The cart may use session-based or database-based storage depending on the implementation decision.

--------------------------------------------------
14. CHECKOUT
--------------------------------------------------

Checkout should show:

- Customer information
- Contact information
- Delivery/shipping information
- Order summary
- Product quantities
- Prices
- Total amount
- Place order button

Customers must be able to review what they are ordering before final submission.

Payment integration can initially be simulated unless a real payment gateway is explicitly introduced.

If a real payment gateway is introduced later, integrate it carefully and securely.

--------------------------------------------------
15. CUSTOMER ACCOUNT
--------------------------------------------------

Customers should be able to:

- View profile
- View order history
- View order details
- View order status
- Update permitted profile information
- Log out

Customers must only be able to access their own orders.

--------------------------------------------------
16. ADMIN DASHBOARD
--------------------------------------------------

The dashboard should remain relatively simple.

Analytics should primarily appear as cards.

Examples:

- Total Sales
- Total Orders
- Total Products
- Low Stock Products
- Out-of-Stock Products
- Other useful business metrics

Transactions, low-stock products, and out-of-stock products should be displayed in tables.

Do not overcomplicate the dashboard with unnecessary analytics.

--------------------------------------------------
17. PRODUCT MANAGEMENT
--------------------------------------------------

Authorized users should be able to manage products according to their permissions.

Product functionality includes:

- Add product
- Edit product
- Delete product
- View products
- Stock quantity
- Price
- Description
- Category
- Supplier
- Product image

All actions must respect PHP permissions.

--------------------------------------------------
18. CATEGORY MANAGEMENT
--------------------------------------------------

There must be a dedicated category-management page.

Categories should NOT be created only through the Add Product page.

The system should contain:

Add Category page
Category list
Edit category
Delete category where appropriate, you can't delete a cateogry that has products in it

The Add Product page should contain a category dropdown populated from the categories table.

--------------------------------------------------
19. INVENTORY
--------------------------------------------------

Inventory should display:

- Product
- Category
- Current stock
- Stock status
- Low-stock indication
- Out-of-stock indication

Authorized users can edit inventory according to their permissions.

Stock should be updated correctly when orders are processed.

--------------------------------------------------
20. SUPPLIER MANAGEMENT
--------------------------------------------------

Supplier management should support:

- Supplier list
- Add supplier
- Edit supplier
- Delete/deactivate supplier where appropriate
- Contact information
- Products associated with supplier

Permissions must be respected.

--------------------------------------------------
21. ORDER MANAGEMENT
--------------------------------------------------

Orders should support statuses such as:

- Pending
- Processing
- Shipped
- Completed
- Cancelled

Authorized staff should be able to update order status.

Customers should be able to see the status of their own orders.

Order records must preserve:
- Customer
- Products
- Quantity
- Price at purchase
- Total
- Status
- Date/time

--------------------------------------------------
22. SALES AND TRANSACTIONS
--------------------------------------------------

Sales/transaction records should be structured so they can later support:

- Dashboard totals
- Reports
- Revenue analysis
- Order/payment tracking
- Accounting functions

Cashier and Accountant access must follow their PHP permissions.

--------------------------------------------------
23. REPORTS
--------------------------------------------------

Reports will eventually include:

- Sales over time
- Best-selling products
- Revenue by category
- Other useful business metrics

Reports should be accessible only to authorized roles.

--------------------------------------------------
24. STAFF MANAGEMENT
--------------------------------------------------

Staff management includes:

- View staff
- Create staff
- Edit staff
- Activate staff
- Deactivate staff
- Role assignment
- Password setup via email
- Creator tracking

Customers must not appear in the staff-management list.

Protected Superuser accounts cannot be deleted or deactivated.

Authorization must be checked server-side.

--------------------------------------------------
25. CONTACT
--------------------------------------------------

Contact page should contain:

- Name
- Email
- Phone where appropriate
- Message

Messages should be stored in the database and/or handled through email depending on the final implementation.

--------------------------------------------------
26. UI / DESIGN
--------------------------------------------------

The interface should be:

- Clean
- Modern
- Responsive
- Professional
- Simple to navigate
- Consistent across pages

The storefront should have a polished ecommerce appearance.

Do not prioritize visual complexity over usability.

--------------------------------------------------
27. DATABASE
--------------------------------------------------

The current exported sql/schema.sql is the source of truth for the current database structure.

Do NOT invent a new schema when an existing table/column already supports the requirement.

Before adding or modifying database structures:

1. inspect the current schema
2. determine whether the required structure already exists
3. make the smallest necessary change
4. test the change

Maintain proper foreign keys and relationships.

--------------------------------------------------
28. DEVELOPMENT WORKFLOW
--------------------------------------------------

For every phase:

STEP 1
Explain briefly what we are building.

STEP 2
Create only the files/code necessary for the current step.

STEP 3
Test the implementation.

STEP 4
Debug any errors.

STEP 5
Test successful and unsuccessful cases.

STEP 6
Only after the current step works, continue.

Never dump the entire remaining project at once.

Do not move to another phase simply because the code has been written.

A phase is complete only when its functionality has been tested.

--------------------------------------------------
29. DEBUGGING RULE
--------------------------------------------------

When something breaks:

- Stop
- Identify the exact error
- Inspect the relevant file/code
- Fix the actual cause
- Retest
- Do not introduce unrelated changes

Do not repeatedly rewrite working code.

If the problem depends on a file that is not available, ask the user to upload it instead of guessing.

--------------------------------------------------
30. CURRENT PHASE PLAN
--------------------------------------------------

PHASE 1 — FOUNDATION
STATUS: COMPLETE

Completed:
- Database
- Connection
- Authentication
- OTP
- Login/logout
- Sessions
- Permissions
- Superuser
- CEO
- Manager
- Staff
- Password setup
- Staff management
- Security testing

--------------------------------------------------

PHASE 2 — STOREFRONT CORE
STATUS: NEXT

Build strictly in steps.

Expected components:

1. Storefront foundation
2. Home page
3. Product/category database integration
4. Products/menu page
5. Product detail page
6. Search/filter/sort
7. Cart
8. Cart validation
9. Customer-facing UI integration
10. Phase 2 testing and security review

Do not move to Phase 3 until Phase 2 is complete.

--------------------------------------------------

PHASE 3 — CHECKOUT & ORDERS

Build:

1. Checkout page
2. Customer information
3. Delivery/shipping information
4. Order summary
5. Order creation
6. Order items
7. Stock validation
8. Stock deduction
9. Customer order history
10. Order detail
11. Order status
12. Payment integration if required
13. Testing/security review

Do not move to Phase 4 until Phase 3 is complete.

--------------------------------------------------

PHASE 4 — ADMIN CORE

Build:

1. Admin dashboard
2. Dashboard metric cards
3. Transaction tables
4. Low-stock table
5. Out-of-stock table
6. Product management
7. Inventory management
8. Category management
9. Supplier management
10. Frontend/admin integration where required
11. Testing/security review

Do not move to Phase 5 until Phase 4 is complete.

--------------------------------------------------

PHASE 5 — ADMIN ADVANCED

Build:

1. Order management
2. Staff management refinements
3. Role-specific workflows
4. Reports
5. Sales analytics
6. Revenue analysis
7. Business reporting
8. Settings
9. Testing/security review

Do not move to Phase 6 until Phase 5 is complete.

--------------------------------------------------

PHASE 6 — POLISH & DEPLOYMENT PREPARATION

Build:

1. Contact form
2. Validation everywhere
3. Error handling
4. Empty states
5. Responsive design
6. UI consistency
7. Security hardening
8. CSRF protection where required
9. Remove development/test files
10. Remove exposed credentials
11. Final database review
12. Final functionality testing
13. Deployment preparation

--------------------------------------------------
31. IMPORTANT PROJECT RULES
--------------------------------------------------

DO NOT:

- Skip phases
- Skip testing
- Rebuild completed functionality unnecessarily
- Invent missing project requirements
- Change working architecture without reason
- Put permissions into MySQL unless explicitly requested
- Allow public users to register staff accounts
- Store plain-text passwords
- Expose credentials
- Trust frontend-only authorization
- Let users access other users' data
- Let protected Superuser accounts be deleted/deactivated

DO:

- Use PHP for backend authorization
- Use prepared statements
- Use secure password hashing
- Use sessions
- Use PHPMailer for required email delivery
- Validate server-side
- Escape output
- Keep code organized
- Reuse existing project functions
- Test every feature
- Fix bugs before moving forward
- Ask for the current MD/project files if project context is missing

--------------------------------------------------
32. AI ASSISTANT BEHAVIOR
--------------------------------------------------

Act as the technical lead for this project.

Be direct and concise.

Do not overwhelm the user with unnecessary explanations.

When giving code:
- tell the user exactly which file to create/edit
- provide the complete relevant code when replacement is safer
- clearly state what to test
- wait for confirmation before moving to the next step

Do not assume a feature works simply because code was written.

When the user says something works, accept that test result and move forward unless there is a clear technical reason to investigate further.

The user wants the project built strictly phase by phase.

The current completed milestone is:

PHASE 1 — FOUNDATION ✅

The next milestone is:

PHASE 2 — STOREFRONT CORE