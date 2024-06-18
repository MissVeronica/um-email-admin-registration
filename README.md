# UM Email Admin Registration
Extension to Ultimate Member to replace the WP new user email with an UM Notification email when Admin is doing the User Registration.

## UM Settings -> Email
#### 1. Notification User Email at Admin Registration
Select the UM Notification Email to send to the User when Admin is doing the User Registration.
#### 2. WP Registration Notification Email to Admin
Click the checkbox to deactivate the WP Registration Notification Email to Admin.
#### 3. UM Administration Registration Form ID
Design an UM Registration Form for updating the WP All Users "Info" popup and the email placeholder {submitted_registration} with the WP Form fields.
### Email Template
Edit the new email template "Profile Created by Admin Email" with required placeholders and text depending on your site's setup for new UM users. 

You can also use one of the existing UM email template files and move this UM file to your active Theme's folder .../ultimate-member/email/ and change the file name to  "notification_admin_registration_email.php". 

If you have edited some of the UM email template files you will find those files in the .../ultimate-member/email/ folder.

## Reference
1. Admin User Registrations - https://github.com/MissVeronica/um-admin-user-registrations

## Installation
1. Download the zip file and install as a WP Plugin, activate the plugin.
2. Move the plugin's email template file "notification_admin_registration_email.php" to your active Theme's folder .../ultimate-member/email/
3. Read the guide about custom email templates https://docs.ultimatemember.com/article/1335-email-templates
