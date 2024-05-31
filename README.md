# PHP & MariaDB Development Enviroment Tester

## Purpose

This simple app will help setup a working enviroment for your final project. It will also check that PHP is working, and that a connection to the MariaDB database server can be established. 

## Usage

1. Clone this repository.
2. Ensure Docker Desktop is running.
3. Open a terminal and change to the folder where you cloned this repository.
4. Run the run.cmd script.  
    4.1. On Windows, type **.\run.cmd**.    
    4.2. On macOS or Linux, type: **./run.cmd**.
5. Open [http://localhost:8001](https://localhost:8001) in your browser.

## Details

PHP has been setup as usual. A MariaDB server has also been created. Details follow:

- **Host**: mariadb
- **Database Name:** kahuna
- **User**: root
- **Pass**: root

The services started include:
- API Server on [http://localhost:8000](https://localhost:8000).
- Client on [http://localhost:8001](https://localhost:8001).

## Issues

When adding a product (POST) i get method not allowed.

## Working as planned

- At the first screen user is shown a login form if no user created can proceed with creating a user.
- After creating a user the user is then send back to the login form where he can login.
- After logging in user is shown the homepage with his registered products (no products if newly created user). Now the user is allowed to register a product on the Add a prodduct button which loads a form where user can insert a product.
- The system checks few conditions: 
    The serial number if it's allowed. List of allowed serial numbers (KHWM8199911, KHWM8199912, KHMW789991, KHWP890001, KHWP890002, KHSS988881, KHSS988882, KHSS988883, KHHM89762, KHSB0001).
    It checks if the product with that serial number is registered by another user.
    It checks if that product is already registered by the logged in user
If all these conditions are met then product is added to Product List of the user where he can see a table of Product ID, Serial, Warranty Lenth.
If any of the conditions are not met then the product is added and appropriate message is shown in the network response tab.

- When clicked on Product Registration in the menu on the top user is taken to a page where he can register a product to see it's warranty  (start date, end date and purchase date) by filling a form and entering userID, productID and Warranty Length

- When on home page viewing Product List customer can click on Submit Ticket to submit a ticket regarding a issue with a product.


## Needs to be implemented

- To bound the product warranty registration with already registered products and also to a user id so they can be only registered by unique users
- To bound the support tickets to a user id so they load only the logged in user tickets
- To implement ticket reply system
- To add admin dashboard where admin can have rights to edit and close tickets.

## Postman API

In the support folder there is Postman folder with the environment and collections

