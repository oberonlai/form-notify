=== Receive Notifications After Form Submitting - Form Notify for WP Forms ===
Contributors: oberonlai
Donate link: https://paypal.me/oberonlai
Tags: notification, email, alert, message, notify, LINE Login, LINE Notify, LINE Messaging API, SMS, Elementor Form, Gravity Form, Fluent Form
Requires at least: 4.8
Tested up to: 6.6.2
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Supercharge your WordPress form plugins notifications using a WYSIWYG editor.

== Description ==

The plugin can assist website owners using form plugins by allowing form submitters to receive relevant submission result information through LINE official account, SMS, and email. Administrators can also receive notifications via LINE Notify for subsequent tracking and management purposes.

The currently supported form plugins are as follows:
	- Elementor Form
	- Fluent Form
	- Gravity Form
The currently supported push notification channels are as follows:
	- LINE Messaging API
	- LINE Notify
	- Email
	- Every8d SMS
	- Mitake SMS
	- easyGo SMS`

= Tutorial =

1. Installing the Form Notify Plugin

First, download the plugin from the following link:
https://oberonlai.blog/form-notify.zip

Upload the plugin from the WordPress dashboard and click “Activate.” After activation, you will see the “Form Notify” menu in the sidebar of the admin dashboard.

2. Setting Up LINE Login and LINE Channel

Navigate to Form Notify > Settings, and select the LINE Channel tab:

Here, you will need to retrieve the LINE Login and LINE Messaging API Channel information from the LINE Developer Console. You can refer to the following guides for detailed instructions:

	- https://developers.line.biz/en/docs/line-login/getting-started/
	- https://developers.line.biz/en/docs/basics/channel-access-token/

3. Designing the Login Page and Form Fields

Create a new page to add the LINE login button, or you can insert it anywhere in your posts using the block editor. In the block editor, type a slash / followed by “LINE Login” to find the LINE Login Button widget. Alternatively, click the plus sign in the upper left corner, navigate to the widgets category, and add the LINE Login button:

Once added, you can adjust the button style through the settings on the right-hand side:

If you’re not using the block editor, you can insert the login button using a shortcode. Here’s how to use it:
```[form_notify_linelogin text="Quick Login" size="m" lgmode="true"]```

	Shortcode: form_notify_linelogin
	- Parameters:
	- text: The button text.
	- size: Button size, with options: f (full-width), l, m, s.
	- lgmode: The redirect mode; if set to true, it redirects back to the original page. If set to a URL, it redirects to that page.
	- align: Alignment options: left, center, right.

If the user is already logged in, the login button will not be displayed on the front end.

For the form demonstration, we use Elementor Form. Design three fields: Name, Email, and a radio button for selecting a session.

4. Editing the Push Notification Content

Go to Form Notify > Add New in the WordPress admin dashboard. Enter a title for the page, select the trigger event as “After Elementor Form Submission”, and choose the form you just created. Then click the “Add Notification Method” button below:

In the next step, add the message content for the notification. Select the type as LINE Push Notification, and copy the required data from the Available Parameters section on the right. Paste the recipient’s email in the Custom Push Notification Field. The system will automatically detect if the email is associated with a LINE User ID via LINE Login and will push the message to the associated LINE official account:

5. Testing the Notification Reception

After logging in via LINE on the front end and submitting the form, ensure that LINE receives the registration information.

6. Plugin Settings

The WordPress Form Notify plugin offers the following features:

	- Credential settings for LINE Login, LINE Notify, and LINE Messaging API.
	- Credential settings for SMS services.
	- LINE Login functionality settings, including whether to display the LINE login button in the WordPress login form, redirection URLs after login, customer roles, and handling cases where email authorization is not obtained.
	- View history of push notifications, with filtering options for weekly and monthly data.

= 3rd Party Integration =

- LINE Login Integration

Our plugin integrates with LINE Login, a third-party service provided by LINE Corporation. LINE Login allows users to authenticate using their LINE account, enabling a seamless and secure login process. This integration is essential for enabling users to log into your website without needing to create a new account, leveraging their existing LINE credentials.

https://developers.line.biz/en/docs/line-login/
https://terms2.line.me/ec_global_pp?lang=en

- LINE Messaging API Integration

Our plugin integrates with the LINE Messaging API, a third-party service provided by LINE Corporation. The LINE Messaging API allows developers to send messages and interact with users through LINE, one of the most popular messaging platforms in Asia. With this integration, the plugin can send automated notifications, custom messages, and other interactions directly to users’ LINE accounts.

https://developers.line.biz/en/docs/messaging-api/
https://terms2.line.me/ec_global_pp?lang=en

- LINE Notify Integration

Our plugin integrates with LINE Notify, a third-party service provided by LINE Corporation. LINE Notify allows users to receive real-time notifications from applications or services directly within their LINE app. This integration enables the plugin to send instant updates, alerts, and notifications to a user’s LINE account, providing an easy and effective way to stay informed about important activities or updates.

https://notify-bot.line.me/en/
https://terms2.line.me/ec_global_pp?lang=en

- Every8d SMS Integration

Our plugin integrates with Every8d SMS, a popular third-party SMS gateway service widely used in Taiwan. Every8d SMS allows businesses to send SMS notifications directly to users’ mobile phones, providing a reliable and efficient communication channel for time-sensitive information, such as order confirmations, promotional messages, and important alerts.

https://www.teamplus.tech/product/every8d-value/
https://www.teamplus.tech/en/team-enterprise-communication-and-collaboration-platform-terms-of-use/
https://www.teamplus.tech/en/team-enterprise-communication-and-collaboration-platform-privacy-policy/

- Mitake SMS Integration

Our plugin integrates with Mitake SMS, a reliable and widely used SMS gateway service in Taiwan. Mitake SMS allows businesses to send SMS notifications directly to users’ mobile phones, ensuring fast and effective communication. This integration enables the plugin to send important alerts, such as order confirmations, promotional messages, or reminders, directly to users via SMS.

https://sms.mitake.com.tw/
https://fget.mitake.com.tw/privacy/fsc.html

- easyGo SMS Integration

Our plugin integrates with easyGo SMS, a third-party SMS gateway service that enables businesses to send SMS notifications directly to users’ mobile phones. This integration allows the plugin to send automated messages, such as order updates, alerts, promotional messages, and reminders, ensuring users receive timely and important notifications through SMS.

https://www.easy-go.com.tw/
https://www.easy-go.com.tw/aboutys.php


