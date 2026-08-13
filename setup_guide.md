# Real Estate Listing System Setup Guide

This document provides step-by-step instructions for setting up the Real Estate Listing System on your local machine using XAMPP.

## Prerequisites

Before you begin, make sure you have the following installed:

1. [XAMPP](https://www.apachefriends.org/index.html) - Version 7.4 or later
2. [Visual Studio Code](https://code.visualstudio.com/) - Any recent version
3. A modern web browser (Chrome, Firefox, Edge, etc.)

## Step 1: Install XAMPP

1. Download XAMPP from the official website: https://www.apachefriends.org/index.html
2. Follow the installation instructions for your operating system
3. Start the XAMPP Control Panel and ensure that both Apache and MySQL services are running

## Step 2: Set Up the Project

1. Navigate to the XAMPP installation directory (usually `C:\xampp` on Windows or `/Applications/XAMPP` on macOS)
2. Find the `htdocs` folder within the XAMPP directory
3. Create a new folder named `realestate` within the `htdocs` folder
4. Copy all the project files into this new `realestate` folder

## Step 3: Set Up the Database

1. Open your web browser and navigate to `http://localhost/phpmyadmin/`
2. Click on the "Import" tab in the top navigation menu
3. Click the "Browse" button and select the `database.sql` file from the project files
4. Click the "Go" button at the bottom of the page to import the database

Alternatively, you can:

1. Create a new database named `real_estate` in phpMyAdmin
2. Select the new database
3. Go to the "SQL" tab
4. Open the `database.sql` file from the project files in a text editor
5. Copy and paste the entire contents of the file into the SQL query box
6. Click "Go" to execute the SQL queries

## Step 4: Configure the Database Connection

1. Open the file `includes/config.php` in Visual Studio Code
2. Verify that the database configuration matches your setup:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'real_estate');
   