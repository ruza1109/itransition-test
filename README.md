CSV Import Task

Here is my submission for the CSV import task. I've separated the logic using a Pipeline pattern (Command -> Handler -> Pipes -> Repository) to keep things clean and stick to SOLID principles. Memory usage is handled by using Spatie\SimpleExcel which reads the file in chunks.

To make my work and reviewing easier, I've dockerized the environment.

How to run it:

1. Spin up the containers:
docker compose up -d

2. Create (copy .env.example) .env, install dependencies and run migrations:

    docker compose exec app cp .env.example .env

    docker compose exec app composer install

    docker compose exec app php artisan migrate

(Note: Since the assignment assumes an existing database and table, make sure to use the 'root' MySQL user to import the provided SQL schema and create the 'importTest' database before running the migration above. Password for root is 'developer').

Running the import:
The stock.csv file is already in the src directory. You can run the import with:
docker compose exec app php artisan app:products-csv-import stock.csv
(You can append --test if you want to run it without hitting the database).

Running tests:
docker compose exec app php artisan test

Note on external data issues: If this were a real-world script, I'd probably add a step to strictly enforce UTF-8 encoding and normalize line endings (CRLF to LF) before parsing the CSV to avoid weird character bugs.
