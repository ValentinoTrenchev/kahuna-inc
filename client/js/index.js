document.addEventListener('DOMContentLoaded', init);


const BASE_URI = 'http://localhost:8000/kahuna/api/';

let products = [];
let rproducts = [];
let supportTickets = [];

//Initialise
function init() {
  setInitialColourMode();
  checkAndRedirect('home', loadProducts);
}


//Set colour mode as per user preference
function setInitialColourMode() {
  let colorMode = localStorage.getItem("kahuna_color")
  if (colorMode) {
    toggleColourMode(colorMode);
  } else {
    toggleColourMode(window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light');
  }
}

//Dark - Light Mode Toggle
function toggleColourMode(mode) {
  document.documentElement.setAttribute("data-bs-theme", mode);
  const switcher = document.getElementById('color-switch-area');
  if (mode === 'dark') {
    switcher.innerHTML = '<i class="bi-moon-stars-fill"></i>';
  } else {
    switcher.innerHTML = '<i class="bi-sun-fill"></i>';
  }
  localStorage.setItem("kahuna_color", mode);
}

//Show Page
async function showView(view) {
  if (view) {
    return fetch(`includes/${view}.html`)
      .then(res => res.text())
      .then(html => document.getElementById('mainContent').innerHTML = html);
  }
  return null;
}

//Check Token
async function isValidToken(token, user, cb) {
  return fetch(`${BASE_URI}token`, {
    headers: {
      'X-Api-Key': token,
      'X-Api-User': user
    }
  })
    .then(res => res.json())
    .then(res => cb(res.data.valid));
}

//Get Form Data
function getFormData(object) {
  const formData = new FormData();
  Object.keys(object).forEach(key => formData.append(key, object[key]));
  return formData;
}

// ------------------- LOGIN ------------------- //


//Check if logged in and redirect
function checkAndRedirect(redirect = null, cb = null) {
  console.log("ENTERING CHECK AND REDIRECT");
  let token = localStorage.getItem("kahuna_token");

  if (!token) {
    showView('login').then(() => bindLogin(redirect, cb));
  } else {
    let user = localStorage.getItem("kahuna_user");
    isValidToken(token, user, (valid) => {
      if (valid) {
        document.getElementById('logout-button').style.display = 'block';
        showView(redirect).then(cb);
      } else {
        document.getElementById('logout-button').style.display = 'none';
        showView('login').then(() => bindLogin(redirect, cb));

      }
    });
  }
}

//Login Form Submission
function bindLogin(redirect, cb) {
  document.getElementById('loginForm').addEventListener('submit', (evt) => {
    evt.preventDefault();
    fetch(`${BASE_URI}login`, {
      mode: 'cors',
      method: 'POST',
      body: new FormData(document.getElementById('loginForm'))
    })
      .then(res => res.json())
      .then(res => {
        localStorage.setItem('kahuna_token', res.data.token);
        localStorage.setItem('kahuna_user', res.data.user);
        //check if admin
        localStorage.setItem('kahuna_level', res.data.accessLevel);
        showView(redirect).then(cb);
      })


      .catch(err => showMessage(err, 'danger'));

  });
}

//Register User
function registerUser() {
  showView('register').then(() => {
    document.getElementById('registerForm').addEventListener('submit', (evt) => {
      evt.preventDefault();
      fetch(`${BASE_URI}user`, {
        mode: 'cors',
        method: 'POST',
        body: new FormData(document.getElementById('registerForm'))
      })
        .then(showView('login').then(() => bindLogin('home', bindHome)))
        .catch(err => showMessage(err, 'danger'));
    });
  });
}

//Logout User
function logout() {
  localStorage.removeItem("kahuna_token");
  localStorage.removeItem("kahuna_user");
  localStorage.removeItem("kahuna_level");

  window.location.href = 'index.html';
}


// ------------------ PRODUCTS ------------------ //


// LOAD PRODUCTS

function loadProducts() {
  checkAndRedirect('home', () => {
    const token = localStorage.getItem("kahuna_token");
    const userId = localStorage.getItem("kahuna_user");

    if (!token || !userId) {
      // Handle case where token or user ID is missing
      return;
    }

    fetch(`${BASE_URI}product`, {
      mode: 'cors',
      method: 'GET',
      headers: {
        'X-Api-Key': token,
        'X-Api-User': userId
      }
    })
      .then(response => response.json())
      .then(response => {
        const products = response.data;
        displayProducts(products, userId);
      })
      .catch(error => console.error('Error:', error));
  });
}


// DISPLAY PRODUCTS

function displayProducts(products, userId) {
  let html = '';

  // Filter the products array to only include products registered by the logged-in user
  const userProducts = products.filter(product => product.userId === parseInt(userId));

  if (userProducts.length === 0) {
    html = '<p>You have not registered any product yet!</p>';
  } else {
    html = '<table><thead>';
    html += '<tr><th>ID</th><th>Serial</th><th>Name</th><th>Warranty Length</th><th>Submit a ticket</th></tr>';
    html += '</thead><tbody>';
    userProducts.forEach(product => {
      html += '<tr>';
      html += `<td class="table-data">${product.id}</td>`;
      html += `<td class="table-data">${product.serial}</td>`;
      html += `<td class="table-data">${product.name}</td>`;
      html += `<td class="table-data">${product.warrantyLength}</td>`;
      html += `<td class="table-data"><button class="btn btn-primary load-ticket" type="button" style="margin: 8px;">Submit</button></td>`;
      html += '</tr>';
    });
    html += '</tbody></table>';
  }
  document.querySelector('#productList').innerHTML = html;

  // Submit a ticket button
  document.querySelectorAll('.load-ticket').forEach(button => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      loadTicket();
      // Use showView to load ticket.html into the mainContent div
      showView('ticket').then(() => {
        bindAddTicket();
        // If you need to bind any JavaScript or event listeners to the newly loaded content,
        // this would be a good place to do it.
      });
    });
  });
}

