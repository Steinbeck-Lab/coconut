# COCONUT - Installation Guide

## Prerequisites

Before you begin, make sure you have the following prerequisites installed on your system:

- PHP (>= 8.3)
- Node
- Composer
- Docker

## Step 1: Clone the Repository

Clone the COCONUT project repository from Github using the following command:

```bash
git clone https://github.com/Steinbeck-Lab/coconut.git
```

## Step 2: Navigate to Project Directory

```bash
cd coconut
```

## Step 3: Install Dependencies

Install the PHP dependencies using Composer:

```
composer install
```

Install the JS dependencies using NPM:

```
npm install
```

## Step 4: Configure Environment Variables

```bash
cp .env.example .env
```

Edit the .env file and set the necessary environment variables such as database credentials.

## Step 5: Start Docker Containers

Run the Sail command to start the Docker containers:

```bash
./vendor/bin/sail up -d
```

## Step 6: Generate Application Key

Generate the application key using the following command:

```bash
./vendor/bin/sail artisan key:generate
```

## Step 7: Run Database Migrations
Run the database migrations to create the required tables:

```bash
./vendor/bin/sail artisan migrate
```

## Step 8: Seed the Database (Optional)
If your project includes seeders, you can run them using the following command:

```bash
./vendor/bin/sail artisan db:seed
```

## Step 9: Access the Application

Once the Docker containers are up and running, you can access the Laravel application in your browser by visiting:

```bash
http://localhost
```

## Step 10: Run Vite Local Development Server

To run the Vite local development server for front-end assets, execute the following command:

```bash
npm run dev
```

or 

```bash
yarn dev
```


Once the Docker containers are up and running, you can access the Laravel application in your browser by visiting:

```bash
http://localhost
```

Congratulations! You have successfully installed the Laravel project using Sail.

Note: You can stop the Docker containers by running ./vendor/bin/sail down from your project directory.

## Step 11: Run Vite Local Development Server

Pull the latest from the origin development branch into your local and create a branch of your own for the modifications you want to make. Please ensure that the changes or new modules are tested thoroghly before pushing them to the origin. Once pushed, create a pull request to the development. Upon approval, your contribution will be merged to the dev and consequently into production in line with the release cycles.