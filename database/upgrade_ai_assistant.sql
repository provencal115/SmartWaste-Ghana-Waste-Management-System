-- AI Customer Assistant settings + extended knowledge base

INSERT INTO smart_settings (setting_key, setting_value, description)
SELECT 'ai_assistant',
       '{"enabled": true, "assistant_name": "SmartWaste Assistant", "welcome_message": "", "company_info": ""}',
       'Customer-facing AI chatbot assistant'
WHERE NOT EXISTS (SELECT 1 FROM smart_settings WHERE setting_key = 'ai_assistant');

-- Update fallback escalation message
UPDATE chatbot_knowledge
SET response = 'I''m not able to confirm that information. I can help you contact our support team.

Contact Support: {contact_url}
Phone: {phone}
Email: {email}

Try asking about collections, payments, bin sizes, or tap a suggestion below.',
    title = 'Escalation fallback'
WHERE category = 'fallback';

INSERT INTO chatbot_knowledge (category, title, keywords, response, is_enabled, is_suggestion, priority)
SELECT * FROM (
    SELECT 'recycling' AS category, 'Recycling guide' AS title,
           'recycling,recycle,what can i recycle,recyclables,plastic,paper,glass' AS keywords,
           'SmartWaste supports responsible recycling:\n\n• **Paper & cardboard** — dry, clean bundles\n• **Plastics** — bottles and containers (rinse first)\n• **Glass** — bottles and jars\n• **Metal cans** — food and drink cans\n\nPlace recyclables in your assigned bin on collection day. For hazardous waste (batteries, chemicals), contact support: {contact_url}' AS response,
           1 AS is_enabled, 1 AS is_suggestion, 57 AS priority
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM chatbot_knowledge WHERE category = 'recycling' AND title = 'Recycling guide');

INSERT INTO chatbot_knowledge (category, title, keywords, response, is_enabled, is_suggestion, priority)
SELECT * FROM (
    SELECT 'bins' AS category, 'Request new bin' AS title,
           'new bin,get a bin,request bin,additional bin,replace bin,need a bin,get a new bin' AS keywords,
           'To request a **new or replacement bin**:\n\n1. Sign in to your resident dashboard.\n2. Contact support via **Contact Us**: {contact_url}\n3. Or call {phone} with your account email and address.\n\nNew registrations can choose a bin size during sign-up: {register_url}' AS response,
           1 AS is_enabled, 1 AS is_suggestion, 61 AS priority
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM chatbot_knowledge WHERE category = 'bins' AND title = 'Request new bin');

INSERT INTO chatbot_knowledge (category, title, keywords, response, is_enabled, is_suggestion, priority)
SELECT * FROM (
    SELECT 'account' AS category, 'Update address' AS title,
           'update address,change address,update my address,new address,moved house,relocate' AS keywords,
           'To **update your collection address**:\n\n1. Sign in to your resident dashboard.\n2. Use **Contact Us** with your new address and zone: {contact_url}\n3. Or call {phone} — our team verifies and updates your account within 1 business day.\n\nEnsure your bin QR code matches your registered property after any move.' AS response,
           1 AS is_enabled, 1 AS is_suggestion, 59 AS priority
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM chatbot_knowledge WHERE category = 'account' AND title = 'Update address');

INSERT INTO chatbot_knowledge (category, title, keywords, response, is_enabled, is_suggestion, priority)
SELECT * FROM (
    SELECT 'services' AS category, 'Company services' AS title,
           'services,what do you offer,what services,company services,waste services,garbage collection' AS keywords,
           'SmartWaste Ghana provides:\n\n• Scheduled **residential & commercial** waste collection\n• **QR-tracked** colour-coded bins (120L–360L)\n• **Mobile Money & card** billing with digital receipts\n• **GPS-optimised** collector routes\n• **SMS/email** pickup reminders\n• Customer support & complaint tracking\n\nRegister: {register_url} · Contact: {contact_url}' AS response,
           1 AS is_enabled, 0 AS is_suggestion, 54 AS priority
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM chatbot_knowledge WHERE category = 'services' AND title = 'Company services');
