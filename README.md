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

User can log in and register
On the first screen user can see his products
When clicked on register product he can see the products he registered for a warranty expiration (Transaction date, End of warranty date)

## Needs to be implemented

Support ticket and reply ticket still needs to be implemented

## Postman API

In the support folder there is Postman folder with the environment and collections
Trough postman adding products work fine only trough front end client they give the error