// ADD NEW PRODUCT 

function bindAddProduct() {
  document.querySelector('#productForm').addEventListener('submit', (event) => {
    event.preventDefault();

    // Serialize form data into JSON
    const formData = new FormData(document.querySelector('#productForm'));
    const productData = {};
    formData.forEach((value, key) => {
      productData[key] = value;
    });

    // Add additional data if needed
    productData['user'] = localStorage.getItem("kahuna_user");

    fetch(`${BASE_URI}product`, {
      method: 'POST',
      mode: 'cors',
      headers: {
        'Content-Type': 'application/json', // Set content type to JSON
        'X-Api-Key': localStorage.getItem("kahuna_token"),
        'X-Api-User': localStorage.getItem("kahuna_user")
      },
      body: JSON.stringify(productData), // Convert form data to JSON
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        // Handle successful response
        console.log('Product registered successfully:', data);
        loadProducts(); // Reload products after adding a new one
      })
      .catch(error => {
        // Handle error
        console.error('Error:', error);
      });
  });
};


// ----------------- TRANSACTIONS ----------------- //


// LOAD TRANSACTION

function loadTransaction() {
  console.log("ENTERING LOAD TRANSACTION");
  checkAndRedirect('register-product', () => {
    const token = localStorage.getItem("kahuna_token");
    const userId = localStorage.getItem("kahuna_user");

    if (!token || !userId) {
      // Handle case where token or user ID is missing
      return;
    }
    fetch(`${BASE_URI}transaction`, {
      mode: 'cors',
      method: 'GET',
      headers: {
        'X-Api-Key': token,
        'X-Api-User': userId
      }
    })
      .then(response => response.json())
      .then(response => {
        const rproducts = response.data;

        // Display the registered products
        const newContent = displayRegisteredProducts(rproducts);

        // Update the homepage with the combined content
        const productListElement = document.getElementById('rproductList');
        const registerProductTitle = document.getElementById('register-product-title');
        if (productListElement) {
          productListElement.innerHTML += newContent; // Append to existing content
          registerProductTitle.style.display = 'none'; // Hide the title

        } else {
          //registerProductTitle.style.display = 'block'; // Show the title
          console.error('Element with ID "rproductList" not found.');
        }
      })
  });
}

//DISPLAY REGISTERED PRODUCTS BY TRANSACTION 

function displayRegisteredProducts(rproducts) {
  let html = '';
  if (!rproducts || rproducts.length === 0) {
    html = '<p>No registered products found!</p>';
  } else {
    html = '<table class="registered-products table-dark"><thead>';
    html += '<tr><th>Product ID</th><th>Warranty Start Date</th><th>Warranty End Date</th><th>Purchase Date</th></tr>';
    html += '</thead><tbody>';
    for (const product of rproducts) {
      html += '<tr>';
      html += `<td>${product.productId}</td>`; // Make sure these keys match your JSON structure.
      html += `<td>${new Date(product.warranty_start_date).toLocaleDateString()}</td>`; // Format date.
      html += `<td>${new Date(product.warranty_end_date).toLocaleDateString()}</td>`; // Format date.
      html += `<td>${new Date(product.purchase_date).toLocaleDateString()}</td>`; // Format date.
      html += '</tr>';
    }
    html += '</tbody></table>';
  }
  return html; // Return the HTML content
}

// ADD NEW TRANSACTION 

function bindAddRegisteredProduct() {
  document.getElementById('registerproduct').addEventListener('submit', (evt) => {
    evt.preventDefault();
    productData = new FormData(document.getElementById('registerproduct'));
    fetch(`${BASE_URI}product/buy`, {
      mode: 'cors',
      method: 'POST',
      headers: {
        'X-Api-Key': localStorage.getItem("kahuna_token"),
        'X-Api-User': localStorage.getItem("kahuna_user")
      },
      body: productData
    })

      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok.');
        return response.json();
      })
      .then(data => {
        console.log(data);
        loadTransaction();
      })
      .catch(err => console.error(err));

  });

};