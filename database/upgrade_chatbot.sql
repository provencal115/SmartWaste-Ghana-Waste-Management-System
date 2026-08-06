-- SmartWaste AI Virtual Assistant — knowledge base, chat history, FAQ tracking

CREATE TABLE IF NOT EXISTS chatbot_knowledge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    keywords TEXT NOT NULL COMMENT 'Comma-separated trigger phrases',
    response TEXT NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_suggestion TINYINT(1) NOT NULL DEFAULT 0,
    priority INT NOT NULL DEFAULT 0,
    use_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_enabled (is_enabled),
    INDEX idx_suggestion (is_suggestion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(64) NOT NULL,
    user_id INT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    knowledge_id INT NULL,
    matched_category VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (knowledge_id) REFERENCES chatbot_knowledge(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_faq (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_question VARCHAR(500) NOT NULL,
    knowledge_id INT NULL,
    hit_count INT NOT NULL DEFAULT 1,
    last_asked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hits (hit_count DESC),
    FOREIGN KEY (knowledge_id) REFERENCES chatbot_knowledge(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default knowledge base (keyword-driven offline assistant)
INSERT INTO chatbot_knowledge (category, title, keywords, response, is_enabled, is_suggestion, priority) VALUES
('greeting', 'Welcome greeting', 'hello,hi,hey,good morning,good afternoon,good evening,howdy,greetings',
 'Hello! 👋 I am **SmartWaste Virtual Assistant**. I can help with registration, scheduling pickups, bin sizes, pricing, payments, collection days, and more. How may I assist you today?',
 1, 0, 100),

('greeting', 'Thanks', 'thank you,thanks,thank u,appreciate it,cheers',
 'You are welcome! If you need anything else about SmartWaste services, just ask.',
 1, 0, 90),

('greeting', 'Goodbye', 'bye,goodbye,see you,later,good night',
 'Goodbye! Thank you for choosing SmartWaste Ghana. Have a great day! 🌿',
 1, 0, 90),

('registration', 'How to register', 'register,registration,sign up,signup,create account,new account,get started,join',
 'To register:\n\n1. Click **Get Started** on our homepage or visit the registration page.\n2. Choose your **bin size** (120L, 240L, or 360L) and **payment plan** (Weekly, Bi-weekly, or Monthly).\n3. Enter your personal details and confirm your email.\n4. Your colour-coded bin is assigned within 48 hours with a unique QR code.\n\nRegister here: {register_url}',
 1, 1, 80),

('login', 'Login help', 'login,log in,sign in,cannot login,cant login,login help,access account',
 'To sign in:\n\n1. Go to the **Login** page.\n2. Enter your registered email and password.\n3. If your account is new, confirm your email first using the link we sent.\n\nLogin here: {login_url}\n\nForgot your password? Use **Forgot Password** on the login page.',
 1, 0, 75),

('login', 'Forgot password', 'forgot password,reset password,password reset,lost password,cant remember password',
 'To reset your password:\n\n1. Open the **Forgot Password** page.\n2. Enter your registered email address.\n3. Check your inbox for a reset link (valid for 24 hours).\n4. Set a new password and sign in.\n\nReset here: {forgot_url}',
 1, 0, 76),

('schedule', 'Schedule pickup', 'schedule pickup,book collection,request pickup,schedule collection,book pickup,pick up request',
 'To schedule a pickup:\n\n1. **Sign in** to your resident dashboard.\n2. Open **Schedule** from the menu.\n3. Pick a date and available time slot.\n4. Confirm — you will receive SMS/email reminders.\n\nYou can schedule one-time or recurring collections. Sign in: {login_url}',
 1, 1, 70),

('payment', 'Pricing and plans', 'payment,pricing,price,charges,cost,how much,fees,plan,weekly,bi-weekly,biweekly,monthly',
 'SmartWaste offers flexible **Weekly**, **Bi-weekly**, and **Monthly** plans. Pricing depends on your **bin size**:\n\n{pricing_table}\n\nPay via **Mobile Money** (MTN, Vodafone, AirtelTigo), card, bank transfer, or verified cash. View plans when you register: {register_url}',
 1, 1, 68),

('payment', 'Mobile Money', 'mobile money,momo,mtn,vodafone cash,airteltigo,telecel',
 'We accept **Mobile Money** payments:\n\n• MTN Mobile Money\n• Vodafone Cash\n• AirtelTigo Money\n\nAfter registration, pay from your resident dashboard under **Payments**. You receive a digital receipt instantly.',
 1, 1, 67),

('payment', 'Card payment', 'card payment,credit card,debit card,visa,mastercard,pay online',
 'You can pay with **Visa** or **Mastercard** from your resident dashboard. Go to **Payments**, select an invoice, and choose card payment. A receipt is generated automatically.',
 1, 0, 65),

('bins', 'Bin sizes', 'bin size,bin sizes,small bin,medium bin,large bin,120l,240l,360l,capacity,liters,litres',
 'SmartWaste bin sizes:\n\n• **Small — 120L** — ideal for singles or small households\n• **Medium — 240L** — most popular for families\n• **Large — 360L** — estates and high-volume users\n\nEach bin is QR-tagged and colour-coded for tracking. Choose your size during registration: {register_url}',
 1, 1, 64),

('bins', 'Bin colours', 'bin colour,bin color,bin colours,bin colors,available colours,available colors,what colour,what color',
 'Available bin colours:\n\n{bin_colours_list}\n\nYour assigned colour is linked to your account and visible on your dashboard after registration.',
 1, 0, 63),

('schedule', 'Collection days', 'collection day,collection days,pickup day,when collected,when will my bin,collection time,collection schedule,my schedule',
 'Your **collection schedule** depends on your zone and plan:\n\n1. Sign in to your **Resident Dashboard**.\n2. Open **Schedule** to see upcoming pickup dates and times.\n3. Enable **Notifications** for SMS/email alerts before collection.\n\nSign in: {login_url}',
 1, 1, 62),

('notifications', 'SMS and email alerts', 'notification,notifications,sms,email alert,email alerts,text message,reminder,alerts',
 'SmartWaste sends:\n\n• **SMS** reminders before scheduled pickups\n• **Email** confirmations for registration, payments, and collections\n• **In-app** notifications on your resident dashboard\n\nManage preferences under **Notifications** after signing in.',
 1, 0, 60),

('complaints', 'Report a complaint', 'complaint,complaints,report issue,report problem,collector missed,missed collection,issue,problem,feedback',
 'To report an issue:\n\n1. Sign in and submit **Feedback** from your dashboard.\n2. Or use our **Contact Us** form: {contact_url}\n3. Call support: {phone}\n\nAll complaints are tracked until resolved. For missed collections, include your bin ID and date.',
 1, 1, 58),

('finance', 'Receipts and balance', 'receipt,receipts,payment history,outstanding balance,balance due,invoice,my payments,billing',
 'Financial information is in your **Resident Dashboard → Payments**:\n\n• View **payment history** and download receipts\n• Check **outstanding balance** and due dates\n• Finance managers verify cash payments and issue digital receipts\n\nSign in: {login_url}',
 1, 1, 56),

('contact', 'Contact support', 'contact,phone,email,call,support,help line,customer service,reach you,get in touch',
 'Contact SmartWaste Ghana:\n\n📍 {address}\n📞 {phone}\n📞 Alt: {phone_alt}\n✉️ {email}\n🆘 Emergency: {emergency}\n🕐 {hours}\n\nContact form: {contact_url}',
 1, 1, 55),

('fallback', 'Unknown question', 'unknown',
 'I''m sorry, I couldn''t understand your request.\n\nTry asking about:\n• Registration\n• Schedule Pickup\n• Payments & Pricing\n• Bin Sizes\n• Contact Support\n\nOr tap a suggestion below to get started.',
 1, 0, 0);
