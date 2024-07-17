Setup
- Run "composer install".
- Run "npm install".
- Create a new file .env on root.
- Copy and paste content from .env.example to .env.
- To connect with database, set up DB_DATABASE, DB_USERNAME and DB_PASSWORD.
- For Google Map to work add GOOGLE_MAPS_API_KEY.
- For stripe to work add STRIPE_KEY and STRIPE_SECRET.
- Run "php artisan migrate" to generate tables in the connected database.
- Run "php artisan key:generate".

Run Project
- Run "npm run dev" on a separate terminal window and keep it running.
- Run "php artisan serve" on a separate terminal window and keep it running. Project should be accessible at the URL shown in terminal.
